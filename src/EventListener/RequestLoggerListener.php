<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest')]
class RequestLoggerListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $this->logger->info(sprintf(
            'Request: %s %s [IP: %s]',
            $request->getMethod(),
            $request->getUri(),
            $request->getClientIp()
        ));
    }
}
