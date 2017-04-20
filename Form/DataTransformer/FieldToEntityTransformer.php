<?php

namespace Opstalent\ApiBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class FieldToEntityTransformer
 * @package Opstalent\ApiBundle
 */
class FieldToEntityTransformer implements DataTransformerInterface
{
    private $manager;
    private $field;
    private $entityClass;

    /**
     * FieldToEntityTransformer constructor.
     * @param EntityManager $manager
     * @param string $field
     * @param string $entityClass
     */
    public function __construct(EntityManager $manager, string $entityClass, string $field = "id")
    {
        $this->manager = $manager;
        $this->field = $field;
        $this->entityClass = $entityClass;
    }


    /**
     * @param mixed $object
     * @return string
     */
    public function transform($object)
    {
        if (null === $object) {
            return '';
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        return $propertyAccessor->getValue($object, $this->field);
    }


    /**
     * @param mixed $fieldValue
     * @return null|object
     */
    public function reverseTransform($fieldValue)
    {
        if (!$fieldValue) {
            return null;
        }
        if(is_array($fieldValue)) return array_map([$this,'reverseTransform'], $fieldValue);
        if($this->field === 'id') {
            $object = $this->manager->getReference($this->entityClass, $fieldValue);
        } else {
            $object = $this->manager
                ->getRepository($this->entityClass)
                ->findOneBy([$this->field => $fieldValue]);
        }


        if (null === $object) {
            throw new TransformationFailedException(sprintf(
                "Data does not exist!",
                $fieldValue
            ));
        }

        return $object;
    }
}
