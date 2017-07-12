<?php
namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class RoutingOptions extends Annotation
{
    public $ownerable;

    public $routes;

    public $roles;

}