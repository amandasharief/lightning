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
use Lightning\Http\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Lightning\Http\Auth\PasswordHasherInterface;
use Lightning\Http\Auth\IdentityServiceInterface;
use Lightning\Http\Exception\UnauthorizedException;

class FormAuthenticationMiddleware extends AbstractAuthenticationMiddleware implements MiddlewareInterface
{
    private IdentityServiceInterface $identityService;
    private SessionInterface $session;
    private ResponseFactoryInterface $responseFactory;
    private string $usernameField = 'email';
    private string $passwordField = 'password';
    private string $sessionKey = 'identity';
    private string $loginPath = '/login';
    private PasswordHasherInterface $passwordHasher;

    /**
     * An unauthorized exception is thrown unless you set an url where it will be redirected to
     */
    protected ?string $unauthenticatedRedirect = null;

    /**
     * Constructor
     */
    public function __construct(IdentityServiceInterface $identityService, PasswordHasherInterface $passwordHasher, SessionInterface $session, ResponseFactoryInterface $responseFactory)
    {
        $this->identityService = $identityService;
        $this->passwordHasher = $passwordHasher;
        $this->session = $session;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Sets the unauthenticated redirect url
     */
    public function setUnauthenticatedRedirect(string $url): static
    {
        $this->unauthenticatedRedirect = $url;

        return $this;
    }

    /**
     * Get the value of unauthenticatedRedirect
     */
    public function getUnauthenticatedRedirect(): ?string
    {
        return $this->unauthenticatedRedirect;
    }

    /**
     * Sets the username field, e.g. username, email, user etc
     */
    public function setUsernameField(string $field): static
    {
        $this->usernameField = $field;

        return $this;
    }

    /**
     * Set session key
     */
    public function setSessionKey(string $key): static
    {
        $this->sessionKey = $key;

        return $this;
    }

    /**
     * Sets the password field if needed
     */
    public function setPasswordField(string $field): static
    {
        $this->passwordField = $field;

        return $this;
    }

    /**
     * Process the server request and produce a response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Checks if login page
        if ($request->getUri()->getPath() === $this->loginPath) {
            return $this->handleLogin($request, $handler);
        }

        // check against path and public paths
        if (! $this->requiresAuthentication($request)) {
            return $handler->handle($request);
        }

        // Get user from session
        if ($identity = $this->getLoggedInUser()) {
            return $handler->handle($request->withAttribute('identity', $identity));
        }

        if ($this->unauthenticatedRedirect) {
            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', $this->unauthenticatedRedirect);
        }

        throw new UnauthorizedException();
    }

    /**
     * Handles the login page
     */
    protected function handleLogin(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // only allow authentication from POST requests
        if ($request->getMethod() === 'POST') {
            if ($identity = $this->authenticate($request)) {
                $this->session->set($this->sessionKey, $identity->toArray());
                $request = $request->withAttribute('identity', $identity);
            }
        }

        return $handler->handle($request); # Continue rendering login page
    }

    /**
     * Gets the user from Session
     */
    protected function getLoggedInUser(): ?Identity
    {
        // Check session
        $auth = $this->session->get($this->sessionKey);

        return  $auth ? new Identity($auth) : null;
    }

    /**
     * The authentication logic
     */
    protected function authenticate(ServerRequestInterface $request): ?Identity
    {
        // Get the credentials from the request
        $body = $request->getParsedBody();
        $username = $body[$this->usernameField] ?? '';
        $password = $body[$this->passwordField] ?? '';

        // Pay attention to empty strings
        if ($username === '' || $password === '') {
            return null;
        }

        // User not found
        $identity = $this->identityService->findByIdentifier($username);
        if ($identity && $this->passwordHasher->verify($password, $identity->get($this->identityService->getCredentialName()))) {
            return $identity;
        }

        return null;
    }

    /**
     * Get the value of usernameField
     */
    public function getUsernameField(): string
    {
        return $this->usernameField;
    }

    /**
     * Get the value of passwordField
     */
    public function getPasswordField(): string
    {
        return $this->passwordField;
    }

    /**
     * Get the value of sessionKey
     */
    public function getSessionKey(): string
    {
        return $this->sessionKey;
    }

    /**
     * Get the value of loginPath
     */
    public function getLoginPath(): string
    {
        return $this->loginPath;
    }

    /**
     * Set the value of loginPath
     */
    public function setLoginPath(string $loginPath): static
    {
        $this->loginPath = $loginPath;

        return $this;
    }

    /**
     * Get the value of session
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * Get the value of identityService
     */
    public function getIdentityService(): IdentityServiceInterface
    {
        return $this->identityService;
    }
}
