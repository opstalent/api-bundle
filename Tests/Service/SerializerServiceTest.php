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

    public function setUp()
    {
        $this->serializer = new SerializerService(
            [],
            [],
            \Mockery::mock(TokenStorageInterface::class)
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
     * @return string
     */
    public function getLoggedInUserId():string
    {
        return $this->loggedInUserId;
    }

    /**
     * @return array
     */
    public function getLoggedInUserRoles():array
    {
        return $this->loggedInUserRoles;
    }
}
