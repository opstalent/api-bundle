<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\LifecycleEvents;
use Opstalent\ApiBundle\Annotation\RepositoryAction;
use Opstalent\ApiBundle\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event as KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ControllerLifecycleSubscriber implements EventSubscriberInterface
{
    const REQUEST = 'opstalent.api.%s.request';
    const CONTROLLER_ARGUMENTS = 'opstalent.api.%s.controller_arguments';
    const CONTROLLER_RESULT = 'opstalent.api.%s.controller_result';
    const CONTROLLER_REPOSITORY_RESULT = 'opstalent.api.%s.repository_result';
    const RESPONSE = 'opstalent.api.%s.response';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['dispatchRequest', 199],
            KernelEvents::CONTROLLER_ARGUMENTS => ['dispatchControllerArguments', -255],
            KernelEvents::VIEW => [
                ['dispatchControllerResult', 255],
                ['dispatchControllerRepositoryResult', 4],
            ],
            KernelEvents::RESPONSE => ['dispatchResponse', -9999],
        ];
    }

    /**
     * @param KernelEvent\FilterControllerEvent $event
     */
    public function dispatchRequest(KernelEvent\FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->get('_lifecycle') instanceof LifecycleEvents) {
            return;
        }

        $event = new Event\RequestEvent($request, $event);
        $eventName = sprintf(static::REQUEST, $this->getRouteName($request));
        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param KernelEvent\FilterControllerArgumentEvent $baseEvent
     */
    public function dispatchControllerArguments(KernelEvent\FilterControllerArgumentsEvent $baseEvent)
    {
        $request = $baseEvent->getRequest();
        if (!$request->attributes->get('_lifecycle') instanceof LifecycleEvents) {
            return;
        }

        $event = new Event\ControllerArgumentsEvent($baseEvent->getArguments(), $baseEvent);
        $eventName = sprintf(static::CONTROLLER_ARGUMENTS, $this->getRouteName($request));
        $this->dispatcher->dispatch($eventName, $event);

        $baseEvent->setArguments($event->getArguments());
    }

    /**
     * @param KernelEvent\GetResponseForControllerResultEvent $baseEvent
     */
    public function dispatchControllerResult(KernelEvent\GetResponseForControllerResultEvent $baseEvent)
    {
        $request = $baseEvent->getRequest();
        if (!$request->attributes->get('_lifecycle') instanceof LifecycleEvents) {
            return;
        }

        $event = new Event\ControllerResultEvent($baseEvent->getControllerResult(), $baseEvent);
        $eventName = sprintf(static::CONTROLLER_RESULT, $this->getRouteName($request));
        $this->dispatcher->dispatch($eventName, $event);

        $baseEvent->setControllerResult($event->getControllerResult());
    }

    /**
     * @param KernelEvent\GetResponseForControllerResultEvent $baseEvent
     */
    public function dispatchControllerRepositoryResult(KernelEvent\GetResponseForControllerResultEvent $baseEvent)
    {
        $request = $baseEvent->getRequest();
        if (
            !$request->attributes->get('_lifecycle') instanceof LifecycleEvents
            || !$request->attributes->get('_repository_caller') instanceof RepositoryAction
        ) {
            return;
        }

        $event = new Event\ControllerRepositoryResultEvent($baseEvent->getControllerResult(), $baseEvent);
        $eventName = sprintf(static::CONTROLLER_REPOSITORY_RESULT, $this->getRouteName($request));
        $this->dispatcher->dispatch($eventName, $event);

        $baseEvent->setControllerResult($event->getControllerRepositoryResult());
    }

    /**
     * @param KernelEvent\FilterResponseEvent $event
     */
    public function dispatchResponse(KernelEvent\FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->get('_lifecycle') instanceof LifecycleEvents) {
            return;
        }

        $event = new Event\ResponseEvent($event->getResponse(), $event);
        $eventName = sprintf(static::RESPONSE, $this->getRouteName($request));
        $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * @param Request $request
     * @return string
     * @throws \UnexpectedValueException
     */
    private function getRouteName(Request $request) : string
    {
        $route = $request->attributes->get('_route');
        if (!is_string($route)) {
            throw new \UnexpectedValueException('Route is not valid string');
        }

        return $route;
    }
}
