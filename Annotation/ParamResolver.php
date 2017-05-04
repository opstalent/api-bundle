<?php

namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotation\Annotation;
use Doctrine\Common\Annotation\Annotation\Target;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class ParamResolver implements AnnotationInterface
{
    /**
     * @var string
     */
    public $queryParam = 'id';

    /**
     * @var string
     */
    public $methodParam = 'entity';

    /**
     * @var string
     */
    public $entityField = 'id';

    /**
     * {@inheritdoc}
     */
    public function getAliasName() : string
    {
        return 'param_resolver';
    }
}
