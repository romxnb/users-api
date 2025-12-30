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
            $raw = trim($data->getPlainPassword() ?? '');

            if ($raw !== '') {
                $data->setPassword($this->passwordHasher->hashPassword($data, $raw));
            }

            // Never persist plain-text passwords
            $data->setPlainPassword(null);
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
