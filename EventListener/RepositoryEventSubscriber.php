<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Opstalent\ApiBundle\Exception\ColumnNotDefinedException;
use Opstalent\ApiBundle\Repository\BaseRepository;
use Opstalent\ApiBundle\Resolver\ColumnTypeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class RepositoryEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ColumnTypeResolver
     */
    protected $columnTypeResolver;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            RepositoryEvents::BEFORE_SEARCH_BY_FILTER => [
                ['prepareLimit', 200],
                ['prepareOffset', 200],
                ['prepareOrder', 200],
                ['buildFilters', 150],
                ['buildPropertyFilters', 145],
            ],
        ];
    }

    /**
     * @param ColumnTypeResolver
     */
    public function __construct(ColumnTypeResolver $columnResolver)
    {
        $this->columnTypeResolver = $columnResolver;
    }

    /**
     * If limit is not fount BaseRepository::DEFAULT_LIMIT is used.
     * @param RepositorySearchEvent $event
     *
     */
    public function prepareLimit(RepositorySearchEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('limit', $data)) {
            if($data['limit'] > 1000){
               $data['limit'] = 1000;
            }           
            
            $event->getQueryBuilder()->setLimit($data['limit']);

            unset($data['limit']);

            $event->setData($data);
        } else {
            $event->getQueryBuilder()->setLimit(BaseRepository::DEFAULT_LIMIT);
        }
    }

    /**
     * @param RepositorySearchEvent $event
     */
    public function prepareOffset(RepositorySearchEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('offset', $data)) {
            $event->getQueryBuilder()->setOffset($data['offset']);

            unset($data['offset']);

            $event->setData($data);
        }
    }

    /**
     * @param RepositorySearchEvent $event
     */
    public function prepareOrder(RepositorySearchEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('order', $data) && array_key_exists('orderBy', $data)) {
            $event->getQueryBuilder()->setOrder($data['order'], $data['orderBy']);

            unset($data['order']);
            unset($data['orderBy']);

            $event->setData($data);
        }
    }

    /**
     * @param RepositorySearchEvent $event
     */
    public function buildFilters(RepositorySearchEvent $event)
    {
        $repository = $event->getRepository();
        $filters = $repository->getFilters();
        $data = $event->getData();

        foreach ($data as $filter => $value) {
            if (!array_key_exists($filter, $filters)) {
                continue;
            }

            $callback = [$repository, $filters[$filter]];
            call_user_func($callback, $value, $event->getQueryBuilder());

            unset($data[$filter]);
        }

        $event->setData($data);
    }

    /**
     * @param RepositorySearchEvent $event
     */
    public function buildPropertyFilters(RepositorySearchEvent $event)
    {
        $data = $event->getData();
        $repository = $event->getRepository();

        foreach ($data as $filter => $value) {
            try {
                $propertyType = $this->columnTypeResolver->resolve($repository->getEntityName(), $filter);
                $event->getQueryBuilder()->filter($filter, $propertyType, $value);

                unset($data[$filter]);
            } catch (ColumnNotDefinedException $exception) {
                continue;
            }
        }

        $event->setData($data);
    }
}
