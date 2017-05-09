<?php

namespace Opstalent\ApiBundle\Repository;

use AppBundle\AppBundle;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ReflectionClass;
use Opstalent\ApiBundle\Event\RepositoryEvent;
use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Opstalent\ApiBundle\QueryBuilder\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BaseRepository extends EntityRepository implements
    PersistableRepositoryInterface,
    SearchableRepositoryInterface
{
    protected $filters = [];
    protected $repositoryName='';
    protected $repositoryAlias='';
    protected $docReader;
    protected $reflect;
    protected $entityName='';

    /**
     * @var EventDispatcherInterface
     */
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

    /**
     * {@inheritdoc}
     */
    public function searchByFilters(array $data) : array
    {
        $qb = new QueryBuilder($this->createQueryBuilder($this->repositoryAlias), $this->repositoryAlias);

        $this->dispatchEvent(new RepositorySearchEvent(
            RepositoryEvents::BEFORE_SEARCH_BY_FILTER,
            $this,
            $data,
            $qb
        ));

        $result = [];
        $query = $qb->getQuery();
        if (array_key_exists('count', $data)) {
            unset($data['count']);

            $paginator = new Paginator($query);
            $result['total'] = count($paginator);
        }

        $result['list'] = $query->getResult();

        $this->dispatchEvent(new RepositoryEvent(
            RepositoryEvents::AFTER_SEARCH_BY_FILTER,
            $this,
            null
        ));

        return $result;
    }

    public function getReference(int $id)
    {
        return $this->getEntityManager()->getReference($this->repositoryName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, bool $flush = true)
    {
        $this->dispatchEvent(new RepositoryEvent(RepositoryEvents::BEFORE_REMOVE, $this , $data));

        $this->getEntityManager()->remove($data);
        if($flush) $this->flush();

        $this->dispatchEvent(new RepositoryEvent(RepositoryEvents::AFTER_REMOVE, $this, $data));

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, bool $flush=false)
    {
        $this->dispatchEvent(new RepositoryEvent(RepositoryEvents::BEFORE_PERSIST, $this, $data));

        $this->getEntityManager()->persist($data);
        if($flush) $this->flush();

        $this->dispatchEvent(new RepositoryEvent(RepositoryEvents::AFTER_PERSIST,$this, $data));

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters() : array
    {
        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName() : string
    {
        return $this->entityName;
    }

    private function addPropertyFilter($value, DoctrineQueryBuilder $qb, string $property, string $propertyType) : DoctrineQueryBuilder
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

    /**
     * @param RepositoryEvent $event
     */
    private function dispatchEvent(RepositoryEvent $event)
    {
        if ($this->dispatcher) {
            $this->dispatcher->dispatch($event->getName(), $event);// new RepositoryEvent($name, $obj, $data));
        }
    }
}
