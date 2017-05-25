<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ControllerRepositoryResultEvent extends EventAware
{
    /**
     * @var object|array
     */
    protected $controllerRepositoryResult;

    /**
     * @param object|array $result
     * @param GetResponseForControllerResultEvent $event
     */
    public function __construct($result, GetResponseForControllerResultEvent $event)
    {
        $this->controllerResult = $result;
        parent::__construct($event);
    }

    /**
     * @param object|array $result
     */
    public function setControllerRepositoryResult($result)
    {
        $this->controllerResult = $result;
    }

    /**
     * @return object|array
     */
    public function getControllerRepositoryResult()
    {
        return $this->controllerResult;
    }
}
