<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class ExceptionListener
 * @package App\EventListener
 */
class ExceptionListener
{
    const MESSAGE = 'Opps, something wrong happened! %s with code %s';

    /**
     * @var \Twig_Environment
     */
    protected $templating;

    /**
     * ExceptionListener constructor.
     * @param \Twig_Environment $templating
     */
    public function __construct(\Twig_Environment $templating)
    {
        $this->templating = $templating;
    }

    /**
     * Basic control for exceptions. Error is shown in the index page
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();
        $message = sprintf(
            $this::MESSAGE,
            $exception->getMessage(),
            $exception->getCode()
        );
        error_log($message);

        $response = new Response();
        try {
            $response->setContent(
                $this->templating->render('default/index.html.twig', [
                    'error' => $message
                ])
            );
        } catch (\Exception $exception) {
            $response->setContent($message);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
    }
}
