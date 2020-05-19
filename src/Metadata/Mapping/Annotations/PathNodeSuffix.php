<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class PathNodeSuffix
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class PathNodeSuffix extends Annotation
{

    /**
     * @var string
     */
    public $separator = '_';

}
