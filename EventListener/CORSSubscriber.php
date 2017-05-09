<?php

namespace Opstalent\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Szymon Kunowski <szymon.kunowski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class CORSSubscriber implements EventSubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 99999],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event) {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $method  = $request->getRealMethod();
        header("Access-Control-Allow-Origin: " . $event->getRequest()->server->get('HTTP_ORIGIN'), $event->getRequest()->getHost());
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Authorization, X-Requested-With, Content-Type, Accept, Origin, X-Custom-Auth, Cache-Control");
        if ('OPTIONS' == $method) {
            $response = new Response();
            $response->setStatusCode(200);
            $event->setResponse($response);
        }
    }
}
