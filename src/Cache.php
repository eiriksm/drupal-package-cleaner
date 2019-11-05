<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\Cache as CacheOriginal;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;

class Cache extends CacheOriginal
{

  /**
   * @var \eiriksm\DrupalPackageCleaner\Filterer
   */
    protected $filterer;

    public function read($file)
    {
      $data = parent::read($file);
      return $this->filterer->filterUneeded($data);
    }

    public function __construct(CacheOriginal $cache) {
      $original_properties = [];
      $ref_class = new \ReflectionClass(CacheOriginal::class);
      foreach (['io', 'root', 'whitelist', 'filesystem'] as $prop) {
        $obj_prop = $ref_class->getProperty($prop);
        $obj_prop->setAccessible(TRUE);
        $original_properties[$prop] = $obj_prop->getValue($cache);
        if ($prop == 'io') {
          $this->filterer = new Filterer($obj_prop->getValue($cache));
        }
      }
      parent::__construct($original_properties['io'], $original_properties['root'], $original_properties['whitelist'], $original_properties['filesystem']);
    }
}
