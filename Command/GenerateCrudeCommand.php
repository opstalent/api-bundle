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
use Twig_Extension_Debug;
use Symfony\Component\Yaml\Yaml;
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
    private $map = ['array' => 'TextType', 'string' => "TextType", 'integer' => "NumberType", '2' => 'EntityType', 'date' => 'DateType', 'datetime' => 'DateTimeType', 'boolean' => 'CheckboxType', 'text' => 'TextType', 'float' => 'NumberType'];
    protected $skeletonDirs = [__DIR__ . '/../Resources/skeleton', __DIR__ . '/../Resources'];
    private static $output;
    protected $entityManager;
    protected $annotationMap = ['authorSubscriber' => 'Opstalent\\ApiBundle\\Annotation\\AuthorSubscriber', 'routes' => 'Opstalent\\ApiBundle\\Annotation\\RoutingOptions'];


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

            $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $this->validatePath($entityPaths);
            $this->createApiRoutes($entityPaths); // main route file
            $this->createRepositoriesYml($entityPaths); // repositories.yml


            foreach ($entityPaths as $entityPath) {
                $className = $this->getClassName($entityPath);
                $pluralClassName = $this->createPluralClassName($entityPath);
                $entityAnnotations = $this->getEntityAnnotations($entityPath);
                $this->createApiRouteFile($entityPath, $entityAnnotations);
                $metadata = $this->getEntityMetadata($entityPath);

                if ($this->overWrite) {
                    $this->injectIntoForm($entityPath, $entityAnnotations);
                    $this->generateFormType($metadata[0], $className, 'FilterType.php', $entityAnnotations);
                    $this->generateFormType($metadata[0], $className, 'AddType.php', $entityAnnotations);
                    $this->generateFormType($metadata[0], $className, 'EditType.php', $entityAnnotations);
                    $this->generateRepository($entityPath, $className, $entityAnnotations);
                } else {
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../app/' . 'config/forms/' . $pluralClassName . '.yml')) $this->injectIntoForm($entityPath, $entityAnnotations);
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/FilterType.php')) $this->generateFormType($metadata[0], $className, 'FilterType.php', $entityAnnotations);
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/AddType.php')) $this->generateFormType($metadata[0], $className, 'AddType.php', $entityAnnotations);
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Form/' . $className . '/EditType.php')) $this->generateFormType($metadata[0], $className, 'EditType.php', $entityAnnotations);
                    if (!file_exists($this->getContainer()->get('kernel')->getRootDir() . '/../src/' . 'AppBundle/Repository/' . $className . 'Repository.php')) $this->generateRepository($entityPath, $className, $entityAnnotations);

                }
                $this->updateMainFormFile();
                $this->editEntityFile($entityPath);

            }
            dump('koniec');
            exit;

        } catch (Exception $e) {
            var_dump($e->getTraceAsString());
        }
    }

    private function updateMainFormFile()
    {
        $files = scandir($this->getContainer()->get('kernel')->getRootDir() . '/../app/config/forms');
        unset($files[0]);
        unset($files[1]);
        $this->createApiFormsYml($files);

    }

    private function createApiFormsYml($files)
    {
        $ymlArray['imports'] = [];

        if ($files) {
            foreach ($files as $key => $file) {
                $arr = explode("/", $file, 2);
                $fileName = $arr[0];
                $ymlArray['imports'][$key - 2] = ['resource' => 'forms/' . $fileName];
            }
            $yml = Yaml::dump($ymlArray, 10);
            return self::dump($this->getContainer()->get('kernel')->getRootDir() . '/config/api_forms.yml', $yml);
        }
    }


    private function validatePath($entityPaths)
    {
        foreach ($entityPaths as $entityPath) {
            if (!class_exists($entityPath)) throw new \Exception('Class' . $entityPath . 'does not exists');
            if (!$this->isEntity($entityPath)) throw new \Exception('Class' . $entityPath . 'is not doctrine entity');
        }
    }

    private function isEntity($class)
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return !$this->entityManager->getMetadataFactory()->isTransient($class);
    }

    public function editEntityFile(string $entityPath)
    {

        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/' . str_replace('\\', '/', $entityPath . '.php');
        $entityFile = file_get_contents($filePath);
        $fileArray = explode("\n", $entityFile);
        foreach ($fileArray as $key => $line) {
            if (strpos($line, "repositoryClass") != false) {
                if ($this->overWrite) {
                    $fileArray[$key] = ' * @ORM\\Entity(' . 'repositoryClass="AppBundle\Repository\\' . $this->getClassName($entityPath) . 'Repository' . '")';

                    $newFile = implode("\n", $fileArray);
                    return self::dump($filePath, $newFile);
                } else {
                    return null;
                }
            } elseif (strpos($line, "@ORM\\Id") != false) {
                $entryPosition = $entityAnnotation = strpos($entityFile, "@ORM\\Entity") + 11;
                $newFile = substr_replace($entityFile, '(' . 'repositoryClass="AppBundle\Repository\\' . $this->getClassName($entityPath) . 'Repository' . '")', $entryPosition, 0);
                return self::dump($filePath, $newFile);
            };
        }


    }


    public function generateRepository($entityPath, $className, $entityAnnotations)
    {
        $dirPath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/AppBundle/Repository/' . $className . 'Repository.php';
        $this->renderFile('repository.php.twig', $dirPath, array(
            'namespace' => 'AppBundle',
            'entity_namespace' => 'Entity',
            'entity_class' => $className,
            'repository_class' => $className . 'Repository',
            'entity_path' => $entityPath,
            'annotations' => $entityAnnotations
        ));
    }


    private function generateFormType(ClassMetadataInfo $metadata, $className, $formType, $entityAnnotations)
    {

        $dirPath = $this->getContainer()->get('kernel')->getRootDir() . '/../src/AppBundle/Form/' . $className . '/' . $formType;
        $fieldsMetadata = $this->getFieldsFromMetadata($metadata);
        $this->renderFile($this->formMapPath[$formType], $dirPath, array(
            'uses' => $this->getUsesForFields($fieldsMetadata),
            'fields' => $fieldsMetadata,
            'namespace' => 'AppBundle',
            'entity_namespace' => 'Entity',
            'entity_class' => $className,
            'form_class' => $className . 'Type',
            'annotations' => $entityAnnotations
        ));


    }

    private function injectIntoForm($entityPath, $entityAnnotations)
    {

        foreach ($entityAnnotations as $key => $value) {

            switch ($key) {
                case 'authorSubscriber':
                    if ($value) $this->inject('AddType', '@security.token_storage', $entityPath);
            }

        }
    }

    private function inject($form, $parameter, $entityPath)
    {
        $className = $this->getClassName($entityPath);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/forms/' . $pluralClassName . '.yml';
        if ($this->overWrite || !file_exists($filePath)) {
            $ymlArray = $this->createFormService($form, $parameter, $entityPath);
            $yml = Yaml::dump($ymlArray, 10);
            return self::dump($this->getContainer()->get('kernel')->getRootDir() . '/config/forms/' . $pluralClassName . '.yml', $yml);
        }

    }

    private function createFormService($form, $parameter, $entityPath)
    {
        if ($this->overWrite) $ymlArray = [];
        elseif (file_exists($filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/forms/' . $this->createPluralClassName($entityPath) . '.yml')) $ymlArray = Yaml::parse(file_get_contents($filePath));
        else $ymlArray = [];

        if ($form == 'AddType') {
            if ($this->overWrite || !array_key_exists('app.form.' . $this->createPluralClassName($entityPath) . 'add', $ymlArray)) {
                $addArray = $this->generateFormYml('Add', $parameter, $entityPath);
                $ymlArray = array_merge($ymlArray, $addArray);
            }
        }

        return $ymlArray;
    }

    private function generateFormYml($form, $parameter, $entityPath)
    {
        $className = $this->getClassName($entityPath);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $array = ['services' =>
            ['app.form.' . $pluralClassName . '.' . strtolower($form) => ['class' => "AppBundle\\Form\\" . $className . "\\" . $form . 'Type', 'arguments' => [$parameter], 'tags' => [0 => ['name' => 'form.type']]]]];

        return $array;
    }

    private function getUsesForFields(array $fieldsMetadata)
    {
        $useArray = [];
        foreach ($fieldsMetadata as $field) {
            if (!in_array($field['type'], $useArray)) $useArray[] = $field['type'];
        }
        return $useArray;

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
        $twig->addExtension(new Twig_Extension_Debug());
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

    private function createApiRouteFile($entityPath, $entityAnnotations)
    {
        $className = $this->getClassName($entityPath);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . $pluralClassName . '.yml';
        if ($this->overWrite || !file_exists($filePath)) {
            $ymlArray = $this->createRoutes($entityPath, $entityAnnotations);
            $yml = Yaml::dump($ymlArray, 10);
            return self::dump($this->getContainer()->get('kernel')->getRootDir() . '/config/routing/' . $pluralClassName . '.yml', $yml);
        }

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
            $yml = Yaml::dump($ymlArray, 4);
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

    private
    function createRepositoryYmlParameterEntry($arrayParameters, $entityPath)
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


    private function createRoutes(string $entityPath, $entityAnnotations)
    {
        if ($this->overWrite) $ymlArray = [];
        elseif (file_exists($filePath = $this->getContainer()->get('kernel')->getRootDir() . '/config/' . $this->createPluralClassName($entityPath) . '.yml')) $ymlArray = Yaml::parse(file_get_contents($filePath));
        else $ymlArray = [];

        if (in_array('default', $entityAnnotations['roles'])) $roles = ['ROLE_SUPER_ADMIN'];
        else $roles = $entityAnnotations['roles'];

        $ownerableEventPut = null;
        $ownerableEventDelete = null;
        if ($entityAnnotations['ownerable']) {
            $ownerableEventPut = ['api_repository.persist.before' => ['authorizeOwner']];
            $ownerableEventDelete = ['api_repository.remove.before' => ['authorizeOwner']];
        }

        if (($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'list', $ymlArray)) && (in_array('list', $entityAnnotations['routes'])) || in_array('default', $entityAnnotations['routes'])) {
            $listArray = $this->generateListRoute($entityPath, $roles);
            $ymlArray = array_merge($ymlArray, $listArray);
        }
        if (($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'get', $ymlArray)) && (in_array('get', $entityAnnotations['routes'])) || in_array('default', $entityAnnotations['routes'])) {
            $getArray = $this->generateGetRoute($entityPath, $roles);
            $ymlArray = array_merge($ymlArray, $getArray);
        }
        if (($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'post', $ymlArray)) && (in_array('post', $entityAnnotations['routes'])) || in_array('default', $entityAnnotations['routes'])) {
            $postArray = $this->generatePostRoute($entityPath, $roles);
            $ymlArray = array_merge($ymlArray, $postArray);
        }
        if (($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'put', $ymlArray)) && (in_array('put', $entityAnnotations['routes'])) || in_array('default', $entityAnnotations['routes'])) {
            $postArray = $this->generatePutRoute($entityPath, $roles, $ownerableEventPut);
            $ymlArray = array_merge($ymlArray, $postArray);
        }
        if (($this->overWrite || !array_key_exists('api.' . $this->createPluralClassName($entityPath) . 'delete', $ymlArray)) && (in_array('delete', $entityAnnotations['routes'])) || in_array('default', $entityAnnotations['routes'])) {
            $postArray = $this->generateDeleteRoute($entityPath, $roles, $ownerableEventDelete);
            $ymlArray = array_merge($ymlArray, $postArray);
        }


        return $ymlArray;

    }

    private function getClassName($entity)
    {
        return substr($entity, strrpos($entity, '\\') + 1);
    }

    private function generateListRoute($entity, $roles, $events = null)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $routeLabel = 'api_' . $pluralClassName . '_list';
        $array = [$routeLabel =>
            ['path' => '/' . $pluralClassName,
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:list'],
                'methods' => ['GET'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\FilterType",
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => $roles
                    ]]]];

        return $array;

    }

    private function generateGetRoute($entity, $roles, $events = null)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $routeLabel = 'api_' . $pluralClassName . '_get';
        $array = [$routeLabel =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:get'],
                'methods' => ['GET'],
                'options' => [
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => $roles
                    ]]]];
        return $array;
    }

    private function generatePostRoute($entity, $roles, $events = null)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $routeLabel = 'api_' . $pluralClassName . '_post';
        $array = [$routeLabel =>
            ['path' => '/' . $pluralClassName,
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:post'],
                'methods' => ['POST'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\AddType",
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => $roles
                    ]]]];
        return $array;
    }

    private function generatePutRoute($entity, $roles, $events = null)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $routeLabel = 'api_' . $pluralClassName . '_put';
        $array = [$routeLabel =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:put'],
                'methods' => ['PUT'],
                'options' => [
                    'form' => "AppBundle\\Form\\" . $className . "\\EditType",
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => $roles
                    ]]]];
        if ($events) {
            $array[$routeLabel]['options']['security']['events'] = $events;
        }
        return $array;
    }


    private function generateDeleteRoute($entity, $roles, $events = null)
    {

        $className = $this->getClassName($entity);
        $pluralClassName = strtolower(Pluralizer::pluralize($className));
        $routeLabel = 'api_' . $pluralClassName . '_delete';
        $array = [$routeLabel =>
            ['path' => '/' . $pluralClassName . '/{id}',
                'requirements' => ['id' => '\d+'],
                'defaults' => ['_controller' => 'OpstalentApiBundle:Action:delete'],
                'methods' => ['DELETE'],
                'options' => [
                    'repository' => '@repository.' . strtolower($className),
                    'security' => [
                        'secure' => true,
                        'roles' => $roles
                    ]]]];
        if ($events) {
            $array[$routeLabel]['options']['security']['events'] = $events;
        }
        return $array;
    }

    protected function getEntityMetadata($entity)
    {
        $factory = new DisconnectedMetadataFactory($this->getContainer()->get('doctrine'));
        return $factory->getClassMetadata($entity)->getMetadata();
    }

    protected function getEntityAnnotations($entity)
    {
        $reader = new AnnotationReader();
        $authorAnnotation = $reader->getClassAnnotation(new \ReflectionClass(new $entity), $this->annotationMap['authorSubscriber']);
        $routesAnnotation = $reader->getClassAnnotation(new \ReflectionClass(new $entity), $this->annotationMap['routes']);
//        dump($routesAnnotation);
//        exit;
        if ($authorAnnotation) {
            $entityAnnotations['authorSubscriber'] = strtolower($authorAnnotation->subscribe);
        } else {
            $entityAnnotations['authorSubscriber'] = "false";
        }
        if ($routesAnnotation) {
            $entityAnnotations['ownerable'] = strtolower($routesAnnotation->ownerable);
            $entityAnnotations['routes'] = array_map('strtolower', $routesAnnotation->routes);
            $entityAnnotations['roles'] = array_map('strtoupper', $routesAnnotation->roles);
        } else {
            $entityAnnotations['ownerable'] = "false";
            $entityAnnotations['routes'] = ['default'];
            $entityAnnotations['roles'] = ['default'];
        }
        return $entityAnnotations;
    }
}


