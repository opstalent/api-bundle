<?php

/**
 * @author Patryk Grudniewski <patgrudniewski@gmail.com>
 */
namespace Opstalent\ApiBundle\Tests\Service;

use Opstalent\ApiBundle\Service\SerializerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;

class SerializerServiceTest extends TestCase
{
    /**
     * @var SerializerService
     */
    private $serializer;

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
}
