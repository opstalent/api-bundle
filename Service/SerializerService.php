<?php
/**
 * Created by PhpStorm.
 * User: szymon
 * Date: 22.12.16
 * Time: 11:51
 */

namespace Opstalent\ApiBundle\Service;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SerializerService extends Serializer
{

    public function __construct(array $normalizers, array $encoders)
    {
        /** @var ObjectNormalizer $normalizer */
        foreach ($normalizers as $normalizer)
            $normalizer->setCircularReferenceHandler(function ($obj) {return $obj->getId();});
        parent::__construct($normalizers,$encoders);
    }
}
