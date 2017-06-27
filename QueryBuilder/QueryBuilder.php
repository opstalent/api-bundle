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
     * @var string
     */
    protected $mainTableAlias;

    /**
     * @param OrmQueryBuilder $qb
     * @param string $alias
     */
    public function __construct(OrmQueryBuilder $qb, string $alias)
    {
        $this->queryBuilder = $qb;
        $this->mainTableAlias = $alias;
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
        $this->inner()->orderBy($this->mainTableAlias . '.' . $orderBy, $order);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(string $field, string $type, $value)
    {
        $field = $this->mainTableAlias . '.' . $field;
        switch($type) {
            case 'string':
            case 'text':
                $this->stringFilter($field, $value);
                break;
            case 'datetime':
                $this->datetimeFilter($field, $value);
                break;
            case 'entity':
            default:
                $this->inner()
                    ->andWhere($field . " = ". ':' . $this->stripDot($field))
                    ->setParameter($this->stripDot($field), $value)
                    ;
                break;
        }
    }

    public function stripDot(string $str)
    {
        return str_replace(".", "_" , $str);
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

    /**
     * @param string $field
     * @param string $value
     */
    protected function stringFilter(string $field, string $value)
    {
        $value = $this->inner()->expr()->literal('%' . $value . '%');
        $expression = $this->inner()->expr()->like($field, $value);

        $this->inner()->andWhere($expression);
    }

    /**
     * @param string $field
     * @param \DateTime $value
     */
    protected function datetimeFilter(string $field, \DateTime $value)
    {
        $lowerLimit = $this->inner()->expr()->gte($property, $value);

        $upperValue = clone $value;
        $upperValue->setTime($upperValue->format('H'), $upperValue->format('i'), 59);
        $upperLimit = $this->inner()->expr()->lt($property, $value);

        $this->inner()
            ->andWhere($lowerLimit)
            ->andWhere($upperLimit)
            ;
    }
}
