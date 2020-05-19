<?php

namespace DoctrineExtensionTree\Contracts;

use Doctrine\Common\Annotations\Reader;

/**
 * Interface AnnotationDriverInterface
 */
interface AnnotationDriverInterface extends MetadataMappingDriverInterface
{

    /**
     * @param Reader $reader
     * @return void
     */
    public function setAnnotationReader(Reader $reader);

}
