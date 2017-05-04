<?php
/**
 * Created by PhpStorm.
 * User: szymon
 * Date: 22.12.16
 * Time: 11:51
 */

namespace Opstalent\ApiBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\Role\RoleInterface;

class SerializerService extends Serializer
{

    protected $tokenStorage;

    public function __construct(array $normalizers, array $encoders, TokenStorageInterface $tokenStorage)
    {
        /** @var ObjectNormalizer $normalizer */
        foreach ($normalizers as $normalizer)
            $normalizer->setCircularReferenceHandler(function ($obj) {return $obj->getId();});
        parent::__construct($normalizers,$encoders);
        $this->tokenStorage = $tokenStorage;
    }

    public function generateSerializationGroup(Route $route, $method, $data=null):array
    {
        $serializeGroup = $route->getOption('serializerGroups');
        if (!$serializeGroup) return $method === 'list' ? ["list"] : ['get'];

        $groups = [];
        $user = $this->tokenStorage->getToken()->getUser();
        if ($this->isOwner($user, $data, $route)) {
            $groups[] = $serializeGroup['owner'];
        }

        $groups += $this->getAclMatchingRoles($route);
        if (!empty($groups)) {
            return $groups;
        } elseif (is_array($serializeGroup) && array_key_exists('all', $serializeGroup)) {
            return [$serializeGroup['all']];
        } else {
            return ['list'];
        }
    }

    public function isRole($value,$key)
    {
        return strpos($key,"ROLE_") === 0;
    }

    public function getRole(RoleInterface $value)
    {
        return $value->getRole();
    }

    /**
     * @param Route $route
     * @return array
     */
    protected function getAclMatchingRoles(Route $route):array
    {
        $serializeGroup = $route->getOption('serializerGroups');
        if (!is_array($serializeGroup)) {
            return [];
        }

        $groups = array_intersect($this->getRolesGroup($serializeGroup), $this->getUserRoles());
        if (!empty($groups)) {
            return array_values(array_intersect_key($serializeGroup, array_flip($groups)));
        } else {
            return [];
        }
    }

    /**
     * @param mixed $user
     * @param object|null $data
     * @param Route $route
     * @return array
     */
    protected function isOwner($user, $data, Route $route) 
    {
        $serializeGroup = $route->getOption('serializerGroups');

        return is_array($serializeGroup) && // is serializeGroup defined?
            array_key_exists('owner', $serializeGroup) && // is owner serialization allowed for route?
            $data && // is data defined?
            method_exists($data, 'getOwner') && // is able to check object owner?
            $data->getOwner() == $user // is user owner of data object?
            ;
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
