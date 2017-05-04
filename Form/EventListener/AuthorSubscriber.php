<?php

namespace Opstalent\ApiBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class AuthorSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string|null
     */
    private $source;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'assignAuthor',
        ];
    }

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string $target
     * @param string $source
     */
    public function __construct(TokenStorageInterface $tokenStorage, string $target, $source = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->target = $target;
        $this->source = $source;
    }

    /**
     * @param FormEvent $event
     */
    public function assignAuthor(FormEvent $event)
    {
        $data = $event->getData();
        $user = $this->tokenStorage->getToken()->getUser();
        $propertyAccess = PropertyAccess::createPropertyAccessor();

        if (null === $this->source) {
            $item = $user;
        } else {
            $item = $propertyAccess->getValue($user, $this->source);
        }

        $propertyAccess->setValue($data, $this->target, $item);
        $event->setData($data);
    }
}
