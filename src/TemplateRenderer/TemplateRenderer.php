<?php declare(strict_types=1);
/**
 * LightningPHP
 * Copyright 2021 - 2024 Amanda Sharief
 *
 * Licensed under GNU Lesser General Public License
 *
 * @copyright   Copyright (c) Amanda Sharief
 * @license     https://opensource.org/licenses/LGPL-3.0 LGPL-3.0
 */

namespace Lightning\TemplateRenderer;

use Throwable;
use Lightning\TemplateRenderer\Compiler\TemplateCompilerInterface;
use Lightning\TemplateRenderer\Exception\TemplateRendererException;

/**
 * Template Renderer
 *
 * @internal Using the industry standard terminolgy, layout, partial, section
 */
class TemplateRenderer implements TemplateRendererInterface
{
    protected ?TemplateCompilerInterface $compiler = null;
    private ?string $path = null;
    private ?string $fileExtension = null;
    private array $variables = [];
    private ?string $layout = null;
    private ?string $section = null;
    private array $sections = [];

    /**
     * Sets the template path which will be preappended when using the short from e.g. articles/index
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Gets thet template path
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Sets the template file extension to be added when using the short form, e.g articles/index
     */
    public function setFileExtension(?string $fileExtension): static
    {
        $this->fileExtension = $fileExtension;

        return $this;
    }

    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    /**
     * Sets a variable that can be used in the template
     */
    public function set(string $name, mixed $value): static
    {
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * Gets a variable value from the template
     */
    public function get(string $name): mixed
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Sets the layout to be used during the rendering of this template
     */
    protected function layout(string $template): void
    {
        $this->layout = $template;
    }

    /**
     * Renders a template and layout if used and returns a response string
     *
     * @param string $path articles/index or /var/www/app/resources/views/articles/index.php
     */
    public function render(string $template, array $variables = [], array $options = []): string
    {
        $this->layout = null;

        $this->sections['content'] = $this->include($template, $variables);

        if ($this->layout) {
            $this->sections['content'] = $this->include($this->layout, $variables);
        }

        return $this->sections['content'];
    }

    /**
     * Renders a partial template (does not load layouts)
     */
    protected function include(string $template, array $variables = []): string
    {
        $path = strncmp($template, '/', 1) === 0 ? $template : $this->path . '/' . trim($template, '/') . ($this->fileExtension ? '.' . $this->fileExtension : '');
        if (! is_readable($path)) {
            throw new TemplateRendererException(sprintf('Template `%s` not found', ltrim(str_replace((string) $this->path, '', $path), '/')));
        }

        return $this->renderFile($this->compiler ? $this->compiler->compile($path) : $path, $variables);
    }

    private function renderFile(string $__filename__, array $__variables__ = []): string
    {
        extract($this->variables); # First
        extract($__variables__);

        $outputBufferLevel = ob_get_level();

        ob_start();

        try {
            include $__filename__;
        } catch (Throwable $exception) {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    /**
     * Starts capturing output
     */
    protected function section(string $name): void
    {
        $this->section = $name;
        ob_start();
    }

    /**
     * Stops capturing output
     */
    protected function end(): void
    {
        if ($this->section) {
            $this->sections[$this->section] = (string) ob_get_clean();
            $this->section = null;
        }
    }

    /**
     * Fetches a rendered section
     */
    protected function fetch(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    /**
     * Sanitize user data incase it is unsafe prior to being outputted
     */
    protected function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sets the compiler to be used
     */
    public function setCompiler(?TemplateCompilerInterface $compiler): static
    {
        $this->compiler = $compiler;

        return $this;
    }

    public function getCompiler(): ?TemplateCompilerInterface
    {
        return $this->compiler;
    }
}
