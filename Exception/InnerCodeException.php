<?php

namespace Opstalent\ApiBundle\Exception;

use Throwable;

/**
 * @author Tomasz Piasecki <tpiasecki85@gmail.com>
 * @package AppBundle
 */
class InnerCodeException extends \Exception implements Exception, InnerCodeExceptionInterface
{

    /** @var integer $innerCode  */
    private $innerCode;

    /**
     * CodeException constructor.
     * @param string $message
     * @param int $code
     * @param null $innerCode
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, $innerCode = null , Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->innerCode = $innerCode;
    }

    /**
     * @return int
     */
    public function getInnerCode(): ?int
    {
        return $this->innerCode;
    }

    /**
     * @param int $innerCode
     * @return CodeException
     */
    public function setInnerCode(?int $innerCode): InnerCodeException
    {
        $this->innerCode = $innerCode;
        return $this;
    }
}
