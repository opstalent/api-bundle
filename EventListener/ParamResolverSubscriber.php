<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\ParamResolver;
use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ParamResolverSubscriber implements EventSubscriberInterface
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
            KernelEvents::CONTROLLER => ['resolveParams', 1],
        ];
    }

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, ContainerInterface $container)
    {
        $this->router = $router;
        $this->container = $container;
    }

    /**
     * @param FilterControllerArgumentsEvent $event
     * @throws \LogicException
     * @throws \Exception
     */
    public function resolveParams(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $annotation = $request->attributes->get('_param_resolver');
        if (!$annotation instanceof ParamResolver) {
            return;
        }

        $filterValue = $request->attributes->get($annotation->queryParam);
        if (null == $filterValue) {
            throw new \LogicException(sprintf(
                'Query param "%s" not found in the request',
                $annotation->queryParam
            ));
        }

        if (!$this->verifyMethodParams($annotation, $event->getController())) {
            throw new \LogicException(string(
                'Controller param "%s" do not exist',
                $annotation->methodParam
            ));
        }

        $route = $this->router->getRouteCollection()->get($request->attributes->get('_route'));
        $repository = $this->container->get(substr($route->getOption('repository'), 1));
        if (!$repository instanceof BaseRepository) {
            throw new \LogicException(sprintf(
                'Repository caller expects that repository would be instance of %s, %s given',
                BaseRepository::class,
                is_object($repository) ? get_class($repository) : gettype($repository)
            ));
        }

        $entity = $repository->findOneBy([
            $annotation->entityField => $filterValue,
        ]);
        if (null === $entity) {
            throw new \Exception ('Entity  not found', 404);
        }

        $request->attributes->set($annotation->methodParam, $entity);
    }

    /**
     * @param ParamResolver $annotation
     * @param mixed $controller
     * @return bool
     */
    private function verifyMethodParams(ParamResolver $annotation, $controller) : bool
    {
        if (is_array($controller)) {
            $reflection = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (is_object($controller) && is_callable($controller, '__invoke')) {
            $reflection = new \ReflectionMethod($controller, '__invoke');
        } else {
            $reflection = new \ReflectionFunction($controller);
        }

        foreach ($reflection->getParameters() as $param) {
            if ($param->name == $annotation->methodParam && !$param->hasType()) {
                return true;
            }
        }

        return false;
    }
}
