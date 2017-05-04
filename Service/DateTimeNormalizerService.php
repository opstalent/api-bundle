<?php

namespace Opstalent\ApiBundle\Service;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class DateTimeNormalizerService extends AbstractNormalizer
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @param string $format
     */
    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type == \DateTime::class && false !== \DateTime::createFromFormat($this->format, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $object->format($this->format);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return \DateTime::createFromFormat($this->format, $data);
    }
}
