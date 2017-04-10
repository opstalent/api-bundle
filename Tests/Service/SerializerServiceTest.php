<?php

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
namespace Opstalent\ApiBundle\Tests\Service;

use Opstalent\ApiBundle\Service\SerializerService;
use Opstalent\ApiBundle\Tests\Utility\OwnableInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
//use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

class SerializerServiceTest extends TestCase
{
    /**
     * @var SerializerService
     */
    private $serializer;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $loggedInUserRoles = [];

    /**
     * @var int|null
     */
    private $loggedInUserId = null;

    public function setUp()
    {
        $token = \Mockery::mock(TokenInterface::class)
            ->shouldReceive('getRoles')
            ->andReturnUsing([$this, 'getLoggedInUserRoles'])
            ->shouldReceive('getUser')
            ->andReturnUsing([$this, 'getLoggedInUserId'])
            ->mock();

        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class)
            ->shouldReceive('getToken')
            ->andReturn($token)
            ->mock();

        $this->serializer = new SerializerService(
            [],
            [],
            $this->tokenStorage
        );
    }

    /**
     * @covers SerializerService::isRole
     * @dataProvider isRoleProvider
     *
     * @param string $role
     * @param bool $expected
     */
    public function testIsRole(string $role, bool $expected)
    {
        $this->assertEquals($expected, $this->serializer->isRole(null, $role));
    }

    /**
     * @covers SerializerService::getRole
     * @dataProvider getRoleProvider
     *
     * @param Role $role
     * @param string $expected
     */
    public function testGetRole(RoleInterface $role, string $expected)
    {
        $this->assertEquals($expected, $this->serializer->getRole($role));
    }

    /**
     * @covers SerializerService::isOwner
     * @dataProvider isOwnerProvider
     *
     * @param string $user
     * @param object|null $data
     * @param Route $route
     * @param bool $expected
     */
    public function testIsOwner(int $user, $data, Route $route, bool $expected)
    {
        $reflection = new \ReflectionMethod(SerializerService::class, 'isOwner');
        $reflection->setAccessible(true);
        $isOwner = $reflection->invokeArgs($this->serializer, [$user, $data, $route]);

        $this->assertEquals($expected, $isOwner);
    }

    /**
     * @covers SerializerService::getAclMatchingRoles
     * @dataProvider getAclMatchingRolesProvider
     *
     * @param RoleInterface[] $roles
     * @param Route $route
     * @param array $expected
     */
    public function testGetAclMatchingRoles(array $roles, Route $route, array $expected)
    {
        $this->setLoggedInUserRoles($roles);

        $reflection = new \ReflectionMethod(SerializerService::class, 'getAclMatchingRoles');
        $reflection->setAccessible(true);
        $roles = $reflection->invokeArgs($this->serializer, [$route]);

        $this->assertEquals($expected, $roles);
    }

    /**
     * @covers SerializerService::getRolesGroup
     * @dataProvider getRolesGroupProvider
     * 
     * @param array $serializeGroup
     * @param array $expected
     */
    public function testGetRolesGroup(array $serializeGroup, array $expected)
    {
        $reflection = new \ReflectionMethod(SerializerService::class, 'getRolesGroup');
        $reflection->setAccessible(true);
        $rolesGroup = $reflection->invokeArgs($this->serializer, [$serializeGroup]);

        $this->assertEquals($expected, $rolesGroup);
    }

    /**
     * @covers SerializerService::getUserRoles
     * @dataProvider getUserRolesProvider
     *
     * @param array $userRoles
     */
    public function testGetUserRoles(array $userRoles)
    {
        $this->setLoggedInUserRoles($userRoles);

        $reflection = new \ReflectionMethod(SerializerService::class, 'getUserRoles');
        $reflection->setAccessible(true);
        $roles = $reflection->invokeArgs($this->serializer, [$roles]);

        $this->assertEquals($userRoles, $roles);
    }

    /**
     * @covers SerializerService::generateSerializationGroup
     * @dataProvider generateSerializationGroupProvider
     *
     * @param Route $route
     * @param string $method
     * @param object|null $data
     * @param array user
     * @param array $expected
     */
    public function testGenerateSerializationGroup(Route $route, string $method, $data, array $user, array $expected)
    {
        $this->setLoggedInUserId(array_key_exists('id', $user) ? $user['id'] : null);
        $this->setLoggedInUserRoles(array_key_exists('roles', $user) ? $user['roles'] : []);

        $roles = $this->serializer->generateSerializationGroup($route, $method, $data);
        $this->assertEquals($expected, $roles);
    }

    /**
     * @return array
     */
    public function generateSerializationGroupProvider():array
    {
        mt_srand(0);
        $methods = ['get', 'list', 'another'];

        $aclMatchingRolesData = $this->getAclMatchingRolesProvider();
        $data = [];

        foreach ($this->isOwnerProvider() as $row) {
            $method = $methods[mt_rand(0, 2)];
            $expected = $row[3] ? $row[2]->getOption('serializerGroups')['owner'] : [];
            $data[] = [$row[2], $method, $row[1], ['id' => $row[0]], $expected];

        }

        foreach ($this->getAclMatchingRolesProvider() as $row) {
            $method = $methods[mt_rand(0, 2)];
            $data[] = [$row[1], $method, null, ['roles' => $row[0]], $row[2]];
        }

        foreach ($data as &$row) {
            $serializeGroup = $row[0]->getOption('serializerGroups');
            if (!$serializeGroup) {
                $row[4] = 'list' == $row[1] ? ['list'] : ['get'];
            } elseif (empty($row[4])) {
                $row[4] = [is_array($serializeGroup) && array_key_exists('all', $serializeGroup) ? $serializeGroup['all'] : 'list'];
            }
        }

        mt_srand();
        return $data;
    }


    /**
     * @return array
     */
    public function getUserRolesProvider():array
    {
        return [
            [['ROLE_TEST', 'ROLE_ADMIN']],
            [['ROLE_TEST']],
            [[]],
        ];
    }

    /**
     * @return array
     */
    public function getRolesGroupProvider():array
    {
        return [
            [['ROLE_TEST' => '', 'someString' => '', 'ROLE_ADMIN' => ''], ['ROLE_TEST', 'ROLE_ADMIN']],
            [['ROLE_TEST' => '', 'someString' => ''], ['ROLE_TEST']],
            [['someString' => ''], []],
            [[], []],
        ];
    }

    /**
     * @return array
     */
    public function getAclMatchingRolesProvider():array
    {
        $rawData = [
            [
                'route' => [
                    'serializerGroups' => [
                        'ROLE_ADMIN' => 'fullAccess',
                        'ROLE_TEST' => 'testAccess',
                    ]
                ],
                'roles' => [
                    'ROLE_ADMIN',
                ],
                'expected' => [
                    'fullAccess',
                ],
            ],
            [
                'route' => [
                ],
                'roles' => [
                    'ROLE_ADMIN',
                ],
                'expected' => [],
            ],
            [
                'route' => [
                    'serializerGroups' => [
                        'ROLE_ADMIN' => 'fullAccess',
                        'ROLE_TEST' => 'testAccess',
                    ]
                ],
                'roles' => [
                    'ROLE_TEST',
                ],
                'expected' => [
                    'testAccess',
                ],
            ],
            [
                'route' => [
                    'serializerGroups' => [
                        'ROLE_ADMIN' => 'fullAccess',
                        'ROLE_TEST' => 'testAccess',
                        'all' => 'globalAccess',
                    ]
                ],
                'roles' => [
                    'ROLE_TEST_ANOTHER',
                ],
                'expected' => [],
            ],
        ];

        $data = [];
        foreach ($rawData as $case) {
            $route = \Mockery::mock(Route::class)
                ->shouldReceive('getOption')
                ->with('serializerGroups')
                ->andReturn($case['route']['serializerGroups'])
                ->mock();

            $data[] = [
                $case['roles'],
                $route,
                $case['expected'],
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    public function isOwnerProvider():array
    {
        $rawData = [
            [
                'user' => 1,
                'data' => [
                    'owner' => 1
                ],
                'route' => [
                    'serializerGroups' => [
                        'owner' => ['me'],
                    ],
                ],
                'expected' => true,
            ],
            [
                'user' => 1,
                'data' => null,
                'route' => [
                    'serializerGroups' => [
                        'owner' => ['me'],
                    ],
                ],
                'expected' => false,
            ],
            [
                'user' => 2,
                'data' => [
                    'owner' => 1
                ],
                'route' => [
                    'serializerGroups' => [
                        'owner' => ['me'],
                    ],
                ],
                'expected' => false,
            ],
            [
                'user' => 1,
                'data' => [
                    'owner' => 1
                ],
                'route' => [
                    'serializerGroups' => [
                        'ROLE_USER' => 'list',
                    ],
                ],
                'expected' => false,
            ],
            [
                'user' => 1,
                'data' => [
                    'owner' => 1
                ],
                'route' => [
                    'serializerGroups' => [
                    ],
                ],
                'expected' => false,
            ],
            [
                'user' => 1,
                'data' => [
                    'owner' => 1
                ],
                'route' => [
                ],
                'expected' => false,
            ],
        ];

        $data = [];
        foreach ($rawData as $row) {
            $object = null;
            if (is_array($row['data'])) {
                $object = \Mockery::mock(OwnableInterface::class)
                    ->shouldReceive('getOwner')
                    ->andReturn($row['data']['owner'])
                    ->mock();
            }

            $route = \Mockery::mock(Route::class)
                ->shouldReceive('getOption')
                ->with('serializerGroups')
                ->andReturn($row['route']['serializerGroups'])
                ->mock();

            $data[] = [
                $row['user'],
                $object,
                $route,
                $row['expected'],
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    public function isRoleProvider():array
    {
        return [
            ['ROLE_ADMIN', true],
            ['ROLE_SUPERADMIN', true],
            ['ROLE_USER', true],
            ['ROLE_SOME', true],
            ['SOME_ROLE', false],
            ['ANOTHER_ROLE', false],
        ];
    }

    /**
     * @return array
     */
    public function getRoleProvider():array
    {
        $roles = [];
        foreach ($this->isRoleProvider() as $item) {
            if (!$item[1]) {
                continue;
            }
            $role = \Mockery::mock(RoleInterface::class)
                ->shouldReceive('getRole')
                ->andReturn($item[0])
                ->mock();
            $roles[] = [$role, $item[0]];
        }

        return $roles;
    }

    /**
     * @return RoleInterface[]
     */
    public function getLoggedInUserRoles():array
    {
        return $this->loggedInUserRoles;
    }

    /**
     * @return int
     */
    public function getLoggedInUserId():?int
    {
        return $this->loggedInUserId;
    }

    /**
     * @param array
     */
    protected function setLoggedInUserRoles(array $roles)
    {
        $this->loggedInUserRoles = [];
        foreach ($roles as $role) {
            $this->loggedInUserRoles[] = \Mockery::mock(RoleInterface::class)
                ->shouldReceive('getRole')
                ->andReturn($role)
                ->mock();
        }
    }

    /**
     * @param int
     */
    protected function setLoggedInUserId(?int $id)
    {
        $this->loggedInUserId = $id;
    }
}
