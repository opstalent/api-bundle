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

//            if (false === $this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
//                throw new AccessDeniedException();
//            }

            // Controller grab all requests to /
            // Check token
            // Check if user have role to use this endpoint
            // Check method
            //run logic
            //return jsonResponse
            $route = $this->get('router')->getRouteCollection()->get($request->attributes->get('_route'));
            $form = $this->createForm($route->getOption('form'));
            $form->handleRequest($request);
            $form->submit($form->getData());
            if($form->isSubmitted() || $form->isValid())
            {
                /** @var EntityRepository $repository */
                $repository = $this->get(substr($route->getOption('repository'),1));
                $data = $repository->searchByFilters($form->getData());
                return new Response(
                    $this->get('opstalent.api_bundle.serializer_service')->serialize($data,"json",['groups'=> ['list']]),
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
