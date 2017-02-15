<?php

namespace Opstalent\ApiBundle\Controller;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\AbstractType;

class ActionController extends Controller
{
    public function listAction(Request $request)
    {
            //TODO: Data Security (isOwner) for list
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
                        ,"json",['groups'=> ['list']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else {
                throw new \Exception($form->getErrors()->count(),400);
            }
    }

    public function getAction(Request $request, int $id)
    {
            //TODO: Data Security (isOwner) for list
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            $data = $repository->find($id);
            if($data) {
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $data
                        ,"json",['groups'=> ['get']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else throw new \Exception("Not Found",404);
    }

    public function postAction(Request $request)
    {
            //TODO: Data Security (isOwner) for list
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
                        ,"json",['groups'=> ['list']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else {
                throw new \Exception($form->getErrors()->count(),400);
            }
    }

    public function putAction(Request $request, int $id)
    {
            //TODO: Data Security (isOwner) for list
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));

            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            if($entity = $repository->find($id)) {
                $form = $this->createForm($route->getOption('form'),$entity);
                foreach ($form->all() as $field => $fieldForm)
                {
                    if(!array_key_exists($field,$request->request->all()[$form->getName()])){
                        $form->remove($field);
                    }
                }
                $form->handleRequest($request);

                if(($form->isSubmitted() && $form->isValid()))
                {
                    return new Response(
                        $this->get('opstalent.api_bundle.serializer_service')->serialize(
                            $repository->persist($form->getData(),true)
                            ,"json",['groups'=> ['list']]
                        ),
                        200,
                        ['Content-Type'=> 'application/json']
                    );
                } else throw new \Exception($form->getErrors()->current()->getMessage(),404);

            } else throw new \Exception("Not Found",404);
    }

    public function deleteAction(Request $request, int $id)
    {
            //TODO: Data Security (isOwner) for list
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'),1));
            if($entity = $repository->getReference($id)) {
                    return new Response(
                        $this->get('opstalent.api_bundle.serializer_service')->serialize(
                            $repository->remove($entity, true)
                            ,"json",['groups'=> ['list']]
                        ),
                        200,
                        ['Content-Type'=> 'application/json']
                    );
            } else throw new \Exception("Not Found",404);
    }
}
