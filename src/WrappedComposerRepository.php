<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Repository\ComposerRepository;
use Composer\Util\RemoteFilesystem;

class WrappedComposerRepository extends ComposerRepository
{

    /**
     * Filterer.
     *
     * @var Filterer
     */
    protected $filterer;

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(\Composer\Cache $cache)
    {
        $this->cache = $cache;
    }

    public function __construct(ComposerRepository $repo)
    {
        $props = [
        'repoConfig',
        'config',
        'io',
        'eventDispatcher',
        'rfs',
        ];
        $reflection_class = new \ReflectionClass(ComposerRepository::class);
        $original_properties = [];
        foreach ($props as $property) {
            $property_obj = $reflection_class->getProperty($property);
            $property_obj->setAccessible(true);
            $original_properties[$property] = $property_obj->getValue($repo);
        }
        parent::__construct($original_properties['repoConfig'], $original_properties['io'], $original_properties['config'], $original_properties['eventDispatcher'], $original_properties['rfs']);
        $this->filterer = new Filterer($original_properties['io']);
    }

    protected function fetchFile($filename, $cacheKey = null, $sha256 = null, $storeLastModifiedTime = false)
    {
        $data = parent::fetchFile($filename, $cacheKey, $sha256, $storeLastModifiedTime);
        return JsonFile::parseJson($this->filterer->filterUneeded(json_encode($data)));
    }
}
