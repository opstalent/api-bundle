<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\Serializable;
use Opstalent\ApiBundle\Service\SerializerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ResponseSerializerSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var SerializerService
     */
    private $serializer;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['serializeResponse', -5],
        ];
    }

    /**
     * @param RouterInterface
     * @param SerializerService
     */
    public function __construct(RouterInterface $router, SerializerService $serializer)
    {
        $this->router = $router;
        $this->serializer = $serializer;
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function serializeResponse(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $serializable = $request->attributes->get('_serializable');
        if (!$serializable instanceof Serializable) {
            return;
        }

        $groups = $this->serializer->generateSerializationGroup(
            $this->router->getRouteCollection()->get($request->attributes->get('_route')),
            $serializable->method,
            $event->getControllerResult()
        );

        $serialized = $this->serializer->serialize($event->getControllerResult(), $serializable->format, [
            'enable_max_depth' => true,
            'groups' => $groups,
            'entity' => $event->getControllerResult(),
            'top' => true,
        ]);

        $event->setControllerResult($serialized);
    }
}
