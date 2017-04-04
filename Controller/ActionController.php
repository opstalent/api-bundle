<?php

namespace Opstalent\ApiBundle\Controller;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\AbstractType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ActionController extends Controller
{

    public function listAction(Request $request)
    {
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            $form = $this->createForm($route->getOption('form'));

            $form->submit($request->query->all());
            if(($form->isSubmitted() && $form->isValid()) || $form->isEmpty())
            {
                /** @var BaseRepository $repository */
                $repository = $this->get(substr($route->getOption('repository'),1));
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $repository->searchByFilters($form->getData())
                        ,"json",['groups'=> [$route->getOption('serializerGroup') ? $route->getOption('serializerGroup') : 'list']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else {
                throw new \Exception((string)$form->getErrors(true,false),400);
            }
    }

    public function getAction(Request $request, int $id)
    {
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            $data = $repository->find($id);
            if($data) {
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $data
                        ,"json",['groups'=> [$route->getOption('serializerGroup') ? $route->getOption('serializerGroup') : 'get']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else throw new \Exception("Not Found",404);
    }

    public function postAction(Request $request)
    {
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            $form = $this->createForm($route->getOption('form'));
            $form->handleRequest($request);

            if(($form->isSubmitted() && $form->isValid()))
            {
                /** @var BaseRepository $repository */
                $repository = $this->get(substr($route->getOption('repository'),1));
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $repository->persist($form->getData(),true)
                        ,"json",['groups'=> [$route->getOption('serializerGroup') ? $route->getOption('serializerGroup') : 'get']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else {
                throw new \Exception((string)$form->getErrors(true,true),400);
            }
    }

    public function putAction(Request $request, int $id)
    {
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));

            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            if($entity = $repository->find($id)) {
                $form = $this->createForm($route->getOption('form'),$entity);
                $form->handleRequest($request);
                dump($form->getData());
                if($form->isSubmitted() && $form->isValid())
                {
                    return new Response(
                        $this->get('opstalent.api_bundle.serializer_service')->serialize(
                            $repository->persist($form->getData(),true)
                            ,"json",['groups'=> [$route->getOption('serializerGroup') ? $route->getOption('serializerGroup') : 'get']]
                        ),
                        200,
                        ['Content-Type'=> 'application/json']
                    );
                } else {
                    throw new \Exception((string)$form->getErrors(true,false),404);
                }

            } else throw new \Exception("Not Found",404);
    }

    public function deleteAction(Request $request, int $id)
    {
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            if($entity = $repository->getReference($id)) {
                    return new Response(
                        $this->get('opstalent.api_bundle.serializer_service')->serialize(
                            $repository->remove($entity, true)
                            ,"json",['groups'=> [$route->getOption('serializerGroup') ? $route->getOption('serializerGroup') : 'get']]
                        ),
                        200,
                        ['Content-Type'=> 'application/json']
                    );
            } else throw new \Exception("Not Found",404);
    }
}
