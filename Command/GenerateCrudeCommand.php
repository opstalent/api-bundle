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

class GenerateCrudeCommand extends ContainerAwareCommand
{

    protected $ignore = ['AppBundle\Entity\AuthCode', 'AppBundle\Entity\AccessToken', 'AppBundle\Entity\RefreshToken', 'AppBundle\Entity\Client'];


    protected function configure()
    {
        $this
            ->setName('app:generatecrude')
            ->setDescription('Generate crude files')
            ->setHelp('This command allows you to fast fill database with data. Enjoy!');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = $this->getEntitiesNames();

        if (file_exists($this->getContainer()->get('kernel')->getRootDir() . '/config/api_routes.yml')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('api_routes.yml allready exists, overwrite ?', false);
            if ($helper->ask($input, $output, $question)) {
                $this->createApiRoutes($entities);
            }
        } else {
            $this->createApiRoutes($entities);
        }

        foreach ($entities as $entity) {
            $yamlArray = null;
            $className = $this->getClassName($entity);
            if (file_exists($this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . strtolower(Pluralizer::pluralize($className)) . '.yml')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion($className . '.yml allready exists, overwrite ?', false);
                if ($helper->ask($input, $output, $question)) {
                    $yamlArray = $this->createRoutes($entity);
                }
            } else {
                $yamlArray = $this->createRoutes($entity);
            }
            if ($yamlArray) {
                $yaml = Yaml::dump($yamlArray, 2);
                file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . strtolower(Pluralizer::pluralize($className)) . '.yml', $yaml);

            }
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

        exit;


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

    private function createRoutes($entity)
    {
        $yamlArray = [];
        $listArray = $this->generateListRoute($entity);
        $yamlArray = array_merge($yamlArray, $listArray);
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
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => ['ROLE_SUPER_ADMIN']
                    ]]]];

        return $array;
    }

}


