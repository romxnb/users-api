<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface as BuiltinProcessorInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;


#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_ROOT')"
        ),
        new Post(
            uriTemplate: '/users',
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ROOT')",
            processor: \App\State\UserPasswordHashProcessor::class
        ),
        new Get(
            uriTemplate: '/users/{id}',
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('ROLE_ROOT') or object == user"
        ),
        new Put(
            uriTemplate: '/users/{id}',
            normalizationContext: ['groups' => ['user:read']],
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ROOT') or object == user",
            processor: \App\State\UserPasswordHashProcessor::class
        ),
        new Delete(
            uriTemplate: '/users/{id}',
            security: "is_granted('ROLE_ROOT')"
        ),
    ]
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['login'], message: 'login is already taken')]
#[UniqueEntity(fields: ['phone'], message: 'phone is already taken')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[Groups(['user:read'])]
    private ?Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private string $login;

    #[ORM\Column(length: 32, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private string $phone;

    // Stored hashed. Never returned.
    #[ORM\Column(length: 255)]
    private string $pass;

    /**
     * Plain password, used only for write operations.
     *
     * Exposed in the API schema and accepted on POST/PUT.
     */
    #[Groups(['user:write'])]
    #[SerializedName('pass')]
    #[Assert\NotBlank(message: 'Password is required.')]
    private ?string $plainPassword = null;

    // Needed for root vs user
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:write'])]
    private array $roles;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    // This returns hashed password to Symfony (required by PasswordAuthenticatedUserInterface)
    public function getPassword(): string
    {
        return $this->pass;
    }

    public function setPassword(string $pass): self
    {
        $this->pass = $pass;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function eraseCredentials(): void
    {
    }
}
