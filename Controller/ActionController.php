<?php

namespace Opstalent\ApiBundle\Controller;

use AppBundle\Repository\UserRepository;
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
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                /** @var EntityRepository $repository */
                $repository = $this->get(substr($route->getOption('repository'),1));
                dump($request->query->all());
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize(
                        $repository->searchByFilters($request->query->all()['list'])
                        ,"json",['groups'=> ['list']]
                    ),
                    200,
                    ['Content-Type'=> 'application/json']
                );
            } else throw new \Exception($form->getErrors()->count(),400);

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
