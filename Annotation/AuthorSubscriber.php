<?php
namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class AuthorSubscriber extends Annotation
{
    public $subscribe;

}