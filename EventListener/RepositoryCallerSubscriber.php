<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\RepositoryAction;
use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class RepositoryCallerSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['callRepositoryMethod', 5],
        ];
    }

    /**
     * @param RouterInterface
     * @param ContainerInterface
     */
    public function __construct(RouterInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * @param GetResponseForControllerResultEvent
     * @throws \LogicException
     */
    public function callRepositoryMethod(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $action = $request->attributes->get('_repository_caller');
        if (!$action instanceof RepositoryAction) {
            return;
        }

        $route = $this->router->getRouteCollection()->get($request->attributes->get('_route'));
        $repository = $this->container->get(substr($route->getOption('repository'), 1));
        if (!$repository instanceof BaseRepository) {
            throw \LogicException(sprintf(
                'Repository caller expects that repository would be instance of %s, %s given',
                BaseRepository::class,
                is_object($repository) ? get_class($repository) : gettype($repository)
            ));
        }

        if (!method_exists($repository, $action->method)) {
            throw \LogicException(sprintf(
                'Repository method "%s" does not exist',
                $action->method
            ));
        }

        $params = $action->params;
        array_unshift($params, $event->getControllerResult());

        $result = call_user_func_array([$repository, $action->method], $params);

        $event->setControllerResult($result);
    }
}
