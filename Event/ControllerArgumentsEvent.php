<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ControllerArgumentsEvent extends EventAware
{
    /**
     * @var array
     */
    protected $arguments;

    /**
     * @param array $arguments
     * @param FilterControllerArgumentsEvent $event
     */
    public function __construct(array $arguments, FilterControllerArgumentsEvent $event)
    {
        $this->arguments = $arguments;
        parent::__construct($event);
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }
}
