<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\Util\RemoteFilesystem as RemoteFilesystemOriginal;

class RemoteFileSystem extends RemoteFilesystemOriginal {

  /**
   * Filterer.
   *
   * @var \eiriksm\DrupalPackageCleaner\Filterer
   */
  protected $filterer;

  public function __construct(RemoteFilesystemOriginal $remoteFilesystem) {
    $original_properties = [];
    $ref_class = new \ReflectionClass(RemoteFilesystemOriginal::class);
    foreach (['io', 'config', 'options', 'disableTls'] as $prop) {
      $obj_prop = $ref_class->getProperty($prop);
      $obj_prop->setAccessible(TRUE);
      $original_properties[$prop] = $obj_prop->getValue($remoteFilesystem);
      if ($prop == 'io') {
        $this->filterer = new Filterer($obj_prop->getValue($remoteFilesystem));
      }
    }

    parent::__construct($original_properties['io'], $original_properties['config'], $original_properties['options'], $original_properties['disableTls']);
  }

  public function get(
    $originUrl,
    $fileUrl,
    $additionalOptions = [],
    $fileName = NULL,
    $progress = TRUE
  ) {
    $data = parent::get($originUrl, $fileUrl, $additionalOptions, $fileName, $progress);
    if ($data) {
      $this->filterer->filterUneeded($data);
    }
    return $data;
  }
}
