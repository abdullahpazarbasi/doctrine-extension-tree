<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class Tree
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Tree extends Annotation
{

    /**
     * @var string|null
     */
    public $identifierGettingMethod;

}
