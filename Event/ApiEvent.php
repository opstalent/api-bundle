<?php

namespace Opstalent\ApiBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ApiEvent extends Event
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @param Request $request
     * @param FormInterface $form
     */
    public function __construct(Request $request, FormInterface $form)
    {
        $this->request = $request;
        $this->form = $form;
    }

    /**
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * @return FormInterface
     */
    public function getForm() : FormInterface
    {
        return $this->form;
    }
}

