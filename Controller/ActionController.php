<?php

namespace Opstalent\ApiBundle\Controller;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\AbstractType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\Role\Role;

class ActionController extends Controller
{

    public function listAction(Request $request)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        $form = $this->createForm($route->getOption('form'));
        $this->addPaginatorFilters($form);
        foreach ($form->all() as $field => $fieldForm)
        {
            if(!$request->get($form->getName()) || !array_key_exists($field,$request->get($form->getName()))){
                $form->remove($field);
            }
        }
        $form->handleRequest($request);
        if (($form->isSubmitted() && $form->isValid()) || $form->isEmpty()) {
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'), 1));
            return new Response(
                $this->get('opstalent.api_bundle.serializer_service')->serialize(
                    $repository->searchByFilters(is_array($form->getData()) ? $form->getData() : [])
                    , "json", ['enable_max_depth' => true, 'groups' => $this->get('opstalent.api_bundle.serializer_service')->generateSerializationGroup($route, "list")]
                ),
                200,
                ['Content-Type' => 'application/json']
            );
        } else {
            throw new \Exception((string)$form->getErrors(true, false), 400);
        }
    }

    public function getAction(Request $request, int $id)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        /** @var BaseRepository $repository */
        $repository = $this->get(substr($route->getOption('repository'), 1));
        $data = $repository->find($id);
        if ($data) {
            return new Response(
                $this->get('opstalent.api_bundle.serializer_service')->serialize(
                    $data
                    , "json", ['enable_max_depth' => true, 'groups' => $this->get('opstalent.api_bundle.serializer_service')->generateSerializationGroup($route, "get", $data)]
                ),
                200,
                ['Content-Type' => 'application/json']
            );
        } else throw new \Exception("Not Found", 404);
    }

    public function postAction(Request $request)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        $form = $this->createForm($route->getOption('form'));
        $form->handleRequest($request);
        if (($form->isSubmitted() && $form->isValid())) {
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'), 1));
            return new Response(
                $this->get('opstalent.api_bundle.serializer_service')->serialize(
                    $repository->persist($form->getData(), true)
                    , "json", ['enable_max_depth' => true, 'groups' => $this->get('opstalent.api_bundle.serializer_service')->generateSerializationGroup($route, "get", $form->getData())]
                ),
                200,
                ['Content-Type' => 'application/json']
            );
        } else {
            throw new \Exception((string)$form->getErrors(true, true), 400);
        }
    }

    public function putAction(Request $request, int $id)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));

        /** @var BaseRepository $repository */
        $repository = $this->get(substr($route->getOption('repository'), 1));
        if ($entity = $repository->find($id)) {
            $form = $this->createForm($route->getOption('form'), $entity);
            foreach ($form->all() as $field => $fieldForm)
                {
                    if(!array_key_exists($field,$request->request->all()[$form->getName()])){
                        $form->remove($field);
                    }
                }
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $repository->persist($form->getData(), true)
                        , "json", ['enable_max_depth' => true, 'groups' => $this->get('opstalent.api_bundle.serializer_service')->generateSerializationGroup($route, "get", $form->getData())]
                    ),
                    200,
                    ['Content-Type' => 'application/json']
                );
            } else {
                throw new \Exception((string)$form->getErrors(true, false), 404);
            }

        } else throw new \Exception("Not Found", 404);
    }

    public function deleteAction(Request $request, int $id)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        /** @var BaseRepository $repository */
        $repository = $this->get(substr($route->getOption('repository'), 1));
        if ($entity = $repository->getReference($id)) {
            return new Response(
                $this->get('opstalent.api_bundle.serializer_service')->serialize(
                    $repository->remove($entity, true, $request->request->all())
                    , "json", ['enable_max_depth' => true, 'groups' => $this->get('opstalent.api_bundle.serializer_service')->generateSerializationGroup($route, "get", $entity)]
                ),
                200,
                ['Content-Type' => 'application/json']
            );
        } else throw new \Exception("Not Found", 404);
    }

    protected function addPaginatorFilters(Form $form)
    {
        $form
            ->add('page', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('limit', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('column', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('sort', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('count', TextType::class, ['required'=> false, 'mapped'=> true])
        ;
    }
}
