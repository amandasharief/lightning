<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2022 Amanda Sharief.
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\Http\ExceptionHandler;

use Exception;
use Throwable;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

use Lightning\Http\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * ExceptionHandlerMiddleware
 *
 * This is based upon the recommendation of PSR-15, but does not include an error handler
 *
 * @see https://www.php-fig.org/psr/psr-15/
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    private string $path;
    private ErrorRenderer $render;
    private ResponseFactoryInterface $responseFactory;
    private ?LoggerInterface $logger;

    /**
     * Constructor
     */
    public function __construct(string $path, ErrorRenderer $renderer, ResponseFactoryInterface $responseFactory, ?LoggerInterface $logger = null)
    {
        $this->path = rtrim($path, '/') . '/';
        $this->render = $renderer;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * Processes an incoming server request in order to produce a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception, $request);
        }
    }

    /**
     * Processes the exception to produce a response
     */
    private function handleException(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        // Standard error for non HTTP exceptions
        $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;
        $message = $exception instanceof HttpException ? $exception->getMessage() : 'Internal Server Error'; // TODO: wait for PSR Localization

        // Log if needed
        if ($this->logger) {
            $this->logger->error(
                sprintf('%s Exception in %s:%s', $exception->getMessage(), $exception->getFile(), $exception->getLine())
            );
        }

        // requested XML but not HTML (e.g browser)
        if ($this->isXml($request)) {
            return $this->createResponse(
                $statusCode, $this->render->xml($message, $statusCode), 'application/xml'
            );
        }

        // If it accepts JSON or if request looks like an API request then use JSON as default.
        if ($this->isJson($request) || $this->isApiRequest($request)) {
            return $this->createResponse(
                $statusCode, $this->render->json($message, $statusCode), 'application/json'
            );
        }

        // Return HTML for any other reason.
        return $this->createResponse(
            $statusCode, $this->render->html($this->template($exception, $statusCode), $message, $statusCode, $request, $exception), 'text/html'
        );
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/api/') && $request->getHeaderLine('Accept') === '*/*';
    }

    private function template(Throwable $exception, int  $statusCode): string
    {
        $template = $exception instanceof HttpException && $statusCode < 500 ? '400' : '500';

        return sprintf('%s/error%s.php', $this->path, $template);
    }

    /**
     * Checks if the request is wanting JSON
     */
    private function isJson(ServerRequestInterface $request): bool
    {
        return strpos($request->getHeaderLine('Accept'), 'application/json') !== false;
    }

    /**
     * Checks if the request is wanting XML. Browsers request both html and application/xml
     */
    private function isXml(ServerRequestInterface $request): bool
    {
        return strpos($request->getHeaderLine('Accept'), 'text/html') === false && (bool) preg_match('/text\/xml|application\/xml/', $request->getHeaderLine('Accept'));
    }

    /**
     * Creates the Response Object
     */
    private function createResponse(int $statusCode, string $body, string $contentType): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($body);

        return $response ->withHeader('Content-Type', $contentType); // 'text/html'
    }
}
