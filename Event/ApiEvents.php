<?php

namespace Opstalent\ApiBundle\Event;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
final class ApiEvents
{
    /**
     * @Event("Opstalent\ApiBundle\Event\ApiEvent")
     */
    const POST_HANDLE_REQUEST = 'opstalent.api.post_handle_request';
}
