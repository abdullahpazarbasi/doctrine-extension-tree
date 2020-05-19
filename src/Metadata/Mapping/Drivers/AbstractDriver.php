<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Drivers;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use DoctrineExtensionTree\Contracts\MetadataMappingDriverInterface;

/**
 * Class AbstractDriver
 */
abstract class AbstractDriver implements MetadataMappingDriverInterface
{

    /**
     * @param ClassMetadata $classMetadata
     * @param string $className
     * @return string
     */
    protected function getRelatedClassName(ClassMetadata $classMetadata, $className)
    {
        if (class_exists($className) || interface_exists($className)) {
            return $className;
        }
        $classShortName = $className;
        $reflectionClass = $classMetadata->getReflectionClass();
        $classNamespaceName = $reflectionClass->getNamespaceName();
        $qualifiedClassName = $classNamespaceName . '\\' . $classShortName;
        return class_exists($qualifiedClassName) ? $qualifiedClassName : '';
    }

}
