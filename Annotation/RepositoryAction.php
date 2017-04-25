<?php

namespace Opstalent\ApiBundle\Annotation;

use Doctrine\Common\Annotation\Annotation;
use Doctrine\Common\Annotation\Annotation\Required;
use Doctrine\Common\Annotation\Annotation\Target;

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 * @package Opstalent\ApiBundle
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class RepositoryAction implements AnnotationInterface
{
    /**
     * @var string
     *
     * @Required
     */
   public $method; 

   /**
    * @var array
    */
   public $params = [];

   /**
    * {@inheritdoc}
    */
   public function getAliasName() : string
   {
       return 'repository_caller';
   }
}
