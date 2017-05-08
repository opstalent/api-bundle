<?php

namespace Opstalent\ApiBundle\Event;

use Opstalent\ApiBundle\QueryBuilder\QueryBuilderInterface;
use Opstalent\ApiBundle\Repository\RepositoryInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class RepositorySearchEvent extends RepositoryEvent
{
    /**
     * @var QueryBuilderInterface
     */
    protected $queryBuilder;

    /**
     * {@inheritdoc}
     * @param QueryBuilderInterface $qb
     */
    public function __construct(string $name, RepositoryInterface $repository, $data = null, QueryBuilderInterface $qb)
    {
        parent::__construct($name, $repository, $data);
        $this->queryBuilder = $qb;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder() : QueryBuilderInterface
    {
        return $this->queryBuilder;
    }
}
