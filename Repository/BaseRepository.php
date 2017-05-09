<?php

namespace Opstalent\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Opstalent\ApiBundle\Event\RepositoryEvent;
use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Opstalent\ApiBundle\QueryBuilder\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BaseRepository extends EntityRepository implements
    PersistableRepositoryInterface,
    SearchableRepositoryInterface
{
    protected $filters = [];
    protected $repositoryName='';
    protected $repositoryAlias='';
    protected $entityName='';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


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
