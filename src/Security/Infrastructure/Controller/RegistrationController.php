<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Controller;

use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    #[IsGranted('ROLE_USER')]
    public function register(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $user->isRegistered()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($request->isMethod('POST')) {
            // In a real app, we might collect name, company, etc.
            // For now, we just mark as registered to satisfy the magic link redirection flow.
            $user->setIsRegistered(true);
            $entityManager->flush();

            $this->addFlash('success', 'Welcome to Sentinel! Your account is now active.');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('registration/register.html.twig');
    }
}
