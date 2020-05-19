<?php

namespace DoctrineExtensionTree\Test;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use DoctrineExtensionTree\Test\Entities\NodeMappedWithAnnotation;

/**
 * Class InitTest
 */
class ConfigurationTest extends BasicTestCase
{

    /**
     * @return void
     * @throws ORMException
     * @throws DBALException
     */
    protected function setUp()
    {
        parent::setUp();
        $this->dropDatabase();
        $this->createSchema();
        $this->addTreeEventSubscriberToEntityManager();
    }

    /**
     * @return void
     * @throws ORMException
     * @throws DBALException
     */
    protected function tearDown()
    {
        parent::tearDown();
        //$this->dropDatabase();
        //$this->removeTreeEventSubscriberFromEntityManager();
    }

    /**
     * @return array|string[]
     */
    protected function getMappingPaths()
    {
        return [$this->getProjectDirectory() . '/tests/Entities'];
    }

    /**
     * @return string
     */
    protected function getProxyDirectoryPath()
    {
        return $this->getProjectDirectory() . '/tests/Entities/Proxies';
    }

    /**
     * @return bool
     */
    protected function isDevModeEnabled()
    {
        return false;
    }

    /**
     * @test
     * @return void
     */
    public function isAnnotationConfigurationValid()
    {
        $configuration = $this->treeEventSubscriber->getConfiguration(
            $this->entityManager,
            NodeMappedWithAnnotation::class
        );
        $this->assertEquals(
            [
                'locking' => false,
                'locking_lifetime' => 3,
                'identifier_getting_method' => 'getId',
                'parent_field' => 'parent',
            ],
            $configuration
        );
    }

}
