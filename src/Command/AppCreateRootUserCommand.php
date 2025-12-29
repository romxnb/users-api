<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-root',
    description: 'Create (or update) a ROOT user for local/dev usage.',
)]
final class AppCreateRootUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $hasher,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Login', 'root')
            ->addOption('phone', null, InputOption::VALUE_REQUIRED, 'Phone', '+10000000000')
            ->addOption('pass', null, InputOption::VALUE_REQUIRED, 'Password', 'rootpass');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = (string)$input->getOption('login');
        $phone = (string)$input->getOption('phone');
        $pass = (string)$input->getOption('pass');

        $repo = $this->em->getRepository(User::class);

        /** @var User|null $user */
        $user = $repo->findOneBy(['login' => $login]);

        if (!$user) {
            $user = new User();
            $user->setLogin($login);
        }

        $user->setPhone($phone);
        $user->setRoles(['ROLE_ROOT']); // important: ROOT role

        // Hash password safely
        $user->setPassword($this->hasher->hashPassword($user, $pass));

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('ROOT user ready: login=%s phone=%s', $login, $phone));
        return Command::SUCCESS;
    }
}
