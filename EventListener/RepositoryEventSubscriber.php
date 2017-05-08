<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Event\RepositoryEvents;
use Opstalent\ApiBundle\Event\RepositorySearchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class RepositoryEventSubscriber implements EventSubscriberInterface
{
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
            ],
        ];
    }

    /**
     * @param RepositoryEvent $event
     */
    public function prepareLimit(RepositorySearchEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('limit', $data)) {
            $event->getQueryBuilder()->setLimit($data['limit']);

            unset($data['limit']);

            $event->setData($data);
        }
    }

    /**
     * @param RepositoryEvent $event
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
     * @param RepositoryEvent $event
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
     * @param RepositoryEvent $event
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
}
