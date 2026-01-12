<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException', priority: 10)]
class ExceptionListener
{
    private $urlGenerator;
    private $logger;

    public function __construct(UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        // Log the exception
        $this->logger->error('An exception occurred: ' . $exception->getMessage(), [
            'exception' => $exception,
        ]);

        // Handle guest users (unauthenticated) or those with insufficient roles (Access Denied)
        if ($exception instanceof AccessDeniedException || $exception instanceof AuthenticationException) {
            $request = $event->getRequest();
            $session = $request->hasSession() ? $request->getSession() : null;

            if ($session instanceof \Symfony\Component\HttpFoundation\Session\Session) {
                $session->getFlashBag()->add('error', 'Access Denied: You do not have permission to view this page.');
            }

            $response = new RedirectResponse($this->urlGenerator->generate('app_home'));
            $event->setResponse($response);
        }
    }
}
