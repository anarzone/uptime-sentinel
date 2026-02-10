<?php

use App\Kernel;
use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    $container = $kernel->getContainer();

    // 1. Get EntityManager
    $em = $container->get('doctrine')->getManager();

    // 2. Find or Create User
    $email = 'admin@example.com';
    $userRepo = $em->getRepository(User::class);
    $user = $userRepo->findOneBy(['email' => $email]);

    if (!$user) {
        // Create user if missing (adjust based on your User entity constructor/setters)
        // Assuming User can be created with email or via factory
        // Let's check User entity if this fails, but for now try generic approach
        echo "User $email not found. Please create it first.\n";
        exit(1);
    }

    // 3. Get Login Link Handler
    // The service ID usually follows convention: security.authenticator.login_link_handler.FIREWALLNAME
    // We'll try to find it dynamically or use the one found via debug:container
    $handler = $container->get('security.authenticator.login_link_handler.main');

    if (!$handler) {
        echo "Login Link Handler service not found.\n";
        exit(1);
    }

    // 4. Generate Link
    // createLoginLink return a LoginLinkDetails object
    $loginLinkDetails = $handler->createLoginLink($user);
    $url = $loginLinkDetails->getUrl();

    echo "Login Link: $url\n";
};
