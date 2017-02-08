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
        try {
            //TODO: Enpoint security if security bundle exist
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

        } catch (\Exception $exception) {
            dump($exception->getMessage());
            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);
        }
    }

    public function getAction(Request $request, int $id)
    {
        try {
            //TODO: Enpoint security if security bundle exist
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

        } catch (\Exception $exception) {
            dump($exception->getMessage());
            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);
        }
    }

    public function postAction(Request $request)
    {
        try {
            //TODO: Enpoint security if security bundle exist
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

        } catch (\Exception $exception) {
            dump($exception->getMessage());
            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);
        }
    }

    public function putAction(Request $request, int $id)
    {
        try {
            //TODO: Enpoint security if security bundle exist
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

        } catch (\Exception $exception) {
            dump($exception->getMessage());
            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);
        }
    }
}
