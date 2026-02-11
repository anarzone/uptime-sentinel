<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class DebugAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $prev = $exception->getPrevious();
        $prevChain = '';
        while ($prev) {
            $prevChain .= \sprintf("\n  -> [%s] %s", $prev::class, $prev->getMessage());
            $prev = $prev->getPrevious();
        }

        return new Response(
            \sprintf(
                "AUTH FAILURE: %s\nPrevious exceptions: %s\nUser Param: %s\nQuery: %s\nGET: %s\nTrace: %s",
                $exception->getMessage(),
                $prevChain ?: '(none)',
                $request->query->get('user') ?? 'NULL',
                json_encode($request->query->all(), \JSON_PRETTY_PRINT),
                json_encode($_GET, \JSON_PRETTY_PRINT),
                $exception->getTraceAsString()
            ),
            401,
            ['Content-Type' => 'text/plain']
        );
    }
}
