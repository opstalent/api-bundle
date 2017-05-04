<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ResponseWrapperSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['wrapResponse', -100],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function wrapResponse(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $annotation = $request->attributes->get('_response_transformer');
        if (
            !$annotation instanceof Response
            || $event->hasResponse()
        ) {
            return;
        }

        $classname = $annotation->getClass();
        $response = new $classname($event->getControllerResult(), 200, [], true);

        $event->setResponse($response);
    }
}
