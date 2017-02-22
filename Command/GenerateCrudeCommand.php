<?php
/**
 * Created by PhpStorm.
 * User: good.vibes.development@gmail.com
 * Date: 2016-10-20
 * Time: 13:07
 */

namespace Opstalent\ApiBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\User;
use ReflectionClass;
use ReflectionProperty;
use ReflectionObject;

class GenerateCrudeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:generatecrude')
            ->setDescription('Generate crude files')
            ->setHelp('This command allows you to fast fill database with data. Enjoy!');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $reader = new AnnotationReader();

        $user = new User();
        $reflectionClass = new ReflectionClass('AppBundle\Entity\User');
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


}
