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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lightning\Translator\TranslatorInterface;

/**
 * LocaleSetterMiddleware
 */
class LocaleSetterMiddleware implements MiddlewareInterface
{
    private TranslatorInterface $translator;

    /**
     * Constructor
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Gets the locale attribute from the Request and sets this on the translator if available
     * @TODO: Locale from URL Middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = $request->getAttribute('locale');

        if ($locale) {
            $this->translator->setLocale($locale);
        }

        return $handler->handle($request);
    }
}
