<?php

namespace Opstalent\ApiBundle\Event;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
final class RepositoryEvents
{
    const BEFORE_SEARCH_BY_FILTER = "api_repository.search_by_filter.before";
    const AFTER_SEARCH_BY_FILTER = "api_repository.search_by_filter.after";

    const BEFORE_PERSIST = "api_repository.persist.before";
    const AFTER_PERSIST = "api_repository.persist.after";

    const BEFORE_REMOVE = "api_repository.remove.before";
    const AFTER_REMOVE = "api_repository.remove.after";
}
