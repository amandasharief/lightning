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

namespace Lightning\Translator;

interface TranslatorInterface
{
    /**
     * Sets the locale for the translator
     */
    public function setLocale(string $locale): static;

    /**
     * Gets the locale for the translator
     */
    public function getLocale(): string;

    /**
     * Gets an instance of the translator with a different locale
     */
    public function withLocale(string $locale): static;

    /**
     * The translate method MUST always return a string
     */
    public function translate(?string $message, array $values = []): string;
}
