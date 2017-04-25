<?php

namespace Opstalent\ApiBundle\Controller;

use Opstalent\ApiBundle\Annotation as API;
use Opstalent\ApiBundle\Event\ApiEvent;
use Opstalent\ApiBundle\Event\ApiEvents;
use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * @API\Response(class="Symfony\Component\HttpFoundation\JsonResponse")
 */
class ActionController extends Controller
{

    /**
     * @API\Serializable(method="list")
     */
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
        $this->get('event_dispatcher')->dispatch(ApiEvents::POST_HANDLE_REQUEST, new ApiEvent($request, $form));
        if (($form->isSubmitted() && $form->isValid()) || $form->isEmpty()) {
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'), 1));

            return $repository->searchByFilters(is_array($form->getData()) ? $form->getData() : []);
        } else {
            throw new \Exception((string)$form->getErrors(true, false), 400);
        }
    }

    /**
     * @API\Serializable(method="get")
     */
    public function getAction(Request $request, int $id)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        /** @var BaseRepository $repository */
        $repository = $this->get(substr($route->getOption('repository'), 1));
        $data = $repository->find($id);
        if ($data) {
            return $data;
        } else throw new \Exception("Not Found", 404);
    }

    /**
     * @API\Serializable(method="get")
     */
    public function postAction(Request $request)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        $form = $this->createForm($route->getOption('form'));
        $form->handleRequest($request);
        $this->get('event_dispatcher')->dispatch(ApiEvents::POST_HANDLE_REQUEST, new ApiEvent($request, $form));
        if (($form->isSubmitted() && $form->isValid())) {
            /** @var BaseRepository $repository */
            $repository = $this->get(substr($route->getOption('repository'), 1));

            return $repository->persist($form->getData(), true);
        } else {
            throw new \Exception((string)$form->getErrors(true, true), 400);
        }
    }

    /**
     * @API\Serializable(method="get")
     */
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
            $this->get('event_dispatcher')->dispatch(ApiEvents::POST_HANDLE_REQUEST, new ApiEvent($request, $form));
            if ($form->isSubmitted() && $form->isValid()) {
                return $repository->persist($form->getData(), true);
            } else {
                throw new \Exception((string)$form->getErrors(true, false), 404);
            }

        } else throw new \Exception("Not Found", 404);
    }

    /**
     * @API\Serializable(method="get")
     */
    public function deleteAction(Request $request, int $id)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        /** @var BaseRepository $repository */
        $repository = $this->get(substr($route->getOption('repository'), 1));
        if ($entity = $repository->find($id)) {
            return $repository->remove($entity, true, $request->request->all());
        } else throw new \Exception("Not Found", 404);
    }

    protected function addPaginatorFilters(Form $form)
    {
        $form
            ->add('offset', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('limit', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('orderBy', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('order', TextType::class, ['required'=> false, 'mapped'=> true])
            ->add('count', TextType::class, ['required'=> false, 'mapped'=> true])
        ;
    }
}
