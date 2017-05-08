<?php

namespace Opstalent\ApiBundle\Exception;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ColumnNotDefinedException extends \RuntimeException implements Exception
{
    /**
     * @param string $entity
     * @param string $column
     */
    public function __construct(string $entity, string $column)
    {
        parent::__construct(sprintf('Definition of column "%s" not found in entity "%s"', $entity, $column));
    }
}
