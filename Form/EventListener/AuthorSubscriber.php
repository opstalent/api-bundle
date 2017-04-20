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
    private $userFieldName;

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
     * @param string $field
     */
    public function __construct(TokenStorageInterface $tokenStorage, string $field)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userFieldName = $field;
    }

    /**
     * @param FormEvent $event
     */
    public function assignAuthor(FormEvent $event)
    {
        $data = $event->getData();
        $user = $this->tokenStorage->getToken()->getUser();
        $propertyAccess = PropertyAccess::createPropertyAccessor();

        $propertyAccess->setValue($data, $this->userFieldName, $user);
        $event->setData($data);
    }
}
