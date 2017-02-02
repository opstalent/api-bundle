<?php

namespace Opstalent\ApiBundle\Controller;

use AppBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

                return new JsonResponse([
                    'success' => true,
                    'data'    => $data
                ]);
            } else throw new \Exception($form->getErrors()->count(),400);
            //data ok lets handle

        } catch (\Exception $exception) {

            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);

        }
    }
}
