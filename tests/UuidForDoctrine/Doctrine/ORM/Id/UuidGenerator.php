<?php

namespace DoctrineExtensionTree\Test\UuidForDoctrine\Doctrine\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UuidGenerator
 */
class UuidGenerator extends AbstractIdGenerator
{

    /**
     * Generate an identifier
     *
     * @param EntityManager $em
     * @param Entity $entity
     *
     * @return UuidInterface
     *
     * @throws Exception
     */
    public function generate(EntityManager $em, $entity)
    {
        return Uuid::uuid4();
    }

}
