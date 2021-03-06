<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\Semver\VersionParser;

class Filterer
{

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public function filterUneeded($data)
    {
        static $onlineComposerJson;
        if (!isset($onlineComposerJson)) {
            $input_reflected = new \ReflectionClass(ConsoleIO::class);
            $input_prop = $input_reflected->getProperty('input');
            $input_prop->setAccessible(true);
          /** @var \Symfony\Component\Console\Input\ArgvInput $input */
            $input = $input_prop->getValue($this->io);
            $arguments = $input->getArguments();
            if (empty($arguments['command']) || $arguments['command'] != 'require') {
                return $data;
            }
            if (empty($arguments['packages']) || count($arguments['packages']) > 1) {
                return $data;
            }
            $is_updating_core = false;
            foreach ($arguments['packages'] as $package) {
                if (strpos($package, 'drupal/core-recommended') === 0) {
                    $is_updating_core = true;
                }
            }
            if (!$is_updating_core) {
                return $data;
            }
            // Try to parse the version.
            $parts = explode(':', $arguments['packages'][0]);
            $contents = @file_get_contents('https://raw.githubusercontent.com/drupal/core-recommended/' . $parts[1] . '/composer.json');
            if (!$contents) {
                return $data;
            }
            $lockfile = @json_decode($contents);
            if (!$lockfile) {
                return $data;
            }
            $onlineComposerJson = $lockfile;
        }
        $json = json_decode($data);
        if (!isset($json->packages)) {
            return $data;
        }

        $copy = json_decode($data);
        $count_unset = 0;
        foreach ($json->packages as $package_name => $packages_item) {
            $found_it = false;
            foreach (['require'] as $type) {
                foreach ($onlineComposerJson->{$type} as $online_name => $online_version) {
                    if ($online_name == $package_name) {
                        $found_it = true;
                        if ($online_version == 'self.version') {
                              continue;
                        }
                        foreach ($json->packages->{$package_name} as $version => $version_item) {
                            if ($online_version != $version) {
                                $count_unset++;
                                unset($json->packages->{$package_name}->{$version});
                            }
                        }
                    }
                }
            }
            if (!$found_it) {
                unset($json->packages->{$package_name});
            }
        }
        if (is_object($json->packages) && empty(get_object_vars($json->packages))) {
            $json = $copy;
        }
        if (empty($json->packages)) {
            $json = $copy;
        } else {
            $this->io->writeError(sprintf('Removed %d packages for command', $count_unset), true, IOInterface::DEBUG);
        }
        return json_encode($json);
    }
}
