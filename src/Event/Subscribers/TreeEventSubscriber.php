<?php

namespace DoctrineExtensionTree\Event\Subscribers;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\DefaultFileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Version as DoctrineCommonVersion;
use Doctrine\ORM\UnitOfWork;
use DoctrineExtensionTree\Contracts\AnnotationDriverInterface;
use DoctrineExtensionTree\Contracts\MetadataMappingDriverInterface;
use DoctrineExtensionTree\Contracts\TreeEventSubscriberInterface;
use DoctrineExtensionTree\Exceptions\InvalidConfigurationException;
use DoctrineExtensionTree\Metadata\Configuration;
use DoctrineExtensionTree\Metadata\Mapping\Drivers\AbstractDriver;
use DoctrineExtensionTree\Metadata\Mapping\Drivers\DriverChain;
use DoctrineExtensionTree\Metadata\Mapping\Drivers\AbstractFileDriver;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class TreeEventSubscriber
 */
class TreeEventSubscriber implements EventSubscriber, TreeEventSubscriberInterface
{

    const PATH_SEPARATOR = '/';

    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @var array
     */
    protected $classesUsedOnFlush;

    /**
     * Array of objects which were scheduled for path processes
     *
     * @var array
     */
    protected $scheduledForPathProcess;

    /**
     * Array of objects which were scheduled for path process.
     * This time, this array contains the objects with their ID already set
     *
     * @var array
     */
    protected $scheduledForPathProcessWithIdSet;

