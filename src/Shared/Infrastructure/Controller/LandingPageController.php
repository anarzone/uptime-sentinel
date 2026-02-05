<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LandingPageController extends AbstractController
{
    #[Route('/', name: 'app_landing_page')]
    public function index(): Response
    {
        return $this->render('landing/index.html.twig');
    }
}
