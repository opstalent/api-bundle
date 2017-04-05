<?php
/**
 * Created by PhpStorm.
 * User: szymon
 * Date: 22.12.16
 * Time: 11:51
 */

namespace Opstalent\ApiBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\Role\Role;

class SerializerService extends Serializer
{

    protected $tokenStorage;

    public function __construct(array $normalizers, array $encoders, TokenStorage $tokenStorage)
    {
        /** @var ObjectNormalizer $normalizer */
        foreach ($normalizers as $normalizer)
            $normalizer->setCircularReferenceHandler(function ($obj) {return $obj->getId();});
        parent::__construct($normalizers,$encoders);
        $this->tokenStorage = $tokenStorage;
    }

    public function generateSerializationGroup(Route $route, $method, object $data=null):array
    {

        if (!$route->getOption('serializerGroups')) return $method === 'list' ? ["list"] : ['get'];
        $user = $this->tokenStorage->getToken()->getUser();
        $serializeGroup = $route->getOption('serializerGroups');
        if (
            array_key_exists('owner', $serializeGroup) &&
            $data &&
            method_exists($data, 'getOwner') &&
            $data->getOwner() == $user
        ) return [$serializeGroup['owner']];

        $groups = array_intersect($this->getRolesGroup($serializeGroup), $this->getUserRoles());
        if(!empty($groups)) return array_values(array_intersect_key($serializeGroup,array_flip($groups)));

        return array_key_exists('all', $serializeGroup) ? [$serializeGroup['all']] : ['list'];
    }

    public function isRole($value,$key)
    {
        return strpos($key,"ROLE_") === 0;
    }

    public function getRole(Role $value)
    {
        return $value->getRole();
    }

    private function getRolesGroup($serializeGroup)
    {
        return array_keys(array_filter(
            $serializeGroup,
            [$this, 'isRole'],
            ARRAY_FILTER_USE_BOTH
        ));
    }

    private function getUserRoles()
    {
        return array_map([$this, "getRole"], $this->tokenStorage->getToken()->getRoles());
    }
}
