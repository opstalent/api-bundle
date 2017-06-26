<?php

namespace Opstalent\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Szymon Kunowski <szymon.kunowski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class CORSSubscriber implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * CORSSubscriber constructor.
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 99999],
            KernelEvents::RESPONSE => ['onKernelResponse', 99999]
        ];
    }

    public function onKernelRequest(GetResponseEvent $event) {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $method  = $request->getRealMethod();
        if ('OPTIONS' == $method) {
            $response = new Response();
            $response->setStatusCode(200);
            $event->setResponse($response);
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->add([
            "Access-Control-Allow-Origin" => $event->getRequest()->server->get('HTTP_ORIGIN'), $event->getRequest()->getHost(),
            "Access-Control-Allow-Credentials" => "true",
            "Access-Control-Allow-Headers" => "Authorization, X-Requested-With, Content-Type, Accept, Origin, X-Custom-Auth, Cache-Control",
            "Access-Control-Allow-Methods" => $event->getRequest()->getMethod() === 'OPTIONS' ? $this->getAllowedMethods($event->getRequest()) : $event->getRequest()->getMethod(),
        ]);
    }

    protected function getAllowedMethods(Request $request)
    {
        $path = str_replace("/app_dev.php","",$request->server->get('DOCUMENT_URI'));
        if(substr_count($path,"/") == 2) {
            $pos = strrpos($path, '/');
            $id = $pos === false ? $path : substr($path, $pos + 1);
            $path = str_replace($id,"{id}", $path);
        }
        $match =array_filter($this->router->getRouteCollection()->getIterator()->getArrayCopy(), function($item) use ($path){
            return $item->getPath() == $path;
        });
        $accu = [];
        foreach ($match as $route) {
            $accu = array_merge($accu, $route->getMethods());
        }

        return $accu;
    }
}
