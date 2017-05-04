<?php

namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotation\Annotation;
use Doctrine\Common\Annotation\Annotation\Required;
use Doctrine\Common\Annotation\Annotation\Target;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class Response implements AnnotationInterface
{
    /**
     * @var string
     *
     * @Required
     */
    protected $class;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (!class_exists($values['class'])) {
            throw new \LogicException(sprintf(
                'Class "%s" defined in %s annotation does not exist',
                $values['class'],
                static::class
            ));
        }

        if (!is_subclass_of($values['class'], HttpResponse::class)) {
            throw new \LogicException(sprintf(
                'Class "%s" defined in %s annotation is not subclass of "%s"',
                $values['class'],
                static::class,
                HttpResponse::class
            ));
        }

        $this->class = $values['class'];
    }

    /**
     * @return string
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliasName() : string
    {
        return 'response_transformer';
    }
}
