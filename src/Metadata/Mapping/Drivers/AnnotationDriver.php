<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Drivers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use DoctrineExtensionTree\Contracts\AnnotationDriverInterface;
use DoctrineExtensionTree\Metadata\Configuration;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\PathNodePrefix;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\PathNodeSuffix;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ReadOnlyBranchDepth;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ReadOnlyParentPathHash;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ReadOnlyPathHash;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ReadOnlyPathSlice;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ReadOnlyRootNode;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\SiblingOrderPosition;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\Tree;
use DoctrineExtensionTree\Metadata\Mapping\Annotations\ParentNode;
use ReflectionClass;
use ReflectionException;

/**
 * Class AnnotationDriver
 */
class AnnotationDriver extends AbstractDriver implements AnnotationDriverInterface
{

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var object|null
     */
    protected $originalDriver = null;

    /**
     * @param Reader $reader
     * @return void
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->reader = $reader;
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
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @return void
     * @throws ReflectionException
     */
    public function readExtendedMetadata(ClassMetadata $classMetadata, Configuration $configuration)
    {
        $classMetadataReflectionClass = $this->getClassMetadataReflectionClass($classMetadata);
        // Class Annotations
        /** @var Tree $annotation */
        if ($annotation = $this->reader->getClassAnnotation($classMetadataReflectionClass, Tree::class)) {
            $configuration->setNodeIdentifierGettingMethodName($annotation->identifierGettingMethod);
        }
        // Property Annotations
        foreach ($classMetadataReflectionClass->getProperties() as $propertyReflection) {
            $propertyName = $propertyReflection->getName();
            if (
                $classMetadata->isMappedSuperclass && !$propertyReflection->isPrivate()
                || $classMetadata->isInheritedField($propertyName)
                || isset($classMetadata->associationMappings[$propertyName]['inherited'])
            ) {
                continue;
            }
            $annotations = $this->reader->getPropertyAnnotations($propertyReflection);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof ParentNode) {
                    $configuration->setParentNodeHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof SiblingOrderPosition) {
                    $configuration->setSiblingOrderPositionHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof PathNodePrefix) {
                    $configuration->setPathNodePrefixHoldingPropertyName($propertyName);
                    $configuration->setPathNodePrefixSeparator($annotation->separator);
                }
                if ($annotation instanceof PathNodeSuffix) {
                    $configuration->setPathNodeSuffixHoldingPropertyName($propertyName);
                    $configuration->setPathNodeSuffixSeparator($annotation->separator);
                }
                if ($annotation instanceof ReadOnlyRootNode) {
                    $configuration->setReadOnlyRootNodeHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof ReadOnlyBranchDepth) {
                    $configuration->setReadOnlyBranchDepthHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof ReadOnlyPathSlice) {
                    $configuration->addReadOnlyPathSliceHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof ReadOnlyPathHash) {
                    $configuration->setReadOnlyPathHashHoldingPropertyName($propertyName);
                }
                if ($annotation instanceof ReadOnlyParentPathHash) {
                    $configuration->setReadOnlyParentPathHashHoldingPropertyName($propertyName);
                }
            }
        }
    }

    /**
     * @param ClassMetadata $classMetadata
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public function getClassMetadataReflectionClass(ClassMetadata $classMetadata)
    {
        $class = $classMetadata->getReflectionClass();
        if (!$class) {
            $class = new ReflectionClass($classMetadata->getName());
        }
        return $class;
    }

}
