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
        // DO NOT RUN parent::__construct()
    }

    public function showExceptionAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $code = $this->getExceptionCode($exception);

        return new JsonResponse([
            'success' => false,
            'code'    => $code,
            'message' => $exception->getMessage(),
        ], $code);
    }

    /**
     * @param FlattenException $exception
     * @return int
     */
    protected function getExceptionCode(FlattenException $exception) : int
    {
        $code = $exception->getCode();
        if (array_key_exists($code, JsonResponse::$statusTexts)) {
            return $code;
        }

        if ($exception->getPrevious() && $exception->getPrevious() instanceof FlattenException) {
            return $this->getExceptionCode($exception->getPrevious());
        }

        return 500;
    }
}
