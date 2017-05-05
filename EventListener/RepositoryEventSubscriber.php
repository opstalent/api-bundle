<?php

namespace Opstalent\ApiBundle\EventListener;

use Opstalent\ApiBundle\Event\RepositoryEvent;
use Opstalent\ApiBundle\Event\RepositoryEvents;
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
            ],
        ];
    }

    /**
     * @param RepositoryEvent $event
     */
    public function prepareLimit(RepositoryEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('limit', $data)) {
            $event->getRepository()->setLimit($data['limit']);

            unset($data['limit']);

            $event->setData($data);
        }
    }

    /**
     * @param RepositoryEvent $event
     */
    public function prepareOffset(RepositoryEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('offset', $data)) {
            $event->getRepository()->setOffset($data['offset']);

            unset($data['offset']);

            $event->setData($data);
        }
    }

    /**
     * @param RepositoryEvent $event
     */
    public function prepareOrder(RepositoryEvent $event)
    {
        $data = $event->getData();
        if (array_key_exists('order', $data) && array_key_exists('orderBy', $data)) {
            $event->getRepository()->setOrder($data['order'], $data['orderBy']);

            unset($data['order']);
            unset($data['orderBy']);

            $event->setData($data);
        }
    }
}
