<?php

namespace Opstalent\ApiBundle\Repository;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
interface SearchableRepositoryInterface extends RepositoryInterface
{
    /**
     * @param array $filters
     * @return array
     */
    public function searchByFilters(array $data) : array;
}
