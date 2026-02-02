<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatusPageController extends AbstractController
{
    #[Route('/status', name: 'app_status_page')]
    public function index(): Response
    {
        return $this->render('status_page/index.html.twig');
    }
}
