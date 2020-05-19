<?php

namespace DoctrineExtensionTree\Contracts;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use DoctrineExtensionTree\Metadata\Configuration;

/**
 * Interface MetadataMappingDriverInterface
 */
interface MetadataMappingDriverInterface
{

    /**
     * @param MappingDriver $driver
     * @return void
     */
    public function setOriginalDriver(MappingDriver $driver);

    /**
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @return void
     */
    public function readExtendedMetadata(ClassMetadata $classMetadata, Configuration $configuration);

}
