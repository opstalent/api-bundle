<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ResponseEvent extends EventAware
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @param Response $response
     * @param FilterResponseEvent $event
     */
    public function __construct(Response $response, FilterResponseEvent $event)
    {
        $this->response = $response;
        parent::__construct($event);
    }

    /**
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }
}
