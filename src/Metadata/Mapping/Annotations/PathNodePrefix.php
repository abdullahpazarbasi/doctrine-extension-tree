<?php

namespace DoctrineExtensionTree\Metadata\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class PathNodePrefix
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class PathNodePrefix extends Annotation
{

    /**
     * @var string
     */
    public $separator = '_';

}
