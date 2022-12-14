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

namespace Lightning\Http\Auth\Middleware;

use Lightning\Http\Auth\Identity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Lightning\Http\Auth\PasswordHasherInterface;
use Lightning\Http\Auth\IdentityServiceInterface;
use Lightning\Http\Exception\UnauthorizedException;

class HttpBasicAuthenticationMiddleware extends AbstractAuthenticationMiddleware implements MiddlewareInterface
{
    private IdentityServiceInterface $identityService;
    private ResponseFactoryInterface $responseFactory;
    private ?string $realm = null;
    private bool $challenge = true;
    private PasswordHasherInterface $passwordHasher;

    /**
     * Constructor
     */
    public function __construct(IdentityServiceInterface $identityService, PasswordHasherInterface $passwordHasher, ResponseFactoryInterface $responseFactory)
    {
        $this->identityService = $identityService;
        $this->passwordHasher = $passwordHasher;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Disables the password challenge
     *
     * @return static
     */
    public function disableChallenge(): static
    {
        $this->challenge = false;

        return $this;
    }

    /**
     * Process the server request and produce a response
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // check against path and publicPaths paths
        if (! $this->requiresAuthentication($request)) {
            return $handler->handle($request);
        }

        $identity = $this->authenticate($request);
        if ($identity) {
            return $handler->handle($request->withAttribute('identity', $identity));
        }

        if ($this->challenge) {
            $serverParams = $request->getServerParams();

            return $this->responseFactory->createResponse(401)->withAddedHeader(
                'WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm ?: $serverParams['SERVER_NAME'] ?? ''));
        }

        throw new UnauthorizedException();
    }

    /**
     * The authentication logic
     *
     * @param ServerRequestInterface $request
     * @return Identity|null
     */
    protected function authenticate(ServerRequestInterface $request): ?Identity
    {
        $serverParams = $request->getServerParams();
        $username = $serverParams['PHP_AUTH_USER'] ?? '';
        $password = $serverParams['PHP_AUTH_PW'] ?? '';

        // Pay attention to empty strings
        if ($username === '' || $password === '') {
            return null;
        }

        $identity = $this->identityService->findByIdentifier($username);
        if ($identity && $this->passwordHasher->verify($password, $identity->get($this->identityService->getCredentialName()))) {
            return $identity;
        }

        return null;
    }

    /**
     * Get the value of realm
     *
     * @return ?string
     */
    public function getRealm(): ?string
    {
        return $this->realm;
    }

    /**
     * Set the value of realm
     *
     * @param string $realm
     * @return static
     */
    public function setRealm(string $realm): static
    {
        $this->realm = $realm;

        return $this;
    }
}
