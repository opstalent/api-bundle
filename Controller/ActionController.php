<?php

namespace Opstalent\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
      //dump($this->get('router')->getRouteCollection()->get($request->attributes->get('_route')));

            return new JsonResponse([
                'success' => true,
                'data'    => [] // Your data here
            ]);

        } catch (\Exception $exception) {

            return new JsonResponse([
                'success' => false,
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ],400);

        }
    }
}
