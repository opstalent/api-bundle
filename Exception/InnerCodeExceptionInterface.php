<?php

namespace Opstalent\ApiBundle\Exception;

/**
 * @author Tomasz Piasecki <tpiasecki85@gmail.com>
 * @package AppBundle
 */
interface InnerCodeExceptionInterface extends \Throwable
{
    public function getInnerCode();

    public function setInnerCode(int $innerCode);
}
