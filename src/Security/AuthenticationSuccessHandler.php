<?php

declare(strict_types=1);

namespace App\Security;

use App\Security\Domain\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        try {
            $user = $token->getUser();

            if ($user instanceof User && !$user->isRegistered()) {
                return new RedirectResponse($this->urlGenerator->generate('app_register'));
            }

            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        } catch (\Throwable $e) {
            file_put_contents(
                \dirname(__DIR__, 2).'/var/log/auth_success_error.log',
                date('[Y-m-d H:i:s] ').$e->getMessage()."\n".$e->getTraceAsString()."\n",
                \FILE_APPEND
            );
            throw $e;
        }
    }
}
