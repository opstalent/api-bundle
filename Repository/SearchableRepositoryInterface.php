<?php

namespace Opstalent\ApiBundle\Repository;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
interface SearchableRepositoryInterface extends RepositoryInterface
{
    /**
     * @param int $limit
     */
    public function setLimit(int $limit);

    /**
     * @param int $offset
     */
    public function setOffset(int $offset);

    /**
     * @param string $order
     * @param string $orderBy
     */
    public function setOrder(string $order, string $orderBy);

    /**
     * @param array $filters
     * @return array
     */
    public function searchByFilters(array $data) : array;
}
