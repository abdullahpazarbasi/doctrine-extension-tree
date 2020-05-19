<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Drivers;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Common\Persistence\Mapping\Driver\FileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\Mapping\Driver\AbstractFileDriver as CoreAbstractFileDriver;

/**
 * Class AbstractFileDriver
 */
abstract class AbstractFileDriver extends AbstractDriver
{

    /**
     * @var FileLocator
     */
    protected $locator;

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var string
     */
    protected $extension;

    /**
     * @var object
     */
    protected $originalDriver = null;

    /**
     * @param FileLocator $locator
     * @return void
     */
    public function setLocator(FileLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param array $paths
     * @return void
     */
    public function setPaths($paths)
    {
        $this->paths = (array)$paths;
    }

    /**
     * @param string $extension
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * @param MappingDriver $driver
     * @return void
     */
    public function setOriginalDriver(MappingDriver $driver)
    {
        $this->originalDriver = $driver;
    }

    /**
     * @param string $file
     * @return array
     */
    abstract protected function loadMappingFile($file);

    /**
     * @param string $className
     * @return null|array|object
     * @throws MappingException
     */
    protected function getMapping($className)
    {
        $mapping = null;
        if (null !== $this->originalDriver) {
            if (
                $this->originalDriver instanceof FileDriver
                || $this->originalDriver instanceof CoreAbstractFileDriver
            ) {
                $mapping = $this->originalDriver->getElement($className);
            }
        }
        if (null === $mapping) {
            $yaml = $this->loadMappingFile($this->locator->findMappingFile($className));
            $mapping = $yaml[$className];
        }
        return $mapping;
    }

}
