<?php

namespace Opstalent\ApiBundle\Repository;

use AppBundle\AppBundle;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Opstalent\SecurityBundle\Event\RepositoryEvent;

class BaseRepository extends EntityRepository
{
    protected $filters = [];
    protected $repositoryName='';
    protected $repositoryAlias='';
    protected $docReader;
    protected $reflect;
    protected $entityName='';
    /** @var  TraceableEventDispatcher */
    protected $dispatcher;
    protected $qb;

    /**
     * BaseRepository constructor.
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em,$class);
        $this->docReader = new AnnotationReader();
        $entity = new $this->entityName();
        $this->reflect = new ReflectionClass($entity);
    }

    public function setEventDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getQueryBuilder():QueryBuilder
    {
        return ($this->qb) ? $this->qb : $this->createQueryBuilderInstance();
    }

    public function createQueryBuilderInstance()
    {
        $this->qb = $this->getEntityManager()->getRepository($this->repositoryName)->createQueryBuilder($this->repositoryAlias);
        return $this->qb;
    }

    public function getPropertyType(string $property)
    {
        if (!$this->reflect->hasProperty($property)) {
            dump('the entity does not have property: '. $property);
            return null;
        }
        $docInfo = $this->docReader->getPropertyAnnotations($this->reflect->getProperty($property));
        return $this->parseDocInfo($docInfo);
    }

    public function parseDocInfo(array $docInfo): string
    {
        if($docInfo[count($docInfo)-2] instanceof Column) return $docInfo[count($docInfo)-2]->type;
        return (array_key_exists("targetEntity", $docInfo[0])) ? 'integer' : 'string';
    }

    public function setLimit(int $limit, QueryBuilder $qb):QueryBuilder
    {
        return $qb->setMaxResults($limit);
    }

    public function setOffset(int $offset, QueryBuilder $qb):QueryBuilder
    {
        return $qb->setFirstResult($offset);
    }

    public function setOrder(string $order='ASC', string $orderBy, QueryBuilder $qb):QueryBuilder
    {
        return $qb->orderBy($this->repositoryAlias . "." . $orderBy,$order);
    }

    public function searchByFilters(array $data):array
    {
        $this->dispatchEvent('before.search.by.filter', $this);
        $qb = $this->getQueryBuilder();
        if(array_key_exists('limit',$data)) {
            $this->setLimit($data['limit'],$qb);
            unset($data['limit']);
        }
        if(array_key_exists('offset',$data)) {
            $this->setOffset($data['offset'],$qb);
            unset($data['offset']);
        }
        if(array_key_exists('order',$data) && array_key_exists('orderBy',$data)) {
            $this->setOrder($data['order'],$data['orderBy'], $qb);
            unset($data['order']);
            unset($data['orderBy']);
        }

        foreach ($data as $filter => $value)
        {
            if($filter === 'count') continue;
            if(array_key_exists($filter,$this->filters)) {
                $func = $this->filters[$filter];
                $this->$func($value,$qb);
            } elseif($propertyType = $this->getPropertyType($filter)) {
                $this->addPropertyFilter($value,$qb,$filter,$propertyType);
            }
        }

        $this->dispatchEvent('after.search.by.filter', $this);

        $query = $qb->getQuery();
        if(array_key_exists('count', $data)) {
            $paginator  = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
            return [
                'list' => $query->getResult(),
                'total' => count($paginator)
            ];
        }
        return [
            'list' => $query->getResult()
        ];
    }

    public function getReference(int $id)
    {
        return $this->getEntityManager()->getReference($this->repositoryName, $id);
    }

    public function remove($data, bool $flush = true)
    {
        $this->dispatchEvent('before.remove', $this , $data);
        $this->getEntityManager()->remove($data);
        if($flush) $this->flush();
        $this->dispatchEvent('after.remove', $this, $data);
        return $data;
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    public function persist($data, bool $flush=false)
    {
        $this->dispatchEvent('before.persist', $this, $data);
        $this->getEntityManager()->persist($data);
        if($flush) $this->flush();
        $this->dispatchEvent('after.persist',$this, $data);
        return $data;
    }

    private function addPropertyFilter($value, QueryBuilder $qb, string $property, string $propertyType):QueryBuilder
    {
        switch ($propertyType)
        {
            case 'string':
                $ex = $qb->expr()->like($this->repositoryAlias . '.' . $property, $qb->expr()->literal('%' . $value . '%'));
                return $qb->andWhere($ex);
                break;
            case 'datetime':
                /** @var \DateTime $value */
                $exgte = $qb->expr()->gte($this->repositoryAlias . '.' . $property, $value);
                $to = clone $value;
                $to->setTime($to->format("H"), $to->format("i"),59);
                $qb->andWhere($this->repositoryAlias . '.' . $property . ' >= :' . $property)->setParameter($property, $value);
                return $qb->andWhere($this->repositoryAlias . '.' . $property . ' <= :to' . $property)->setParameter('to'.$property, $to);;
                break;
            default:
                return $qb->andWhere($this->repositoryAlias . '.' . $property . ' = :' . $property)->setParameter($property, $value);
                break;
        }
    }

    private function dispatchEvent($name,$obj,$data=null,$params=[])
    {
        if($this->dispatcher) {
            $this->dispatcher->dispatch($name, new RepositoryEvent($name, $obj, $data));
        }
    }
}
