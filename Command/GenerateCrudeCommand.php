<?php
/**
 * Created by PhpStorm.
 * User: good.vibes.development@gmail.com
 * Date: 2016-10-20
 * Time: 13:07
 */

namespace Opstalent\ApiBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Opstalent\ApiBundle\Util\FormGenerator;
use Opstalent\ApiBundle\Util\RepositoryGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\User;
use ReflectionClass;
use ReflectionProperty;
use ReflectionObject;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Opstalent\ApiBundle\Util\Pluralizer;
use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;


class GenerateCrudeCommand extends ContainerAwareCommand
{

    protected $ignore = ['AppBundle\Entity\User', 'AppBundle\Entity\AuthCode', 'AppBundle\Entity\AccessToken', 'AppBundle\Entity\RefreshToken', 'AppBundle\Entity\Client'];


    protected function configure()
    {
        $this
            ->setName('app:generatecrude')
            ->setDescription('Generate crude files')
            ->setHelp('This command allows you to fast fill database with data. Enjoy!');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $entities = $this->getEntitiesNames();
            $this->createApiRoutes($entities); // main route file
            $this->createRepositoriesYaml($entities);

            $formGenerator = new FormGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('kernel')->getRootDir());
            $repositoryGenerator = new RepositoryGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('kernel')->getRootDir());

            foreach ($entities as $entity) {
                var_dump($entity);
                // entitie routings
//            $yamlArray = null;
                $className = $this->getClassName($entity);
                $yamlArray = $this->createRoutes($entity);

//            if ($yamlArray) {
                $yaml = Yaml::dump($yamlArray, 2);
                file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . strtolower(Pluralizer::pluralize($className)) . '.yml', $yaml);

//            }
                // FilterForms
                $metadata = $this->getEntityMetadata($entity);
                $formGenerator->generate($entity, $metadata[0], $className, 'Filter');
                $formGenerator->generate($entity, $metadata[0], $className, 'Add');
                $formGenerator->generate($entity, $metadata[0], $className, 'Edit');

                // repositories

                $repositoryGenerator->generate($entity, $metadata[0], $className);
//            dump($metadata);
//            exit;


            }

            dump('koniec');
            exit;
            $reader = new AnnotationReader();
            $reflectionClass = new ReflectionClass($entity);
            $classAnnotations = $reader->getClassAnnotations($reflectionClass);

//        $reflectionObject = new ReflectionObject($user);
//        $objectAnnotations = $reader->getClassAnnotations($reflectionObject);

            dump($classAnnotations);


            $cols = $this->getContainer()->get('doctrine.orm.entity_manager')->getClassMetadata(get_class($user))->getColumnNames();
//        $cols = $this->getContainer()->get('')

//        dump($cols);
//        exit;
            foreach ($cols as $property) {
                dump($property);
                try {
                    $reflectionProperty = new ReflectionProperty('AppBundle\Entity\User', $property);
                } catch (\ReflectionException $e) {
                    echo($e->getMessage());
                }
                $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);
                dump($propertyAnnotations);
            }

        } catch (Exception $e) {
            var_dump($e->getTraceAsString());
        }


    }

    private function getEntitiesNames()
    {
        $entities = [];
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $meta = $em->getMetadataFactory()->getAllMetadata();
        /** @var ClassMetadata $entity */
        foreach ($meta as $entity) {
            if (strpos($entity->getName(), 'AppBundle\Entity') === 0 && !in_array($entity->getName(), $this->ignore)) $entities[] = $entity->getName();
        }
        return $entities;
    }

    private function createRepositoriesYaml($entities)
    {

//        $value = Yaml::parse(file_get_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/services.yml'));
//        dump($value);
//        exit;

        $yamlArray = [];
        foreach ($entities as $key => $entity) {
            $className = $this->getClassName($entity);
            $arrayParameters['entity.' . strtolower($className)] = $entity;
            $arrayRepositories['repository.' . strtolower($className)] = ['class' => 'AppBundle\Repository\\' . $className . 'Repository',
                'factory' => ["@doctrine", 'getRepository'],
                'arguments' => ["%entity." . strtolower($className) . '%'],
                'calls' => [0 => ["setEventDispatcher", ['@event_dispatcher']]],

            ];
//            $this->createRepositoryFile($entity);

        }
        $yamlArray['parameters'] = $arrayParameters;
        $yamlArray['services'] = $arrayRepositories;
//        dump($yamlArray);
//        exit;
        $yaml = Yaml::dump($yamlArray);
        file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/repositories.yml', $yaml);
    }

    private function createApiRoutes($entities)
    {
        $yamlArray = [];
        foreach ($entities as $key => $entity) {
            $className = $this->getClassName($entity);
            $pluralClassName = strtolower(Pluralizer::pluralize($className));
            $array = [$pluralClassName => ['resource' => 'routing/' . $pluralClassName . '.yml']];
            $yamlArray = array_merge($yamlArray, $array);
        }
        $yaml = Yaml::dump($yamlArray);
        file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/api_routes.yml', $yaml);
    }

    private function createRepository($entity)
    {

    }


    private function createRoutes($entity)
    {
        $yamlArray = [];
        $listArray = $this->generateListRoute($entity);
        $yamlArray = array_merge($yamlArray, $listArray);
        $getArray = $this->generateGetRoute($entity);
        $yamlArray = array_merge($yamlArray, $getArray);
        $postArray = $this->generatePostRoute($entity);
        $yamlArray = array_merge($yamlArray, $postArray);
        $postArray = $this->generatePutRoute($entity);
        $yamlArray = array_merge($yamlArray, $postArray);
        $postArray = $this->generateDeleteRoute($entity);
        $yamlArray = array_merge($yamlArray, $postArray);
        return $yamlArray;

    }

    private function getClassName($entity)
    {
        return substr($entity, strrpos($entity, '\\') + 1);
    }

    private function generateListRoute($entity)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['api_' . $pluralClassName . '_list' =>
            ['path' => '/' . $pluralClassName,
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:list'],
                'methods' => ['GET'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\FilterType",
                    'serializerGroup' => 'list',
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }

    private function generateGetRoute($entity)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['api_' . $pluralClassName . '_get' =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:get'],
                'methods' => ['GET'],
                'options' => [
                    'serializerGroup' => 'get',
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }

    private function generatePostRoute($entity)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['api_' . $pluralClassName . '_post' =>
            ['path' => '/' . $pluralClassName,
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:post'],
                'methods' => ['POST'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\AddType",
                    'serializerGroup' => 'get',
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }

    private function generatePutRoute($entity)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['api_' . $pluralClassName . '_put' =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:put'],
                'methods' => ['PUT'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\EditType",
                    'serializerGroup' => 'get',
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }


    private function generateDeleteRoute($entity)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['api_' . $pluralClassName . '_delete' =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:delete'],
                'methods' => ['DELETE'],
                'options' => [
                    'serializerGroup' => 'get',
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }

    protected function getEntityMetadata($entity)
    {
        $factory = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));

//        dump($factory->getClassMetadata($entity));
//        exit;

        return $factory->getClassMetadata($entity)->getMetadata();
    }


}


