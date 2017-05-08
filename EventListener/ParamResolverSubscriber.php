<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Annotation\ParamResolver;
use Opstalent\ApiBundle\Repository\PersistableRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
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
     * @throws \Exception
     */
    public function resolveParams(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $annotation = $request->attributes->get('_param_resolver');
        if (!$annotation instanceof ParamResolver) {
            return;
        }

        if (!$this->verifyMethodParams($annotation, $event->getController())) {
            throw new \LogicException(sprintf(
                'Controller param "%s" do not exist',
                $annotation->methodParam
            ));
        }

        $repository = $this->extractRepository($request);
        $entity = $repository->findOneBy([
            $annotation->entityField => $this->extractFilterValue($request, $annotation),
        ]);
        if (null === $entity) {
            throw new \Exception ('Entity  not found', 404);
        }

        $request->attributes->set($annotation->methodParam, $entity);
    }

    /**
     * @param Request $request
     * @return PersistableRepositoryInterface
     * @throws \LogicException
     */
    private function extractRepository(Request $request) : PersistableRepositoryInterface
    {
        $route = $this->router->getRouteCollection()->get($request->attributes->get('_route'));
        $repository = $this->container->get(substr($route->getOption('repository'), 1));
        if (!$repository instanceof PersistableRepositoryInterface) {
            throw new \LogicException(sprintf(
                'Param resolver expects that repository would be instance of %s, %s given',
                PersistableRepositoryInterface::class,
                is_object($repository) ? get_class($repository) : gettype($repository)
            ));
        }

        return $repository;
    }

    /**
     * @param Request $request
     * @param ParamResolver $annotation
     * @return mixed
     * @throws \LogicException
     */
    private function extractFilterValue(Request $request, ParamResolver $annotation)
    {
        $filterValue = $request->attributes->get($annotation->queryParam);
        if (null == $filterValue) {
            throw new \LogicException(sprintf(
                'Query param "%s" not found in the request',
                $annotation->queryParam
            ));
        }

        return $filterValue;
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
            if ($param->name == $annotation->methodParam) {
                return true;
            }
        }

        return false;
    }
}
