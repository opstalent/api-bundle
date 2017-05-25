<?php

namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotation\Annotation;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 *
 * @Annotation
 * @Annotation\Target({"CLASS", "METHOD"})
 */
class LifecycleEvents implements AnnotationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAliasName() : string
    {
        return 'lifecycle';
    }
}

