<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-user',
    description: 'Promotes a user to a specific role (e.g. ROLE_ADMIN, ROLE_AUTHOR)',
)]
class PromoteUserCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user to promote')
            ->addArgument('role', InputArgument::OPTIONAL, 'The role to assign', 'ROLE_ADMIN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $roles = $user->getRoles();
        if (!in_array($role, $roles)) {
            $roles[] = $role;
            $user->setRoles($roles);
            $this->entityManager->flush();
            $io->success(sprintf('User "%s" has been promoted to %s.', $email, $role));
        } else {
            $io->note(sprintf('User "%s" already has the role %s.', $email, $role));
        }

        return Command::SUCCESS;
    }
}

// RUN THIS COMMAND
// php bin/console app:promote-user your-email@example.com ROLE_ADMIN