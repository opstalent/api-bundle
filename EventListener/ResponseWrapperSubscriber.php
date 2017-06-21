<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Exception\AnnotationNotFoundException;
use Opstalent\ApiBundle\Resolver\ExceptionCodeResolver;
use Opstalent\ApiBundle\Resolver\ResponseClassResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
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
            KernelEvents::EXCEPTION => ['handleExceptionResponse', -100],
        ];
    }

    /**
     * @param GetResponseForControllerResultEvent $event
     */
    public function wrapResponse(GetResponseForControllerResultEvent $event)
    {
        try {
            $classname = ResponseClassResolver::resolveByRequest($event->getRequest());
        } catch (AnnotationNotFoundException $e) {
            return;
        }

        $response = new $classname($event->getControllerResult(), 200, [], true);

        $event->setResponse($response);
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function handleExceptionResponse(GetResponseForExceptionEvent $event)
    {
        try {
            $classname = ResponseClassResolver::resolveByRequest($event->getRequest());
        } catch (AnnotationNotFoundException $e) {
            $classname = JsonResponse::class;
        }

        $exception = $event->getException();
        $content = [
            'success' => false,
            'code' => ExceptionCodeResolver::resolveResponseCode($exception),
            'message' => $exception->getMessage(),
            'errors' => get_class($exception) === 'Opstalent\\ApiBundle\\Exception\\FormException' ? $exception->getFormErrors() : [],
        ];

        $response = new $classname($content, $content['code'], []);
        $event->setResponse($response);
    }
}
