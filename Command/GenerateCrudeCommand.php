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
use phpDocumentor\Reflection\Types\Boolean;
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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\Common\Annotations\AnnotationRegistry;


class GenerateCrudeCommand extends ContainerAwareCommand
{

//    protected $ignore = ['AppBundle\Entity\User', 'AppBundle\Entity\AuthCode', 'AppBundle\Entity\AccessToken', 'AppBundle\Entity\RefreshToken', 'AppBundle\Entity\Client'];
    protected $ignore;
    protected $overWrite;
    protected $actions;
    private $formMapPath = ['FilterType.php' => 'filterFormType.twig', 'AddType.php' => 'addFormType.twig', 'EditType.php' => 'editFormType.twig'];
    private $map = ['array' => 'TextType', 'string' => "TextType", 'integer' => "NumberType", '2' => 'EntityType', 'datetime' => 'DateTimeType', 'boolean' => 'CheckboxType', 'text' => 'TextType', 'float' => 'NumberType'];
    protected $skeletonDirs = [__DIR__ . '/../Resources/skeleton', __DIR__ . '/../Resources'];
    private static $output;

    protected function configure()
    {
        $this
            ->setName('app:generatecrude')
            ->setDescription('Generate crude files')
            ->addArgument('entityPath', InputArgument::OPTIONAL, 'The entity name.', 'ALL')
            ->addArgument('overwrite', InputArgument::OPTIONAL, 'Overwrite Y/N', 'N')
            ->addArgument('actions', InputArgument::IS_ARRAY, 'Available actions [LIST,GET,POST,PUT,DELETE]', ['LIST', 'GET', 'POST', 'PUT', 'DELETE'])
            ->setHelp('This command allows you to fast fills database with data. Enjoy!');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $config = $value = Yaml::parse(file_get_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/config.yml'));


        $this->ignore = $this->getContainer()->getParameter('opstalent_api.generator.ignore');
        $this->overWrite = $input->getArgument('overwrite');
        $this->actions = $input->getArgument('actions');
        if ($input->getArgument('overwrite') == 'Y') $this->overWrite = true;
        if ($input->getArgument('overwrite') == 'N') $this->overWrite = false;

        try {
            AnnotationRegistry::registerLoader('class_exists');

            if (strtoupper($input->getArgument('entityPath')) === 'ALL') {
                $entityPaths = $this->getEntitiesPaths();
            } else {
                $entityPaths = [$input->getArgument('entityPath')];
            }

            $this->createApiRoutes($entityPaths); // main route file
            $this->createRepositoriesYml($entityPaths); // repositories.yml

            foreach ($entityPaths as $entityPath) {
                $className = $this->getClassName($entityPath);
                $this->createApiRouteFile($entityPath);
                $metadata = $this->getEntityMetadata($entityPath);

                if ($this->overWrite) {
                    $this->generateFormType($metadata[0], $className, 'FilterType.php');
                    $this->generateFormType($metadata[0], $className, 'AddType.php');
                    $this->generateFormType($metadata[0], $className, 'EditType.php');
                    $this->generateRepository($entityPath, $className);
                } else {
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/FilterType.php')) $this->generateFormType($metadata[0], $className, 'FilterType.php');
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/AddType.php')) $this->generateFormType($metadata[0], $className, 'AddType.php');
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/EditType.php')) $this->generateFormType($metadata[0], $className, 'EditType.php');
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Repository/' . $className . 'Repository.php')) $this->generateRepository($entityPath, $className);

                }
                if ($this->overWrite) {
                    $this->editEntityFile($entityPath);
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

        } catch (Exception $e) {
            var_dump($e->getTraceAsString());
        }


    }

    public function editEntityFile(string $entityPath)
    {
        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/' . str_replace('\\', '/', $entityPath . '.php');
        $entityFile = file_get_contents($filePath);
//        $entryPosition = $entityAnnotation = strpos($entityFile, "@ORM\\Entity") + 11;
//        $repositoryString = strpos($entityFile, "repository");

        $fileArray = explode("\n", $entityFile);
        foreach ($fileArray as $key => $line) {
            if (strpos($line, "repository") != false) {
                if ($this->overWrite) {
                    $fileArray[$key] = ' * @ORM\\Entity(' . 'repositoryClass="AppBundle\Repository\\' . $this->getClassName($entityPath) . 'Repository' . '")';
                    $newFile = implode("\n", $fileArray);
                    return self::dump($filePath, $newFile);
                    break;
                }
            } elseif (strpos($line, "@ORM\\Id") != false) {
                $entryPosition = $entityAnnotation = strpos($entityFile, "@ORM\\Entity") + 11;
                $newFile = substr_replace($entityFile, '(' . 'repositoryClass="AppBundle\Repository\\' . $this->getClassName($entityPath) . 'Repository' . '")', $entryPosition, 0);
                return self::dump($filePath, $newFile);
            };
        }


    }

    private function createRepositoryClassName()
    {

    }

    public function generateRepository($entityPath, $className)
    {
        $dirPath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/AppBundle/Repository/' . $className . 'Repository.php';
        $this->renderFile('repository.php.twig', $dirPath, array(
            'namespace' => 'AppBundle',
            'entity_namespace' => 'Entity',
            'entity_class' => $className,
            'repository_class' => $className . 'Repository',
            'entity_path' => $entityPath
        ));
    }


    private function generateFormType(ClassMetadataInfo $metadata, $className, $formType)
    {

        $dirPath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/AppBundle/Form/' . $className . '/' . $formType;
        $this->renderFile($this->formMapPath[$formType], $dirPath, array(
            'fields' => $this->getFieldsFromMetadata($metadata),
            'namespace' => 'AppBundle',
            'entity_namespace' => 'Entity',
            'entity_class' => $className,
            'form_class' => $className . 'Type',
        ));

    }

    private function getFieldsFromMetadata(ClassMetadataInfo $metadata)
    {

        $fields = (array)$metadata->columnNames;
        if (!$metadata->isIdentifierNatural()) {

            $fields = array_diff($fields, $metadata->identifier);
        }

        foreach ($fields as $key => $field) {
            if (array_key_exists($key, $metadata->fieldMappings)) {
                $fields[$key] = ['type' => $this->map[$metadata->fieldMappings[$key]['type']], 'nullable' => $metadata->fieldMappings[$key]['nullable']];

            }
        }
        return $fields;
    }

    private function renderFile($template, $target, $parameters)
    {
        self::mkdir(dirname($target));

        return self::dump($target, $this->render($template, $parameters));
    }

    public static function mkdir($dir, $mode = 0777, $recursive = true)
    {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, $recursive);
            self::writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($dir)));
        }
    }


    /**
     * @internal
     */
    public static function dump($filename, $content)
    {
        if (file_exists($filename)) {
            self::writeln(sprintf('  <fg=yellow>updated</> %s', self::relativizePath($filename)));
        } else {
            self::writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($filename)));
        }

        return file_put_contents($filename, $content);
    }

    private static function writeln($message)
    {
        if (null === self::$output) {
            self::$output = new ConsoleOutput();
        }

        self::$output->writeln($message);
    }


    protected function render($template, $parameters)
    {
        $twig = $this->getTwigEnvironment();

        return $twig->render($template, $parameters);
    }

    protected function getTwigEnvironment()
    {
        return new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));
    }

    private function createApiRouteFile($entityPath)
    {
        $className = $this->getClassName($entityPath);
        $ymlArray = $this->createRoutes($entityPath);
        $yml = Yaml::dump($ymlArray, 2);
        file_put_contents($this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . strtolower(Pluralizer::pluralize($className)) . '.yml', $yml);
    }

    private function getEntitiesPaths()
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

    protected function createRepositoriesYml(array $entityPaths) : int
    {
        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/repositories.yml';
        if ($this->overWrite) {
            $ymlArray = [];
            $arrayParameters = [];
            $arrayRepositories = [];
            $modified = true;
            foreach ($entityPaths as $key => $entityPath) {
                $arrayParameters = $this->createRepositoryYmlParameterEntry($arrayParameters, $entityPath);
                $arrayRepositories = $this->createRepositoryYmlRepositoryEntry($arrayRepositories, $entityPath);
            }
        } else {
            if (file_exists($filePath)) $ymlArray = Yaml::parse(file_get_contents($filePath));
            else $ymlArray = [];
            $modified = false;
            $arrayParameters = $ymlArray['parameters'] ? $ymlArray['parameters'] : [];
            $arrayRepositories = $ymlArray['services'] ? $ymlArray['services'] : [];
            foreach ($entityPaths as $key => $entityPath) {
                $className = $this->getClassName($entityPath);
                if (!array_key_exists('entity.' . strtolower($className), $arrayParameters)) {
                    $arrayParameters = $this->createRepositoryYmlParameterEntry($arrayParameters, $entityPath);
                    $modified = true;
                }
                if (!array_key_exists('repository.' . strtolower($className), $arrayRepositories)) {
                    $arrayRepositories = $this->createRepositoryYmlRepositoryEntry($arrayRepositories, $entityPath);
                    $modified = true;
                }
            }

        }
        $ymlArray['parameters'] = $arrayParameters;
        $ymlArray['services'] = $arrayRepositories;
        if ($modified) {
            $yml = Yaml::dump($ymlArray);
            self::mkdir(dirname($filePath));
            return self::dump($filePath, $yml);
        } else return 0;


    }

    private function createRepositoryYmlRepositoryEntry($arrayRepositories, $entityPath)
    {
        $className = $this->getClassName($entityPath);
        $arrayRepositories['repository.' . strtolower($className)] = ['class' => 'AppBundle\Repository\\' . $className . 'Repository',
            'factory' => ["@doctrine", 'getRepository'],
            'arguments' => ["%entity." . strtolower($className) . '%'],
            'calls' => [0 => ["setEventDispatcher", ['@event_dispatcher']]],

        ];
        return $arrayRepositories;
    }

    private function createRepositoryYmlParameterEntry($arrayParameters, $entityPath)
    {
        $className = $this->getClassName($entityPath);
        $arrayParameters['entity.' . strtolower($className)] = $entityPath;
        return $arrayParameters;
    }

    protected function createApiRoutes(array $entityPaths) : int
    {
        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/api_routes.yml';
        if ($this->overWrite) {
            $ymlArray = [];
            $modified = true;
            foreach ($entityPaths as $key => $entityPath) {
                $ymlArray = $this->addMainRouteEntry($ymlArray, $entityPath);
            }
        } else {

            if (file_exists($filePath)) $ymlArray = Yaml::parse(file_get_contents($filePath));
            else $ymlArray = [];
            $modified = false;
            foreach ($entityPaths as $key => $entityPath) {
                $pluralClassName = $this->createPluralClassName($entityPath);
                if (!array_key_exists($pluralClassName, $ymlArray)) {
                    $modified = true;
                    $ymlArray = $this->addMainRouteEntry($ymlArray, $entityPath);
                }
            }

        }
        if ($modified) {
            $yml = Yaml::dump($ymlArray);
            self::mkdir(dirname($filePath));
            return self::dump($filePath, $yml);
        } else return 0;


    }

    private static function relativizePath($absolutePath)
    {
        $relativePath = str_replace(getcwd(), '.', $absolutePath);

        return is_dir($absolutePath) ? rtrim($relativePath, '/') . '/' : $relativePath;
    }

    private function addMainRouteEntry(array $ymlArray, string $entityPath) : array
    {
        $pluralClassName = $this->createPluralClassName($entityPath);
        $route = [$pluralClassName => ['resource' => 'routing/' . $pluralClassName . '.yml']];
        return array_merge($ymlArray, $route);
    }

    private function createPluralClassName(string $entityPath) : string
    {
        $className = $this->getClassName($entityPath);
        return strtolower(Pluralizer::pluralize($className));
    }


    private function createRoutes(string $entityPath)
    {
        if ($this->overWrite) $ymlArray = [];
        elseif (file_exists($filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/' . $this->createPluralClassName($entityPath) . '.yml')) $ymlArray = Yaml::parse(file_get_contents($filePath));
        else $ymlArray = [];

        if ($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'list', $ymlArray)) {
            $listArray = $this->generateListRoute($entityPath);
            $ymlArray = array_merge($ymlArray, $listArray);
        }
        if ($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'get', $ymlArray)) {
            $getArray = $this->generateGetRoute($entityPath);
            $ymlArray = array_merge($ymlArray, $getArray);
        }
        if ($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'post', $ymlArray)) {
            $postArray = $this->generatePostRoute($entityPath);
            $ymlArray = array_merge($ymlArray, $postArray);
        }
        if ($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'put', $ymlArray)) {
            $postArray = $this->generatePutRoute($entityPath);
            $ymlArray = array_merge($ymlArray, $postArray);
        }
        if ($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'delete', $ymlArray)) {
            $postArray = $this->generateDeleteRoute($entityPath);
            $ymlArray = array_merge($ymlArray, $postArray);
        }


        return $ymlArray;

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
        return $factory->getClassMetadata($entity)->getMetadata();
    }


}


