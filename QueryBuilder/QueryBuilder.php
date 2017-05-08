<?php

namespace Opstalent\ApiBundle\QueryBuilder;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder as OrmQueryBuilder;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class QueryBuilder implements QueryBuilderInterface
{
    /**
     * @var OrmQueryBuilder
     */
    protected $queryBuilder;

    /**
     * @param OrmQueryBuilder $qb
     */
    public function __construct(OrmQueryBuilder $qb)
    {
        $this->queryBuilder = $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit(int $limit)
    {
        $this->inner()->setMaxResults($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset)
    {
        $this->inner()->setFirstResult($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(string $order, string $orderBy)
    {
        $this->inner()->orderBy($orderBy, $order);
    }

    /**
     * @return Query
     */
    public function getQuery() : Query
    {
        return $this->inner()->getQuery();
    }

    /**
     * @return OrmQueryBuilder
     */
    public function inner() : OrmQueryBuilder
    {
        return $this->queryBuilder;
    }
}
