<?php

namespace Opstalent\ApiBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Opstalent\ApiBundle\Annotation\AnnotationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class AnnotationResolverSubscriber implements EventSubscriberInterface
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['resolveControllerAnnotations', 200],
        ];
    }

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function resolveControllerAnnotations(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        if (!is_array($controller)) {
            return;
        }

        $object = new \ReflectionClass(get_class($controller[0]));
        $method = $object->getMethod($controller[1]);

        $objectConfig = $this->processAnnotations($this->reader->getClassAnnotations($object));
        $methodConfig = $this->processAnnotations($this->reader->getMethodAnnotations($method));

        $config = array_merge($objectConfig, $methodConfig);

        $request = $event->getRequest();
        foreach ($config as $key => $annotation) {
            $request->attributes->set($key, $annotation);
        }
    }

    /**
     * @param array $annotations
     * @return array
     * @throws \LogicException
     */
    protected function processAnnotations(array $annotations) : array
    {
        $attributes = [];
        foreach ($annotations as $annotation) {
            if (!$annotation instanceof AnnotationInterface) {
                continue;
            }

            $key = '_' . $annotation->getAliasName();
            if (array_key_exists($key, $attributes)) {
                throw new \LogicException(sprintf(
                    'Multiple "%s" annotations are not allowed.',
                    $annotation->getAliasName()
                ));
            }

            $attributes['_' . $annotation->getAliasName()] = $annotation;
        }

        return $attributes;
    }
}
