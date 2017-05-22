<?php

namespace Opstalent\ApiBundle\Service;

use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ObjectNormalizerService extends ObjectNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object);
        }

        $data = array();
        $stack = array();
        $attributes = $this->getAttributes($object, $format, $context);
        $class = get_class($object);
        $attributesMetadata = $this->classMetadataFactory ? $this->classMetadataFactory->getMetadataFor($class)->getAttributesMetadata() : null;

        foreach ($attributes as $attribute) {
            $attributeValue = $this->getAttributeValue($object, $attribute, $format, $context);

            if (isset($this->callbacks[$attribute])) {
                $attributeValue = call_user_func($this->callbacks[$attribute], $attributeValue);
            }

            if (null !== $attributeValue && !is_scalar($attributeValue)) {
                $stack[$attribute] = $attributeValue;
            }

            $data = $this->updateData($data, $attribute, $attributeValue);
        }

        if (!empty($stack) && !$this->serializer instanceof NormalizerInterface) {
            throw new LogicException(sprintf('Cannot normalize subobjects because the injected serializer is not a normalizer'));
        }

        foreach ($stack as $attribute => $attributeValue) {
            if ($this->isMaxDepthReached($context)) {
                unset($data[$attribute]);
                continue;
            }

            if (null !== $attributesMetadata && isset($attributesMetadata[$attribute])) {
                $stackContext = $this->buildStackContext($attributesMetadata[$attribute], $context);
            }

            $data = $this->updateData($data, $attribute, $this->serializer->normalize($attributeValue, $format, $stackContext));
        }

        return $data;
    }

    /**
     * @param AttributeMetadata $metadata
     * @param array $context
     * @return array
     */
    protected function buildStackContext(AttributeMetadata $metadata, array $context = [])
    {
        if (array_key_exists('depth_remaining', $context)) {
            --$context['depth_remaining'];
        } elseif ($metadata->getMaxDepth()) {
            $context['depth_remaining'] = $metadata->getMaxDepth();
        }

        return $context;
    }

    /**
     * @see \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::validateAndDenormalize()
     */
    private function validateAndDenormalize($currentClass, $attribute, $data, $format, array $context)
    {
        if (null === $this->propertyTypeExtractor || null === $types = $this->propertyTypeExtractor->getTypes($currentClass, $attribute)) {
            return $data;
        }

        $expectedTypes = array();
        foreach ($types as $type) {
            if (null === $data && $type->isNullable()) {
                return;
            }

            if ($type->isCollection() && null !== ($collectionValueType = $type->getCollectionValueType()) && Type::BUILTIN_TYPE_OBJECT === $collectionValueType->getBuiltinType()) {
                $builtinType = Type::BUILTIN_TYPE_OBJECT;
                $class = $collectionValueType->getClassName().'[]';

                if (null !== $collectionKeyType = $type->getCollectionKeyType()) {
                    $context['key_type'] = $collectionKeyType;
                }
            } else {
                $builtinType = $type->getBuiltinType();
                $class = $type->getClassName();
            }

            $expectedTypes[Type::BUILTIN_TYPE_OBJECT === $builtinType && $class ? $class : $builtinType] = true;

            if (Type::BUILTIN_TYPE_OBJECT === $builtinType) {
                if (!$this->serializer instanceof DenormalizerInterface) {
                    throw new LogicException(sprintf('Cannot denormalize attribute "%s" for class "%s" because injected serializer is not a denormalizer', $attribute, $class));
                }

                if ($this->serializer->supportsDenormalization($data, $class, $format)) {
                    return $this->serializer->denormalize($data, $class, $format, $context);
                }
            }

            // JSON only has a Number type corresponding to both int and float PHP types.
            // PHP's json_encode, JavaScript's JSON.stringify, Go's json.Marshal as well as most other JSON encoders convert
            // floating-point numbers like 12.0 to 12 (the decimal part is dropped when possible).
            // PHP's json_decode automatically converts Numbers without a decimal part to integers.
            // To circumvent this behavior, integers are converted to floats when denormalizing JSON based formats and when
            // a float is expected.
            if (Type::BUILTIN_TYPE_FLOAT === $builtinType && is_int($data) && false !== strpos($format, JsonEncoder::FORMAT)) {
                return (float) $data;
            }

            if (call_user_func('is_'.$builtinType, $data)) {
                return $data;
            }
        }

        throw new UnexpectedValueException(sprintf('The type of the "%s" attribute for class "%s" must be one of "%s" ("%s" given).', $attribute, $currentClass, implode('", "', array_keys($expectedTypes)), gettype($data)));
    }

    /**
     * @see \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::updateData()
     */
    private function updateData(array $data, $attribute, $attributeValue)
    {
        if ($this->nameConverter) {
            $attribute = $this->nameConverter->normalize($attribute);
        }

        $data[$attribute] = $attributeValue;

        return $data;
    }

    /**
     * @param array $context
     * @return bool
     */
    private function isMaxDepthReached(array $context)
    {
        if (array_key_exists('depth_remaining', $context) && 1 === $context['depth_remaining']) {
            return true;
        }

        return false;
    }

    /**
     * @see \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::getCacheKey()
     */
    private function getCacheKey($format, array $context)
    {
        try {
            return md5($format.serialize($context));
        } catch (\Exception $exception) {
            // The context cannot be serialized, skip the cache
            return false;
        }
    }
}
