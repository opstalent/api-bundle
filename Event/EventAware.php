<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
abstract class EventAware extends Event
{
    /**
     * @var Event
     */
    protected $event;

    /**
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * @return Event
     */
    public function getEvent() : Event
    {
        return $this->event;
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped()
    {
        return $this->event->isPropagationStopped();
    }

    /**
     * {@inheritdoc}
     */
    public function stopPropagation()
    {
        $this->event->stopPropagation();
    }
}
