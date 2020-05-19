<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Drivers;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use DoctrineExtensionTree\Metadata\Configuration;

/**
 * Class YamlDriver
 */
class YamlDriver extends AbstractFileDriver
{

    /**
     * @param string $file
     * @return array
     */
    protected function loadMappingFile($file)
    {
        // TODO: Implement loadMappingFile() method.
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @return void
     */
    public function readExtendedMetadata(ClassMetadata $classMetadata, Configuration $configuration)
    {
        // TODO: Implement readExtendedMetadata() method.
    }

}
