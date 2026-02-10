<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[AsCommand(
    name: 'app:generate-login-link',
    description: 'Generates a magic login link for a user.',
)]
final class GenerateLoginLinkCommand extends Command
{
    public function __construct(
        private LoginLinkHandlerInterface $loginLinkHandler,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::OPTIONAL, 'User email', 'admin@example.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Spoof request for FirewallAwareLoginLinkHandler
        $request = \Symfony\Component\HttpFoundation\Request::create('/');
        $this->requestStack->push($request);

        $email = $input->getArgument('email');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln("<info>User $email not found. Creating...</info>");
            $user = new User($email);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
        $output->writeln($loginLinkDetails->getUrl());

        return Command::SUCCESS;
    }
}
