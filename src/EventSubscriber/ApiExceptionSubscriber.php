<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        // Only affect your API routes
        if (!str_starts_with($request->getPathInfo(), '/v1/api')) {
            return;
        }

        $e = $event->getThrowable();

        // Default values
        $status = 500;
        $message = 'Internal Server Error';

        // Respect HttpExceptions (404, 403, etc.)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $message;
        }

        // Handle DB unique constraint nicely (login/phone uniqueness)
        if ($e instanceof UniqueConstraintViolationException) {
            $status = 409;
            $message = 'Unique constraint violation.';
        }

        $payload = [
            'error' => [
                'message' => $message,
                'status' => $status,
            ],
        ];

        if ($this->debug) {
            $payload['error']['exception'] = $e::class;
            $payload['error']['detail'] = $e->getMessage();
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
