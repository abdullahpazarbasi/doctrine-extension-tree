<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Drivers;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use DoctrineExtensionTree\Contracts\MetadataMappingDriverInterface;
use DoctrineExtensionTree\Metadata\Configuration;

/**
 * Class DriverChain
 */
final class DriverChain implements MetadataMappingDriverInterface
{

    /**
     * @var MetadataMappingDriverInterface|null
     */
    private $defaultDriver;

    /**
     * @var MetadataMappingDriverInterface[]|array
     */
    private $drivers = [];

    /**
     * @param MetadataMappingDriverInterface $nestedDriver
     * @param string $namespace
     */
    public function addDriver(MetadataMappingDriverInterface $nestedDriver, $namespace)
    {
        $this->drivers[$namespace] = $nestedDriver;
    }

    /**
     * @return MetadataMappingDriverInterface[]|array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * @return MetadataMappingDriverInterface|null
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    /**
     * @param MetadataMappingDriverInterface $driver
     */
    public function setDefaultDriver(MetadataMappingDriverInterface $driver)
    {
        $this->defaultDriver = $driver;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     */
    public function readExtendedMetadata(ClassMetadata $classMetadata, Configuration $configuration)
    {
        foreach ($this->drivers as $namespace => $driver) {
            if (0 === strpos($classMetadata->name, $namespace)) {
                $driver->readExtendedMetadata($classMetadata, $configuration);
                return;
            }
        }
        if (null !== $this->defaultDriver) {
            $this->defaultDriver->readExtendedMetadata($classMetadata, $configuration);
            return;
        }
    }

    /**
     * @param MappingDriver $driver
     * @return void
     */
    public function setOriginalDriver(MappingDriver $driver)
    {
        //do nothing
    }

}
