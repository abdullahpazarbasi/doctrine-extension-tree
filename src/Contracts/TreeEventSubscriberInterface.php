<?php

namespace DoctrineExtensionTree\Contracts;

use Doctrine\Common\EventArgs;

/**
 * Interface TreeEventSubscriberInterface
 */
interface TreeEventSubscriberInterface
{

    /**
     * @param EventArgs $eventArguments
     * @return void
     */
    public function onFlush(EventArgs $eventArguments);

    /**
     * @param EventArgs $eventArguments
     * @return void
     */
    public function postPersist(EventArgs $eventArguments);

}
