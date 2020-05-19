<?php

namespace DoctrineExtensionTree\Test;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\ToolsException;
use DoctrineExtensionTree\Event\Subscribers\TreeEventSubscriber;
use DoctrineExtensionTree\Test\UuidForDoctrine\Doctrine\DBAL\Types\UuidType;
use PHPUnit\Framework\TestCase;

/**
 * Class BasicTestCase
 */
abstract class BasicTestCase extends TestCase
{

    const METADATA_DRIVER_DOCBLOCK_ANNOTATION = 'ANNOTATION';
    const METADATA_DRIVER_XML = 'XML';
    const METADATA_DRIVER_YAML = 'YAML';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TreeEventSubscriber
     */
    protected $treeEventSubscriber;

    /**
     * @return void
     */
    protected function setUp()
    {
        ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE ^ E_DEPRECATED);
        AnnotationRegistry::registerLoader('class_exists');
        parent::setUp();
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return string
     */
    final protected function getProjectDirectory()
    {
        return dirname(__DIR__);
    }

    /**
     * @return string
     */
    protected function getMatadataDriverKey()
    {
        return self::METADATA_DRIVER_DOCBLOCK_ANNOTATION;
    }

    /**
     * @return array|string[]
     */
    abstract protected function getMappingPaths();

    /**
     * @return string
     */
    abstract protected function getProxyDirectoryPath();

    /**
     * @return bool
     */
    abstract protected function isDevModeEnabled();

    /**
     * @param bool $force
     * @return void
     * @throws ORMException
     * @throws DBALException
     */
    protected function createEntityManager($force = false)
    {
        if ($force || null === $this->entityManager) {
            $devModeEnabled = $this->isDevModeEnabled();
            $proxyDirectoryPath = $this->getProxyDirectoryPath();
            switch ($this->getMatadataDriverKey()) {
                case self::METADATA_DRIVER_YAML:
                    $config = Setup::createYAMLMetadataConfiguration(
                        $this->getMappingPaths(),
                        $devModeEnabled,
                        $proxyDirectoryPath,
                        null
                    );
                    break;
                case self::METADATA_DRIVER_XML:
                    $config = Setup::createXMLMetadataConfiguration(
                        $this->getMappingPaths(),
                        $devModeEnabled,
                        $proxyDirectoryPath,
                        null
                    );
                    break;
                default:
                    $config = Setup::createAnnotationMetadataConfiguration(
                        $this->getMappingPaths(),
                        $devModeEnabled,
                        $proxyDirectoryPath,
                        null,
                        false
                    );
            }
            $dbOpts = [
                'driver' => 'pdo_sqlite',
                'path' => $this->getProjectDirectory() . '/db.sqlite',
            ];
            Type::addType('uuid', UuidType::class);
            $eventManager = new EventManager();
            $this->entityManager = EntityManager::create($dbOpts, $config, $eventManager);
        }
    }

    /**
     * @return void
     */
    protected function addTreeEventSubscriberToEntityManager()
    {
        $annotationReader = new AnnotationReader();
        $this->treeEventSubscriber = new TreeEventSubscriber($annotationReader);
        $this->entityManager->getEventManager()->addEventSubscriber($this->treeEventSubscriber);
    }

    /**
     * @return void
     */
    protected function removeTreeEventSubscriberFromEntityManager()
    {
        $this->entityManager->getEventManager()->removeEventSubscriber($this->treeEventSubscriber);
    }

    /**
     * @return void
     * @throws ToolsException
     * @throws ORMException
     * @throws DBALException
     */
    protected function createSchema()
    {
        $this->createEntityManager();
        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->createSchema($allMetadata);
    }

    /**
     * @return void
     * @throws ORMException
     * @throws DBALException
     */
    protected function dropSchema()
    {
        $this->createEntityManager();
        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($allMetadata);
    }

    /**
     * @return void
     * @throws ORMException
     * @throws DBALException
     */
    protected function dropDatabase()
    {
        $this->createEntityManager();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();
    }

}
