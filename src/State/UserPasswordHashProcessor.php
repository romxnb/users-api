<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPasswordHashProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface          $decorated,
        private UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User) {
            $raw = $data->getPassword();

            // Avoid double-hashing if pass looks already hashed
            $looksHashed = str_starts_with($raw, '$2y$') || str_starts_with($raw, '$argon2');

            if ($raw !== '' && !$looksHashed) {
                $data->setPassword($this->passwordHasher->hashPassword($data, $raw));
            }
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
