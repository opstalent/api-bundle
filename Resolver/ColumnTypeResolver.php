<?php

namespace Opstalent\ApiBundle\Resolver;

use Doctrine\ORM\EntityManager;
use Opstalent\ApiBundle\Exception\ColumnNotDefinedException;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 */
class ColumnTypeResolver
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->manager = $em;
    }

    /**
     * @param string $entity
     * @param string $column
     * @return string
     * @throws ColumnNotDefinedException
     */
    public function resolve(string $entity, string $column) : string
    {
        $metadata = $this->manager->getClassMetadata($entity);
        if (!array_key_exists($column, $metadata->fieldMappings)) {
            throw new ColumnNotDefinedException($entity, $column);
        }

        return $metadata->fieldMappings[$column]['type'];
    }
}
