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

namespace Lightning\TestSuite;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * ServerRequestFactory - This does more than the PSR specifications, so we can create ServerRequests for testing.
 */
class ServerRequestFactory
{
    private ServerRequestFactoryInterface $serverRequestFactory;

    /**
     * Constructor
     *
     * @param ServerRequestFactoryInterface $serverRequestFactory
     */
    public function __construct(ServerRequestFactoryInterface $serverRequestFactory)
    {
        $this->serverRequestFactory = $serverRequestFactory;
    }

    /**
     * Factory Method
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ServerRequestInterface
     */
    public function create(string $method, string $uri, array $options = []): ServerRequestInterface
    {
        $options += ['serverParams' => [], 'headers' => [],'cookies' => [],'post' => null,'files' => []];

        $serverRequest = $this->serverRequestFactory->createServerRequest($method, $uri, $options['serverParams']);

        foreach ($options['headers'] as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader((string) $name, $value);
        }

        $serverRequest = $serverRequest
            ->withProtocolVersion('1.1')
            ->withCookieParams($options['cookies'])
            ->withQueryParams($this->parseGET($uri))
            ->withParsedBody($options['post'])
            ->withUploadedFiles($options['files']);

        return $serverRequest;
    }

    /**
     * Parses the GET params from the URI
     *
     * @param string $uri
     * @return array
     */
    private function parseGET(string $uri): array
    {
        $get = [];
        if (strpos($uri, '?') !== false) {
            list($uri, $queryString) = explode('?', $uri);
            parse_str($queryString, $get);
        }

        return $get;
    }
}
