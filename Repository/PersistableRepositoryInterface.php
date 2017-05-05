<?php

namespace Opstalent\ApiBundle\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
interface PersistableRepositoryInterface extends ObjectRepository, RepositoryInterface
{
    /**
     * @param mixed $data
     * @param bool $flush
     * @return mixed
     */
    public function remove($data, bool $flush = true);

    public function flush();

    /**
     * @param mixed $data
     * @param bool $flush
     * @return mixed
     */
    public function persist($data, bool $flush = false);

}
