<?php

namespace Opstalent\ApiBundle\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExceptionController extends BaseExceptionController
{

    public function __construct()
    {
//        parent::__construct($twig,$debug);
    }

    public function showExceptionAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        return new JsonResponse([
            'success' => false,
            'code'    => $exception->getCode(),
            'message' => $exception->getMessage(),
        ],$exception->getCode());
    }
}