<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Controller;

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkNotification;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        Request $request,
        UserRepository $userRepository,
        LoginLinkHandlerInterface $loginLinkHandler,
        NotifierInterface $notifier,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($request->isMethod('POST')) {
            $email = $request->getPayload()->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                // Pre-register user but with isRegistered = false
                $user = new User($email);
                $entityManager->persist($user);
                $entityManager->flush();
            }

            $loginLinkDetails = $loginLinkHandler->createLoginLink($user);

            // create a notification based on the login link details
            $notification = new LoginLinkNotification(
                $loginLinkDetails,
                'Sentinel Login Link' // email subject
            );

            // send the notification to the user
            $notifier->send($notification, new Recipient($user->getEmail()));

            return $this->render('security/login_link_sent.html.twig', [
                'email' => $user->getEmail(),
            ]);
        }

        return $this->render('security/login.html.twig');
    }

    #[Route('/login_check', name: 'login_check')]
    public function check(): Response
    {
        throw new \LogicException('This code should never be reached');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This code should never be reached');
    }
}
