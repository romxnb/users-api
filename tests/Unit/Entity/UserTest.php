<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Security\Role;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for the User entity.
 */
class UserTest extends TestCase
{
    public function testConstructorSetsDefaultValues(): void
    {
        $user = new User();

        $this->assertInstanceOf(Uuid::class, $user->getId());
        $this->assertEquals([Role::USER->value], $user->getRoles());
        $this->assertEmpty($user->getLogin());
        $this->assertEmpty($user->getPhone());
    }

    public function testIdIsUuidV7(): void
    {
        $user = new User();
        $id = $user->getId();

        $this->assertNotNull($id);
        // UUID v7 has version bits set to 0111 (7) in the version field
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id->toRfc4122());
    }

    public function testSetAndGetLogin(): void
    {
        $user = new User();
        $login = 'testuser';

        $result = $user->setLogin($login);

        $this->assertSame($user, $result, 'setLogin should return $this for method chaining');
        $this->assertEquals($login, $user->getLogin());
    }

    public function testSetLoginTrimsWhitespace(): void
    {
        $user = new User();
        $user->setLogin('  testuser  ');

        $this->assertEquals('testuser', $user->getLogin());
    }

    public function testSetAndGetPhone(): void
    {
        $user = new User();
        $phone = '+1234567890';

        $result = $user->setPhone($phone);

        $this->assertSame($user, $result, 'setPhone should return $this for method chaining');
        $this->assertEquals($phone, $user->getPhone());
    }

    public function testSetPhoneTrimsWhitespace(): void
    {
        $user = new User();
        $user->setPhone('  +1234567890  ');

        $this->assertEquals('+1234567890', $user->getPhone());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $hashedPassword = '$2y$13$hashedpasswordvalue';

        $result = $user->setPassword($hashedPassword);

        $this->assertSame($user, $result, 'setPassword should return $this for method chaining');
        $this->assertEquals($hashedPassword, $user->getPassword());
    }

    public function testSetAndGetPlainPassword(): void
    {
        $user = new User();
        $plainPassword = 'MySecretPassword123';

        $result = $user->setPlainPassword($plainPassword);

        $this->assertSame($user, $result, 'setPlainPassword should return $this for method chaining');
        $this->assertEquals($plainPassword, $user->getPlainPassword());
    }

    public function testPlainPasswordIsNullByDefault(): void
    {
        $user = new User();

        $this->assertNull($user->getPlainPassword());
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $roles = [Role::USER->value, Role::ROOT->value];

        $result = $user->setRoles($roles);

        $this->assertSame($user, $result, 'setRoles should return $this for method chaining');
        $this->assertEquals($roles, $user->getRoles());
    }

    public function testGetRolesAlwaysIncludesUserRole(): void
    {
        $user = new User();
        $user->setRoles([Role::ROOT->value]);

        $roles = $user->getRoles();

        $this->assertContains(Role::USER->value, $roles);
        $this->assertContains(Role::ROOT->value, $roles);
    }

    public function testSetRolesNormalizesInput(): void
    {
        $user = new User();
        // Set roles with duplicates and invalid roles
        $user->setRoles([Role::ROOT->value, Role::USER->value, Role::ROOT->value, 'INVALID_ROLE']);

        $roles = $user->getRoles();

        // Should have USER and ROOT only once, no invalid roles
        $this->assertCount(2, $roles);
        $this->assertContains(Role::USER->value, $roles);
        $this->assertContains(Role::ROOT->value, $roles);
    }

    public function testSetRolesWithEmptyArrayStillIncludesUserRole(): void
    {
        $user = new User();
        $user->setRoles([]);

        $roles = $user->getRoles();

        $this->assertEquals([Role::USER->value], $roles);
    }

    public function testSetAndGetRoleEnums(): void
    {
        $user = new User();
        $result = $user->setRoleEnums(Role::USER, Role::ROOT);

        $this->assertSame($user, $result, 'setRoleEnums should return $this for method chaining');

        $roleEnums = $user->getRoleEnums();

        $this->assertCount(2, $roleEnums);
        $this->assertContainsOnly(Role::class, $roleEnums);
        $this->assertContains(Role::USER, $roleEnums);
        $this->assertContains(Role::ROOT, $roleEnums);
    }

    public function testGetRoleEnumsReturnsOnlyValidRoles(): void
    {
        $user = new User();
        $user->setRoles([Role::USER->value]);

        $roleEnums = $user->getRoleEnums();

        $this->assertCount(1, $roleEnums);
        $this->assertEquals(Role::USER, $roleEnums[0]);
    }

    public function testGetUserIdentifierReturnsLogin(): void
    {
        $user = new User();
        $login = 'testuser';
        $user->setLogin($login);

        $this->assertEquals($login, $user->getUserIdentifier());
    }

    public function testEraseCredentialsClearsPlainPassword(): void
    {
        $user = new User();
        $user->setPlainPassword('MySecretPassword123');

        $this->assertNotNull($user->getPlainPassword());

        $user->eraseCredentials();

        $this->assertNull($user->getPlainPassword());
    }

    public function testEraseCredentialsDoesNotClearHashedPassword(): void
    {
        $user = new User();
        $hashedPassword = '$2y$13$hashedpasswordvalue';
        $user->setPassword($hashedPassword);
        $user->setPlainPassword('plaintext');

        $user->eraseCredentials();

        $this->assertEquals($hashedPassword, $user->getPassword());
    }

    public function testMultipleUsersHaveUniqueIds(): void
    {
        $user1 = new User();
        $user2 = new User();

        $this->assertNotEquals($user1->getId()->toRfc4122(), $user2->getId()->toRfc4122());
    }

    public function testMethodChaining(): void
    {
        $user = new User();

        $result = $user
            ->setLogin('testuser')
            ->setPhone('+1234567890')
            ->setPassword('hashedpass')
            ->setPlainPassword('plainpass')
            ->setRoles([Role::ROOT->value]);

        $this->assertSame($user, $result, 'All setter methods should support method chaining');
        $this->assertEquals('testuser', $user->getLogin());
        $this->assertEquals('+1234567890', $user->getPhone());
        $this->assertEquals('hashedpass', $user->getPassword());
        $this->assertEquals('plainpass', $user->getPlainPassword());
        $this->assertContains(Role::ROOT->value, $user->getRoles());
    }

    public function testUserImplementsUserInterface(): void
    {
        $user = new User();

        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);
    }

    public function testUserImplementsPasswordAuthenticatedUserInterface(): void
    {
        $user = new User();

        $this->assertInstanceOf(
            \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class,
            $user
        );
    }
}

