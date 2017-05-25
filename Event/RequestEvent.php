<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class RequestEvent extends EventAware
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     * @param FilterControllerEvent $event
     */
    public function __construct(Request $request, FilterControllerEvent $event)
    { 
        $this->request = $request;
        parent::__construct($event);
    }

    /**
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }
}
