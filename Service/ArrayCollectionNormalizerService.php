<?php

namespace Opstalent\ApiBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Doctrine\Common\Collections\Collection;

/**
 * @author Tomasz Piasecki <tpiasecki85@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ArrayCollectionNormalizerService extends AbstractNormalizer
{

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->serializer->normalize(array_values($object->toArray()), $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {

        throw new \Exception(sprintf('Method "%s" not implemented', __METHOD__));
    }
}
