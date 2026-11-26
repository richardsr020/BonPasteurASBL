<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Crée un utilisateur administrateur pour la production.')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de l\'utilisateur')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom complet', 'Administrateur')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Rôle à attribuer', ['ROLE_ADMIN'])
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (le prompt masqué est recommandé)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse e-mail invalide.');

            return Command::FAILURE;
        }

        if (null !== $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
            $io->error(sprintf('L\'utilisateur "%s" existe déjà.', $email));

            return Command::FAILURE;
        }

        $password = $input->getOption('password');
        if (!is_string($password) || '' === $password) {
            $password = $io->askHidden('Mot de passe');
        }
        if (!is_string($password) || \strlen($password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');

            return Command::FAILURE;
        }

        $roles = $input->getOption('role');
        if (!is_array($roles) || [] === $roles) {
            $roles = ['ROLE_ADMIN'];
        }

        $user = new User();
        $user->setEmail($email)
            ->setFullName((string) $input->getOption('name'))
            ->setRoles(array_values(array_map('strval', $roles)))
            ->setIsActive(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur "%s" créé avec les rôles : %s.', $email, implode(', ', $user->getRoles())));

        return Command::SUCCESS;
    }
}
