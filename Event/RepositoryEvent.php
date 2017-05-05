<?php

namespace Opstalent\ApiBundle\Event;

use Opstalent\ApiBundle\Repository\RepositoryInterface;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    protected $repository;
    protected $data;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     * @param RepositoryInterface $repository
     * @param mixed $data
     */
    public function __construct(string $name, RepositoryInterface $repository, $data = null)
    {
        $this->name = $name;
        $this->repository = $repository;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
