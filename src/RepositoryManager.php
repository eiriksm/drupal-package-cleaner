<?php

namespace eiriksm\DrupalPackageCleaner;

use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager as RepositoryManagerOriginal;
use eiriksm\DrupalPackageCleaner\RemoteFileSystem as RemoteFileSystemCopy;

class RepositoryManager extends RepositoryManagerOriginal
{
    public function __construct(RepositoryManagerOriginal $repositoryManager)
    {
        $reflection_class = new \ReflectionClass(RepositoryManagerOriginal::class);
        $original_properties = [];
        foreach (['io', 'config', 'eventDispatcher', 'rfs'] as $property) {
            $property_obj = $reflection_class->getProperty($property);
            $property_obj->setAccessible(true);
            $original_properties[$property] = $property_obj->getValue($repositoryManager);
        }
        parent::__construct($original_properties['io'], $original_properties['config'], $original_properties['eventDispatcher'], $original_properties['rfs']);
        $this->setLocalRepository($repositoryManager->getLocalRepository());
        foreach ($repositoryManager->getRepositories() as $repository) {
            if ($repository instanceof ComposerRepository) {
                $wrapped_repo = new WrappedComposerRepository($repository);
                $cache = new Cache($wrapped_repo->getCache());
                $wrapped_repo->setCache($cache);
                $repository = $wrapped_repo;
            }
            $this->addRepository($repository);
        }
    }
}
