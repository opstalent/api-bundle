<?php

namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotation\Annotation;
use Doctrine\Common\Annotation\Annotation\Enum;
use Doctrine\Common\Annotation\Annotation\Required;
use Doctrine\Common\Annotation\Annotation\Target;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class Serializable implements AnnotationInterface
{
    /**
     * @var string
     */
    public $format = 'json';

    /**
     * @var string
     *
     * @Enum({"list", "get"})
     * @Required
     */
    public $method;

    /**
     * {@inheritdoc}
     */
    public function getAliasName() : string
    {
        return 'serializable';
    }
}
