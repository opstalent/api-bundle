<?php

namespace Opstalent\ApiBundle\Event;

use Opstalent\ApiBundle\Repository\BaseRepository;
use Symfony\Component\EventDispatcher\Event;

class RepositoryEvent extends Event
{
    protected $repository;
    protected $data;

    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name, BaseRepository $repository, $data = null)
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
}
