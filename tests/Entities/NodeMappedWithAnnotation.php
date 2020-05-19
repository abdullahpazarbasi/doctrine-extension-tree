<?php

namespace DoctrineExtensionTree\Test\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensionTree\Metadata\Mapping\Annotations as DET;

/**
 * Class NodeMappedWithAnnotation
 *
 * @ORM\Entity(repositoryClass="DoctrineExtensionTree\Test\EntityRepositories\NodeMappedWithAnnotationRepository")
 * @ORM\Table(name="test_node_mapped_with_annotation")
 * @DET\Tree(locking=false, lockingLifetime=3, identifierGettingMethod="getId")
 */
class NodeMappedWithAnnotation
{

    /**
     * @var string|null
     *
     * @ORM\Column(name="id", type="uuid", unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="DoctrineExtensionTree\Test\UuidForDoctrine\Doctrine\ORM\Id\UuidGenerator")
     */
    protected $id;

    /**
     * @var Node|null
     *
     * @ORM\ManyToOne(targetEntity="Node", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @DET\TreeParent()
     */
    protected $parent;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer")
     */
    protected $level;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Node", mappedBy="parent")
     */
    protected $children;

    /**
     * @param Node|null $parent
     * @param Collection $children
     */
    public function __construct(Node $parent = null, Collection $children = null)
    {
        $this->parent = $parent;
        $this->level = 0;
        $this->children = isset($children) ? $children : new ArrayCollection();
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Node|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

}
