<?php

namespace Opstalent\ApiBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class BooleanToStringTransformer
 * @package Opstalent\ApiBundle
 */
class BooleanToStringTransformer implements DataTransformerInterface
{

    /**
     * @param bool $fieldValue
     * @return string
     */
    public function transform($fieldValue)
    {
        return ($fieldValue) ? 'true' : 'false';
    }


    /**
     * @param string $fieldValue
     * @return bool
     */
    public function reverseTransform($fieldValue)
    {
        return ($fieldValue === 'true') ? true : false;
    }
}
