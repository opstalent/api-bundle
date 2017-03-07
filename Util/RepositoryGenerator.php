<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Opstalent\ApiBundle\Util;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;

/**
 * Generates a form class based on a Doctrine entity.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Hugo Hamon <hugo.hamon@sensio.com>
 */
class RepositoryGenerator extends Generator
{
    private $filesystem;
    private $className;
    private $classPath;
    private $rootDir;

//    private $map = ['string' => "TextType", 'integer' => "NumberType", '2' => 'EntityType'];


    /**
     * Constructor.
     *
     * @param Filesystem $filesystem A Filesystem instance
     */
    public function __construct(Filesystem $filesystem, $rootDir)
    {
        $this->filesystem = $filesystem;
        $this->rootDir = $rootDir;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getClassPath()
    {
        return $this->classPath;
    }

    /**
     * Generates the entity form class.
     *
     * @param BundleInterface $bundle The bundle in which to create the class
     * @param string $entity The entity relative class name
     * @param ClassMetadataInfo $metadata The entity metadata class
     * @param bool $forceOverwrite If true, remove any existing form class before generating it again
     */
    public function generate($entity, ClassMetadataInfo $metadata, $className)
    {
        $dirPath = $this->rootDir . '/../src/AppBundle/Repository/' . $className . 'Repository.php';
//        $this->classPath = $dirPath.'/'.str_replace('\\', '/', $entity).'Type.php';

        $this->setSkeletonDirs($this->getSkeletonDirs());


        $this->renderFile('repository.php.twig', $dirPath, array(
//            'fields' => $this->getFieldsFromMetadata($metadata),
            'namespace' => 'AppBundle',
            'entity_namespace' => 'Entity',
            'entity_class' => $className,
            'repository_class' => $className . 'Repository',
            'entity_path' => $entity
//            // BC with Symfony 2.7
//            'get_name_required' => !method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix'),
        ));
    }

    /**
     * Returns an array of fields. Fields can be both column fields and
     * association fields.
     *
     * @param ClassMetadataInfo $metadata
     *
     * @return array $fields
     */
    private function getFieldsFromMetadata(ClassMetadataInfo $metadata)
    {
        $fields = (array)$metadata->fieldNames;
//        var_dump($fields);


        // Remove the primary key field if it's not managed manually
        if (!$metadata->isIdentifierNatural()) {
            $fields = array_diff($fields, $metadata->identifier);
        }

//        foreach ($metadata->associationMappings as $fieldName => $relation) {
//            if ($relation['type'] !== ClassMetadataInfo::ONE_TO_MANY) {
//                $fields[$fieldName] = $fieldName;
//            }
//        }
//        dump($fields);
//        exit;
//
//        dump($metadata);


        foreach ($fields as $field) {
            if (array_key_exists($field, $metadata->fieldMappings)) {
                $fields[$field] = $this->map[$metadata->fieldMappings[$field]['type']];
            }

        }
//        dump($fields);
//        exit;
        return $fields;
    }

    protected function getSkeletonDirs()
    {
        $skeletonDirs = array();
        $skeletonDirs[] = __DIR__ . '/../Resources/skeleton';
        $skeletonDirs[] = __DIR__ . '/../Resources';

        return $skeletonDirs;
    }

}
