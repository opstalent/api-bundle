<?php

namespace Opstalent\ApiBundle\Repository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @package Opstalent\ApiBundle
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
interface RepositoryInterface
{
    /**
     * @return string
     */
    public function getEntityName() : string;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $dispatcher);
}
