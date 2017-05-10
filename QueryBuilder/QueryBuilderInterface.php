<?php

namespace Opstalent\ApiBundle\QueryBuilder;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
interface QueryBuilderInterface
{
    /**
     * @param int $limit
     */
    public function setLimit(int $limit);

    /**
     * @param int $offset
     */
    public function setOffset(int $offset);

    /**
     * @param string $order
     * @param string $orderBy
     */
    public function setOrder(string $order, string $orderBy);

    /**
     * @param string $field
     * @param string $type
     * @param mixed $value
     */
    public function filter(string $field, string $type, $value);

    /**
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function inner();
}
