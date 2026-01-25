<?php

declare(strict_types=1);

namespace App\Monitoring\Infrastructure\Http\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: 'kernel.exception')]
final class ValidationExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle MapRequestPayload validation failures
        $validationException = null;
        if ($exception instanceof ValidationFailedException) {
            $validationException = $exception;
        } elseif ($exception->getPrevious() instanceof ValidationFailedException) {
            $validationException = $exception->getPrevious();
        }

        if ($validationException instanceof ValidationFailedException) {
            $errors = [];
            foreach ($validationException->getViolations() as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            $event->setResponse(new JsonResponse([
                'error' => 'Validation failed',
                'violations' => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        // Generic 422 handling if it's already an HttpException
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 422) {
            $event->setResponse(new JsonResponse([
                'error' => $exception->getMessage(),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
        }
    }
}