    /**
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->classesUsedOnFlush = [];
        $this->scheduledForPathProcess = [];
        $this->scheduledForPathProcessWithIdSet = [];
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'postPersist',
        ];
    }

    /**
     * @param EventArgs $eventArguments
     * @return void
     */
    public function onFlush(EventArgs $eventArguments)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $eventArguments->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $object) {
            $classMetadata = $this->getClassMetadata($objectManager, $object);
            $class = $classMetadata->getName();
            $configuration = $this->getConfiguration($objectManager, $class);
            if (empty($configuration)) {
                continue;
            }
            $this->classesUsedOnFlush[$class] = null;
            $objectManager->initializeObject($object);
            $this->processScheduledInsertion($objectManager, $configuration, $object);
            $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $object);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $object) {
            $classMetadata = $this->getClassMetadata($objectManager, $object);
            $class = $classMetadata->getName();
            $configuration = $this->getConfiguration($objectManager, $class);
            if (empty($configuration)) {
                continue;
            }
            $this->classesUsedOnFlush[$class] = null;
            $objectManager->initializeObject($object);
            $this->processScheduledUpdate($objectManager, $configuration, $object);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $object) {
            $classMetadata = $this->getClassMetadata($objectManager, $object);
            $class = $classMetadata->getName();
            $configuration = $this->getConfiguration($objectManager, $class);
            if (empty($configuration)) {
                continue;
            }
            $this->classesUsedOnFlush[$class] = null;
            $objectManager->initializeObject($object);
            $this->removeNode($objectManager, $classMetadata, $configuration, $object);
        }
    }

    /**
     * @param EventArgs $eventArguments
     * @return void
     */
    public function postPersist(EventArgs $eventArguments)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = $eventArguments->getObjectManager();
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $eventArguments->getClassMetadata();
        $configuration = $this->getConfiguration($objectManager, $classMetadata->getName());
        if (empty($configuration)) {
            return;
        }
        $node = $eventArguments->getObject();
        $oid = spl_object_hash($node);
        if ($this->scheduledForPathProcess && array_key_exists($oid, $this->scheduledForPathProcess)) {
            $this->scheduledForPathProcessWithIdSet[$oid] = $node;
            unset($this->scheduledForPathProcess[$oid]);
            if (empty($this->scheduledForPathProcess)) {
                foreach ($this->scheduledForPathProcessWithIdSet as $oid => $node) {
                    $this->updateNode($objectManager, $configuration, $node);
                    unset($this->scheduledForPathProcessWithIdSet[$oid]);
                }
            }
        }
    }

    /**
     * @param ObjectManager $objectManager
     * @param string $class
     * @return Configuration
     */
    public function getConfiguration(ObjectManager $objectManager, $class)
    {
        $configuration = new Configuration();
        $classMetadataFactory = $objectManager->getMetadataFactory();
        $cacheDriver = $classMetadataFactory->getCacheDriver();
        $cacheId = $this->getCacheId($class);
        if ($cacheDriver instanceof Cache) {
            if (false !== ($cachedAsArray = $cacheDriver->fetch($cacheId))) {
                return $configuration->fromArray($cachedAsArray);
            }
        }
        $classMetadata = $classMetadataFactory->getMetadataFor($class);
        if ($classMetadata->isMappedSuperclass) {
            return $configuration;
        }
        if (null !== $classMetadata->getReflectionClass()) {
            $coreClassMetadataMappingDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
            $extensionClassMetadataMappingDriver = $this->getExtensionClassMetadataMappingDriver(
                $coreClassMetadataMappingDriver
            );
            $parentClasses = class_parents($classMetadata->getName());
            $parentClasses = array_reverse($parentClasses); // from super class to sub class in hierarchy
            foreach ($parentClasses as $parentClass) {
                if ($classMetadataFactory->hasMetadataFor($parentClass)) {
                    try {
                        $extensionClassMetadataMappingDriver->readExtendedMetadata(
                            $objectManager->getClassMetadata($parentClass),
                            $configuration
                        );
                    } catch (InvalidConfigurationException $e) {
                        continue;
                    }
                }
            }
            $extensionClassMetadataMappingDriver->readExtendedMetadata($classMetadata, $configuration);
        }
        if ($cacheDriver instanceof Cache) {
            $cacheDriver->save($cacheId, $configuration->toArray(), null);
        }
        return $configuration;
    }

    /**
     * @param ObjectManager $objectManager
     * @param Configuration $configuration
     * @param object $node
     * @return void
     */
    protected function processScheduledUpdate(ObjectManager $objectManager, Configuration $configuration, $node)
    {
        $classMetadata = $this->getClassMetadata($objectManager, $node);
        $objectChangeSet = $objectManager->getUnitOfWork()->getEntityChangeSet($node);

        if (
            isset($objectChangeSet[$configuration->getParentNodeHoldingPropertyName()])
            || isset($objectChangeSet[$configuration->getPathNodePrefixHoldingPropertyName()])
            || isset($objectChangeSet[$configuration->getPathNodeSuffixHoldingPropertyName()])
        ) {
            $originalNodePathSlices = $this->resolveOriginalPathSlices(
                $configuration->getReadOnlyPathSliceHoldingPropertyNameList(),
                $classMetadata,
                $objectManager->getUnitOfWork(),
                $node
            );
            $this->updateNode($objectManager, $configuration, $node);
            $this->updateChildren($objectManager, $classMetadata, $configuration, $originalNodePathSlices);
        }
    }

    /**
     * @param array $pathSliceHoldingPropertyNameList
     * @param ClassMetadata $objectClassMetadata
     * @param UnitOfWork $unitOfWork
     * @param object $node
     * @return array|string[]
     */
    protected function resolveOriginalPathSlices(
        array $pathSliceHoldingPropertyNameList,
        ClassMetadata $objectClassMetadata,
        UnitOfWork $unitOfWork,
        $node
    ) {
        $originalPathSlices = [];
        $objectChangeSet = $unitOfWork->getEntityChangeSet($node);
        foreach ($pathSliceHoldingPropertyNameList as $propertyName) {
            if (isset($objectChangeSet[$propertyName])) {
                $originalPathSlices[$propertyName] = $objectChangeSet[$propertyName][0];
            } else {
                $propertyReflection = $objectClassMetadata->getReflectionProperty($propertyName);
                $propertyReflection->setAccessible(true);
                $originalPathSlices[$propertyName] = $propertyReflection->getValue($node);
            }
        }
        return $originalPathSlices;
    }

    /**
     * @param ObjectManager $objectManager
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @param array|string[] $nodePathSlices
     * @return void
     */
    protected function updateChildren(
        ObjectManager $objectManager,
        ClassMetadata $classMetadata,
        Configuration $configuration,
        array $nodePathSlices
    ) {
        $qb = $objectManager->createQueryBuilder();
        $qb
            ->select('e')
            ->from($classMetadata->getName(), 'e') // useObjectClass
        ;
        $entityAlias = 'e';
        $firstCycle = true;
        $lastPropertyName = null;
        $lastNodePathSlice = null;
        foreach ($nodePathSlices as $propertyName => $nodePathSlice) {
            if ($firstCycle) {
                $firstCycle = false;
                $lastPropertyName = $propertyName;
                $lastNodePathSlice = $nodePathSlice;
                continue;
            }
            $lastQualifiedPropertyName = $entityAlias . '.' . $lastPropertyName;
            if (null === $nodePathSlice) {
                if (null === $lastNodePathSlice) {
                    $lastPropertyName = $propertyName;
                    $lastNodePathSlice = $nodePathSlice;
                    continue;
                }
                $qb
                    ->andWhere($qb->expr()->like(
                        $lastQualifiedPropertyName, $qb->expr()->literal(addcslashes($lastNodePathSlice, '%') . '%')
                    ))
                    ->andWhere($lastQualifiedPropertyName . ' != :' . $lastPropertyName)
                    ->setParameter($lastPropertyName, $lastNodePathSlice)
                ;
            } else {
                $qb
                    ->andWhere($lastQualifiedPropertyName . ' = :' . $lastPropertyName)
                    ->setParameter($lastPropertyName, $lastNodePathSlice)
                ;
            }
            $qb->orderBy($lastQualifiedPropertyName, 'asc');
            $lastPropertyName = $propertyName;
            $lastNodePathSlice = $nodePathSlice;
        }
        if (null !== $lastNodePathSlice) {
            $lastQualifiedPropertyName = $entityAlias . '.' . $lastPropertyName;
            $qb
                ->andWhere($lastQualifiedPropertyName . ' = :' . $lastPropertyName)
                ->setParameter($lastPropertyName, $lastNodePathSlice)
            ;
            $qb->orderBy($lastQualifiedPropertyName, 'asc');
        }
        $children = $qb->getQuery()->execute();
        foreach ($children as $child) {
            $this->updateNode($objectManager, $configuration, $child);
        }
    }

    /**
     * @param ObjectManager $objectManager
     * @param Configuration $configuration
     * @param object $node
     * @return void
     */
    protected function processScheduledInsertion(ObjectManager $objectManager, Configuration $configuration, $node)
    {
        $this->scheduledForPathProcess[spl_object_hash($node)] = $node;
    }

    /**
     * @param ObjectManager $objectManager
     * @param Configuration $configuration
     * @param object $node
     * @return void
     */
    protected function updateNode(ObjectManager $objectManager, Configuration $configuration, $node)
    {
        $nodeOid = spl_object_hash($node);
        $nodeClassMetadata = $this->getClassMetadata($objectManager, $node);
        $unitOfWork = $objectManager->getUnitOfWork();

        $pathNodePrefix = '';
        $pathNodePrefixSeparator = '';
        $pathNodePrefixHoldingPropertyName = $configuration->getPathNodePrefixHoldingPropertyName();
        if (null !== $pathNodePrefixHoldingPropertyName) {
            $pathNodePrefixHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
                $pathNodePrefixHoldingPropertyName
            );
            $pathNodePrefixHoldingPropertyReflection->setAccessible(true);

            $pathNodePrefix = (string)$pathNodePrefixHoldingPropertyReflection->getValue($node);
            if (false !== strpos($pathNodePrefix, self::PATH_SEPARATOR)) {
                throw new RuntimeException(sprintf(
                    'Property "%1$s" can not contain path node separator ("%2$s")',
                    $pathNodePrefixHoldingPropertyName,
                    self::PATH_SEPARATOR
                ));
            }

            $pathNodePrefixSeparator = $configuration->getPathNodePrefixSeparator();
            if (false !== strpos($pathNodePrefix, $pathNodePrefixSeparator)) {
                throw new RuntimeException(sprintf(
                    'Property "%1$s" can not contain path node prefix separator ("%2$s")',
                    $pathNodePrefixHoldingPropertyName,
                    $pathNodePrefixSeparator
                ));
            }
        }

        $pathNodeSuffix = '';
        $pathNodeSuffixSeparator = '';
        $pathNodeSuffixHoldingPropertyName = $configuration->getPathNodeSuffixHoldingPropertyName();
        if (null !== $pathNodeSuffixHoldingPropertyName) {
            $pathNodeSuffixHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
                $pathNodeSuffixHoldingPropertyName
            );
            $pathNodeSuffixHoldingPropertyReflection->setAccessible(true);

            $pathNodeSuffix = (string)$pathNodeSuffixHoldingPropertyReflection->getValue($node);
            if (false !== strpos($pathNodeSuffix, self::PATH_SEPARATOR)) {
                throw new RuntimeException(sprintf(
                    'Property "%1$s" can not contain path node separator ("%2$s")',
                    $pathNodeSuffixHoldingPropertyName,
                    self::PATH_SEPARATOR
                ));
            }

            $pathNodeSuffixSeparator = $configuration->getPathNodeSuffixSeparator();
            if (false !== strpos($pathNodeSuffix, $pathNodeSuffixSeparator)) {
                throw new RuntimeException(sprintf(
                    'Property "%1$s" can not contain path node suffix separator ("%2$s")',
                    $pathNodeSuffixHoldingPropertyName,
                    $pathNodeSuffixSeparator
                ));
            }
        }

        $nodeIdentifierGettingMethodName = $configuration->getNodeIdentifierGettingMethodName();
        if (null !== $nodeIdentifierGettingMethodName) {
            $identifier = $node->$nodeIdentifierGettingMethodName();
        } elseif (method_exists($nodeClassMetadata, 'getIdentifierValue')) {
            $identifier = $nodeClassMetadata->getIdentifierValue($node);
        } else {
            $idHoldingPropertyName = $nodeClassMetadata->getSingleIdentifierFieldName();
            if ($idHoldingPropertyName === $pathNodePrefixHoldingPropertyName) {
                throw new InvalidArgumentException(sprintf(
                    'The identifier holding property ("%1$s") can not be the path node prefix holding property at the same time',
                    $idHoldingPropertyName
                ));
            }
            if ($idHoldingPropertyName === $pathNodeSuffixHoldingPropertyName) {
                throw new InvalidArgumentException(sprintf(
                    'The identifier holding property ("%1$s") can not be the path node suffix holding property at the same time',
                    $idHoldingPropertyName
                ));
            }

            $idHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty($idHoldingPropertyName);
            $idHoldingPropertyReflection->setAccessible(true);

            $identifier = $idHoldingPropertyReflection->getValue($node);
        }
        if (false !== strpos($identifier, self::PATH_SEPARATOR)) {
            throw new RuntimeException(sprintf(
                'Id value can not contain path node separator ("%1$s")',
                self::PATH_SEPARATOR
            ));
        }
        if (strlen($pathNodePrefixSeparator) > 0 && false !== strpos($identifier, $pathNodePrefixSeparator)) {
            throw new RuntimeException(sprintf(
                'Id value can not contain path node prefix separator ("%1$s")',
                $pathNodePrefixSeparator
            ));
        }
        if (strlen($pathNodeSuffixSeparator) > 0 && false !== strpos($identifier, $pathNodeSuffixSeparator)) {
            throw new RuntimeException(sprintf(
                'Id value can not contain path node suffix separator ("%1$s")',
                $pathNodeSuffixSeparator
            ));
        }

        $currentPathNode = $pathNodePrefix . $pathNodePrefixSeparator . $identifier . $pathNodeSuffixSeparator . $pathNodeSuffix;

        $pathSliceHoldingPropertyNameList = $configuration->getReadOnlyPathSliceHoldingPropertyNameList();

        $parentNodeHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
            $configuration->getParentNodeHoldingPropertyName()
        );
        $parentNodeHoldingPropertyReflection->setAccessible(true);

        $parentNodePath = '';
        $parentNode = $parentNodeHoldingPropertyReflection->getValue($node);
        if (null !== $parentNode) {
            // Ensure parent has been initialized in the case where it's a proxy
            $objectManager->initializeObject($parentNode);

            $holderPathWillBeChanged = false;
            if ($unitOfWork->isScheduledForUpdate($parentNode)) {
                $objectChangeSet = $unitOfWork->getEntityChangeSet($parentNode);
                if (!empty($objectChangeSet)) {
                    foreach ($pathSliceHoldingPropertyNameList as $pathSliceHoldingPropertyName) {
                        if (isset($objectChangeSet[$pathSliceHoldingPropertyName])) {
                            $holderPathWillBeChanged = true;
                        }
                    }
                    if (null !== $pathNodePrefixHoldingPropertyName) {
                        if (isset($objectChangeSet[$pathNodePrefixHoldingPropertyName])) {
                            $holderPathWillBeChanged = true;
                        }
                    }
                    if (null !== $pathNodeSuffixHoldingPropertyName) {
                        if (isset($objectChangeSet[$pathNodeSuffixHoldingPropertyName])) {
                            $holderPathWillBeChanged = true;
                        }
                    }
                }
            }
            $parentNodePath = $this->buildNodePath($objectManager, $configuration, $parentNode);
            if (empty($parentNodePath)) {
                $holderPathWillBeChanged = true;
            }
            if ($holderPathWillBeChanged) {
                // Recursive changes downwards
                $this->updateNode($objectManager, $configuration, $parentNode);
            }

            $parentNodePath = $this->buildNodePath($objectManager, $configuration, $parentNode);
        }

        $nodePath = $parentNodePath . self::PATH_SEPARATOR . $currentPathNode;

        $changes = [];

        $pathHoldingPropertyReflection->setValue($node, $nodePath);
        $changes[$configuration[AbstractDriver::PATH_HOLDING_PROPERTY]] = [null, $nodePath];

        $readOnlyPathHashHoldingPropertyName = $configuration->getReadOnlyPathHashHoldingPropertyName();
        $nodePathHash = null;
        if (null !== $readOnlyPathHashHoldingPropertyName) {
            $pathHashHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
                $readOnlyPathHashHoldingPropertyName
            );
            $pathHashHoldingPropertyReflection->setAccessible(true);

            $nodePathHash = md5($nodePath);
            $pathHashHoldingPropertyReflection->setValue($node, $nodePathHash);
            $changes[$readOnlyPathHashHoldingPropertyName] = [null, $nodePathHash];
        }

        $readOnlyRootNodeHoldingPropertyName = $configuration->getReadOnlyRootNodeHoldingPropertyName();
        if (null !== $readOnlyRootNodeHoldingPropertyName) {
            $rootNode = null;
            $firstPathNode = null;
            $remaining = null;
            list($firstPathNode, $remaining) = explode(self::PATH_SEPARATOR, substr($nodePath, 1), 2);
            $rootNodePathNodePrefix = null;
            $rootNodeIdentifier = null;
            list($rootNodePathNodePrefix, $rootNodeIdentifier) = explode('-', $firstPathNode, 2);
            if ($nodeClassMetadata->hasAssociation($readOnlyRootNodeHoldingPropertyName)) {
                $rootClass = $nodeClassMetadata->getAssociationTargetClass($readOnlyRootNodeHoldingPropertyName);
                $rootNode = $objectManager->getReference($rootClass, $rootNodeIdentifier);
            } else {
                $rootNode = $rootNodeIdentifier;
            }

            $rootNodeHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
                $readOnlyRootNodeHoldingPropertyName
            );
            $rootNodeHoldingPropertyReflection->setAccessible(true);

            $rootNodeHoldingPropertyReflection->setValue($node, $rootNode);
            $changes[$readOnlyRootNodeHoldingPropertyName] = [null, $rootNode];
        }

        $readOnlyBranchDepthHoldingPropertyName = $configuration->getReadOnlyBranchDepthHoldingPropertyName();
        if (null !== $readOnlyBranchDepthHoldingPropertyName) {
            $readOnlyBranchDepthHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty(
                $readOnlyBranchDepthHoldingPropertyName
            );
            $readOnlyBranchDepthHoldingPropertyReflection->setAccessible(true);

            $depth = substr_count($nodePath, self::PATH_SEPARATOR);
            $readOnlyBranchDepthHoldingPropertyReflection->setValue($node, $depth);
            $changes[$readOnlyBranchDepthHoldingPropertyName] = [null, $depth];
        }

        $nodePathSlices = $this->splitNodePath($nodeClassMetadata, $configuration, $nodePath);
        foreach ($nodePathSlices as $pathSliceHoldingPropertyName => $nodePathSlice) {
            $unitOfWork->setOriginalEntityProperty(
                $nodeOid,
                $pathSliceHoldingPropertyName,
                $nodePathSlice
            );
        }
        $unitOfWork->scheduleExtraUpdate($node, $changes);

        if (null !== $readOnlyPathHashHoldingPropertyName) {
            $unitOfWork->setOriginalEntityProperty($nodeOid, $readOnlyPathHashHoldingPropertyName, $nodePathHash);
        }
    }

    /**
     * @param ObjectManager $objectManager
     * @param Configuration $configuration
     * @param object $node
     * @return array
     */
    protected function getNodePathSlices(ObjectManager $objectManager, Configuration $configuration, $node)
    {
        $slices = [];
        $nodeClassMetadata = $this->getClassMetadata($objectManager, $node);
        $pathSliceHoldingPropertyNameList = $configuration->getReadOnlyPathSliceHoldingPropertyNameList();
        foreach ($pathSliceHoldingPropertyNameList as $pathSliceHoldingPropertyName) {
            $pathHoldingPropertyReflection = $nodeClassMetadata->getReflectionProperty($pathSliceHoldingPropertyName);
            $pathHoldingPropertyReflection->setAccessible(true);

            $slices[$pathSliceHoldingPropertyName] = $pathHoldingPropertyReflection->getValue($node);
        }
        return $slices;
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @param string $nodePath
     * @return array
     */
    protected function splitNodePath(ClassMetadata $classMetadata, Configuration $configuration, $nodePath)
    {
        $slices = [];
        $pathSliceLength = $configuration->getPathSliceLength();
        $pathSliceHoldingPropertyNameList = $configuration->getReadOnlyPathSliceHoldingPropertyNameList();
        foreach ($pathSliceHoldingPropertyNameList as $pathSliceHoldingPropertyName) {
            $currentSlice = ''; // todo: complete this
            $slices[$pathSliceHoldingPropertyName] = $currentSlice;
        }
        return $slices;
    }

    /**
     * @param ObjectManager $objectManager
     * @param Configuration $configuration
     * @param object $node
     * @return string
     */
    protected function buildNodePath(ObjectManager $objectManager, Configuration $configuration, $node)
    {
        return implode(self::PATH_SEPARATOR, $this->getNodePathSlices($objectManager, $configuration, $node));
    }

    /**
     * @param ObjectManager $objectManager
     * @param ClassMetadata $classMetadata
     * @param Configuration $configuration
     * @param object $node
     * @return void
     */
    protected function removeNode(
        ObjectManager $objectManager,
        ClassMetadata $classMetadata,
        Configuration $configuration,
        $node
    ) {
        $unitOfWork = $objectManager->getUnitOfWork();

        $nodePathSlices = [];
        $pathSliceHoldingPropertyNameList = $configuration->getReadOnlyPathSliceHoldingPropertyNameList();
        foreach ($pathSliceHoldingPropertyNameList as $propertyName) {
            $propertyReflection = $classMetadata->getReflectionProperty($propertyName);
            $propertyReflection->setAccessible(true);
            $nodePathSlices[$propertyName] = $propertyReflection->getValue($node);
        }

        $qb = $objectManager->createQueryBuilder();
        $qb
            ->select('e')
            ->from($classMetadata->getName(), 'e') // useObjectClass
        ;
        $entityAlias = 'e';
        $firstCycle = true;
        $lastPropertyName = null;
        $lastNodePathSlice = null;
        foreach ($nodePathSlices as $propertyName => $nodePathSlice) {
            if ($firstCycle) {
                $firstCycle = false;
                $lastPropertyName = $propertyName;
                $lastNodePathSlice = $nodePathSlice;
                continue;
            }
            $lastQualifiedPropertyName = $entityAlias . '.' . $lastPropertyName;
            if (null === $nodePathSlice) {
                if (null === $lastNodePathSlice) {
                    $lastPropertyName = $propertyName;
                    $lastNodePathSlice = $nodePathSlice;
                    continue;
                }
                $qb
                    ->where($qb->expr()->like(
                        $lastQualifiedPropertyName, $qb->expr()->literal(addcslashes($lastNodePathSlice, '%') . '%')
                    ))
                ;
            } else {
                $qb
                    ->andWhere($lastQualifiedPropertyName . ' = :' . $lastPropertyName)
                    ->setParameter($lastPropertyName, $lastNodePathSlice)
                ;
            }
            $lastPropertyName = $propertyName;
            $lastNodePathSlice = $nodePathSlice;
        }
        if (null !== $lastNodePathSlice) {
            $lastQualifiedPropertyName = $entityAlias . '.' . $lastPropertyName;
            $qb
                ->andWhere($lastQualifiedPropertyName . ' = :' . $lastPropertyName)
                ->setParameter($lastPropertyName, $lastNodePathSlice)
            ;
        }

        $readOnlyBranchDepthHoldingPropertyName = $configuration->getReadOnlyBranchDepthHoldingPropertyName();
        if (null !== $readOnlyBranchDepthHoldingPropertyName) {
            $readOnlyBranchDepthHoldingPropertyReflection = $classMetadata->getReflectionProperty(
                $readOnlyBranchDepthHoldingPropertyName
            );
            $readOnlyBranchDepthHoldingPropertyReflection->setAccessible(true);

            $depth = $readOnlyBranchDepthHoldingPropertyReflection->getValue($node);
            if (!empty($depth)) {
                $qb->andWhere($qb->expr()->gt(
                    'e.' . $readOnlyBranchDepthHoldingPropertyName,
                    $qb->expr()->literal($depth)
                ));
            }
        }

        $results = $qb->getQuery()->execute();
        foreach ($results as $node) {
            $unitOfWork->scheduleForDelete($node);
        }
    }

    /**
     * @param string $class
     * @return string
     */
    protected function getCacheId($class)
    {
        return sprintf('%1$s$TreeClassMetadata', $class);
    }

    /**
     * @param MappingDriver $coreClassMetadataMappingDriver
     * @return MetadataMappingDriverInterface
     */
    protected function getExtensionClassMetadataMappingDriver(MappingDriver $coreClassMetadataMappingDriver)
    {
        $extensionClassMetadataMappingDriver = null;
        $coreClassMetadataMappingDriverClassName = get_class($coreClassMetadataMappingDriver);
        $coreClassMetadataMappingDriverName = $this->extractClassBaseName($coreClassMetadataMappingDriverClassName);
        if (
            $coreClassMetadataMappingDriver instanceof MappingDriverChain
            || 'DriverChain' === $coreClassMetadataMappingDriverName
        ) {
            $extensionClassMetadataMappingDriver = new DriverChain();
            foreach ($coreClassMetadataMappingDriver->getDrivers() as $namespace => $currentDriver) {
                $extensionClassMetadataMappingDriver->addDriver(
                    $this->getExtensionClassMetadataMappingDriver($currentDriver),
                    $namespace
                );
            }
            if (
                version_compare(DoctrineCommonVersion::VERSION, '2.3.0', '>=')
                && null !== ($defaultDriver = $coreClassMetadataMappingDriver->getDefaultDriver())
            ) {
                $extensionClassMetadataMappingDriver->setDefaultDriver(
                    $this->getExtensionClassMetadataMappingDriver($defaultDriver)
                );
            }
            return $extensionClassMetadataMappingDriver;
        }
        $isSimplified = false;
        $extensionClassMetadataMappingDriverName = $this->convertCoreDriverNameToExtensionDriverName(
            $coreClassMetadataMappingDriverName,
            $isSimplified
        );
        $extensionClassMetadataMappingDriverClassName = str_replace(
            'AbstractDriver',
            $extensionClassMetadataMappingDriverName,
            AbstractDriver::class
        );
        if (!class_exists($extensionClassMetadataMappingDriverClassName)) {
            throw new RuntimeException(sprintf(
                'Extension driver "%1$s" could not be found',
                $extensionClassMetadataMappingDriverClassName
            ));
        }
        /** @var MetadataMappingDriverInterface $extensionClassMetadataMappingDriver */
        $extensionClassMetadataMappingDriver = new $extensionClassMetadataMappingDriverClassName();
        $extensionClassMetadataMappingDriver->setOriginalDriver($coreClassMetadataMappingDriver);
        if ($extensionClassMetadataMappingDriver instanceof AbstractFileDriver) {
            if ($coreClassMetadataMappingDriver instanceof MappingDriver) {
                $extensionClassMetadataMappingDriver->setLocator($coreClassMetadataMappingDriver->getLocator());
            } elseif ($isSimplified) {
                $extensionClassMetadataMappingDriver->setLocator(
                    new SymfonyFileLocator(
                        $coreClassMetadataMappingDriver->getNamespacePrefixes(),
                        $coreClassMetadataMappingDriver->getFileExtension()
                    )
                );
            } else {
                $extensionClassMetadataMappingDriver->setLocator(
                    new DefaultFileLocator(
                        $coreClassMetadataMappingDriver->getPaths(),
                        $coreClassMetadataMappingDriver->getFileExtension()
                    )
                );
            }
        }
        if ($extensionClassMetadataMappingDriver instanceof AnnotationDriverInterface) {
            $extensionClassMetadataMappingDriver->setAnnotationReader($this->annotationReader);
        }
        return $extensionClassMetadataMappingDriver;
    }

    /**
     * @param string $qualifiedClassName
     * @return string
     */
    protected function extractClassBaseName($qualifiedClassName)
    {
        return (string)substr($qualifiedClassName, strrpos($qualifiedClassName, '\\') + 1);
    }

    /**
     * @param string $coreDriverName
     * @param bool $isSimplified
     * @return string
     */
    protected function convertCoreDriverNameToExtensionDriverName($coreDriverName, &$isSimplified)
    {
        $isSimplified = false;
        $extensionDriverName = $coreDriverName;
        if ('Simplified' === substr($extensionDriverName, 0, 10)) {
            $extensionDriverName = substr($extensionDriverName, 10);
            $isSimplified = true;
        }
        return $extensionDriverName;
    }

    /**
     * @param EventArgs $eventArguments
     * @param object $object
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @todo remove this if not needed
     */
    protected function setFieldValue(EventArgs $eventArguments, $object, $propertyName, $oldValue, $newValue)
    {
        $objectManager = $eventArguments->getObjectManager();
        $classMetadata = $this->getClassMetadata($objectManager, $object);
        $unitOfWork = $objectManager->getUnitOfWork();
        $classMetadata->getReflectionProperty($propertyName)->setValue($object, $newValue);
        $unitOfWork->propertyChanged($object, $propertyName, $oldValue, $newValue);
        $unitOfWork->recomputeSingleEntityChangeSet($classMetadata, $object);
    }

    /**
     * @param ObjectManager $objectManager
     * @param object $object
     * @return ClassMetadata
     */
    protected function getClassMetadata(ObjectManager $objectManager, $object)
    {
        return $objectManager->getClassMetadata(ClassUtils::getClass($object));
    }

}
