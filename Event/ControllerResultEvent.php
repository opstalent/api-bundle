<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ControllerResultEvent extends EventAware
{
    /**
     * @var mixed
     */
    protected $controllerResult;

    /**
     * @param mixed $result
     * @param GetResponseForControllerResultEvent $event
     */
    public function __construct($result, GetResponseForControllerResultEvent $event)
    {
        $this->controllerResult = $result;
        parent::__construct($event);
    }

    /**
     * @param mixed $result
     */
    public function setControllerResult($result)
    {
        $this->controllerResult = $result;
    }

    /**
     * @return mixed
     */
    public function getControllerResult()
    {
        return $this->controllerResult;
    }
}
