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

namespace Lightning\Translator\Middleware;

use Locale;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * LocaleDetectorMiddleware
 */
class LocaleDetectorMiddleware implements MiddlewareInterface
{
    private string $defaultLocale;
    private array $locales = [];

    /**
     * Constructor
     */
    public function __construct(string $defaultLocale, array $locales = [])
    {
        $this->defaultLocale = $defaultLocale;
        $this->locales = $locales;
    }

    /**
     * Detect the locale from the Request headers
     *
     * @see https://www.php.net/manual/en/locale.acceptfromhttp.php
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        // TODO: translator should handle closest match
        $locale = Locale::acceptFromHttp($request->getHeaderLine('Accept-Language'));

        if ($locale && $this->locales) {
            $locale = Locale::lookup($this->locales, $locale, false);
        }

        return $handler->handle($request->withAttribute('locale', $locale ?: $this->defaultLocale));
    }
}
