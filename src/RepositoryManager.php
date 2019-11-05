<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager as RepositoryManagerOriginal;
use Composer\Util\RemoteFilesystem;

class RepositoryManager extends RepositoryManagerOriginal {
  public function __construct(RepositoryManagerOriginal $repositoryManager) {
    $reflection_class = new \ReflectionClass(RepositoryManagerOriginal::class);
    $original_properties = [];
    foreach (['io', 'config', 'eventDispatcher', 'rfs'] as $property) {
      $property_obj = $reflection_class->getProperty($property);
      $property_obj->setAccessible(TRUE);
      $original_properties[$property] = $property_obj->getValue($repositoryManager);
    }
    parent::__construct($original_properties['io'], $original_properties['config'], $original_properties['eventDispatcher'], $original_properties['rfs']);
    $this->setLocalRepository($repositoryManager->getLocalRepository());
    foreach ($repositoryManager->getRepositories() as $repository) {
      if ($repository instanceof ComposerRepository) {
        $repo_reflected = new \ReflectionClass(ComposerRepository::class);
        $cache_prop = $repo_reflected->getProperty('cache');
        $cache_prop->setAccessible(TRUE);
        $cache_prop->setValue($repository, new Cache($cache_prop->getValue($repository)));
        $rfs_prop = $repo_reflected->getProperty('rfs');
        $rfs_prop->setAccessible(TRUE);
        $rfs_prop->setValue($repository, new \eiriksm\DrupalPackageCleaner\RemoteFileSystem($rfs_prop->getValue($repository)));
      }
      $this->addRepository($repository);
    }
  }
}
