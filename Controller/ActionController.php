<?php

namespace Opstalent\ApiBundle\Controller;

use Opstalent\ApiBundle\Annotation as API;
use Opstalent\ApiBundle\Event\ApiEvent;
use Opstalent\ApiBundle\Event\ApiEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Opstalent\ApiBundle\Exception\FormException;

/**
 * @API\Response(class="Symfony\Component\HttpFoundation\JsonResponse")
 * @API\LifecycleEvents
 */
class ActionController extends Controller
{

    /**
     * @API\Serializable(method="list")
     * @API\RepositoryAction(method="searchByFilters")
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
            return is_array($form->getData()) ? $form->getData() : [];
        } else {
            throw new FormException((string)$form->getErrors(true, true), 400, $form->getErrors());
        }
    }

    /**
     * @API\ParamResolver
     * @API\Serializable(method="get")
     */
    public function getAction($entity)
    {
        return $entity;
    }

    /**
     * @API\Serializable(method="get")
     * @API\RepositoryAction(method="persist", params={true})
     */
    public function postAction(Request $request)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
        $form = $this->createForm($route->getOption('form'));
        $form->handleRequest($request);
        $this->get('event_dispatcher')->dispatch(ApiEvents::POST_HANDLE_REQUEST, new ApiEvent($request, $form));
        if (($form->isSubmitted() && $form->isValid())) {
            return $form->getData();
        } else {
            throw new FormException((string)$form->getErrors(true, true), 400, $form->getErrors());
        }
    }

    /**
     * @API\ParamResolver
     * @API\Serializable(method="get")
     * @API\RepositoryAction(method="persist", params={true})
     */
    public function putAction(Request $request, $entity)
    {
        $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));

        $form = $this->createForm($route->getOption('form'), $entity);
        /**
        * @var string $field
        * @var Form $fieldForm
        */
        foreach ($form->all() as $field => $fieldForm) {
            if (!array_key_exists($field, $request->request->get($form->getName()))) {
                $form->remove($field);
            }
        }
        $form->handleRequest($request);
        $this->get('event_dispatcher')->dispatch(ApiEvents::POST_HANDLE_REQUEST, new ApiEvent($request, $form));
        if ($form->isSubmitted() && $form->isValid()) {
            return $form->getData();
        } else {
            throw new FormException((string)$form->getErrors(true, true), 400, $form->getErrors());
        }
    }

    /**
     * @API\ParamResolver
     * @API\Serializable(method="get")
     * @API\RepositoryAction(method="remove", params={true})
     */
    public function deleteAction(Request $request, $entity)
    {
        return $entity;
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
