<?php

namespace Opstalent\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class CollectionItemTransformerWrapper implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface
     */
    private $transformer;

    /**
     * @param DataTransformerInterface
     */
    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function transform($object)
    {
        if (null === $object) {
            return;
        }

        if (!(is_array($object) || $object instanceof \Traversable)) {
            throw new \InvalidArgumentException(sprintf(
                'Traversable element expected, %s given',
                is_object($object) ? get_class($object) : gettype($object)
            ));
        }

        $result = [];
        foreach ($object as $key => $item) {
            $result[$key] = $this->transformer->transform($item);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function reverseTransform($object)
    {
        if (!(is_array($object) || $object instanceof \Traversable)) {
            throw new \InvalidArgumentException(sprintf(
                'Traversable element expected, %s given',
                is_object($object) ? get_class($object) : gettype($object)
            ));
        }

        $result = [];
        foreach ($object as $key => $item) {
            $result[$key] = $this->transformer->reverseTransform($item);
        }

        return $result;
    }
}
