<?php

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
namespace Opstalent\ApiBundle\Tests\Utility;

interface OwnableInterface
{
    /**
     * @return mixed
     */
    public function getOwner();
}
