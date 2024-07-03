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

namespace Lightning\TemplateRenderer;

use Throwable;
use Lightning\TemplateRenderer\Exception\TemplateRendererException;

class TemplateRenderer implements TemplateRendererInterface
{
    private string $charset;
    private string $cachePath;
    private ?string $fileExtension;

    private array $variables = [];
    protected ?string $extends = null;

    private int $obCurrentLevel = 0;
    private ?int $obStartLevel = null;

    /**
     * @param string $path template folder path
     * @param array $options The following options are supported
     * - charset: default:UTF-8
     * - cachePath: directory where complied templates are stored
     * - fileExtension: default: php set to null if you prefer to specifcy templates with their extension
     */
    public function __construct(private string $path, array $options = [])
    {
        $options += [
            'charset' => 'UTF-8',
            'cachePath' => sys_get_temp_dir() . '/tr_compiled',
            'fileExtension' => 'php'
        ];

        $this->charset = $options['charset'];
        $this->cachePath = $options['cachePath'];
        $this->fileExtension = $options['fileExtension'];

        if (! is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
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
    * Sets the template path
    *
    * @param string $path
    * @return static
    */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Gets thet template path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * An immutable setter, returns a new instance with the new path set
     */
    public function withPath(string $path): static
    {
        $template = clone $this;
        $template->path = $path;

        return $template;
    }

    /**
     * Sets the default file extension, e.g .php .ctp or null if you want to specificy the name
     * yourself (e.g index.php)
     *
     * @param string|null $extension
     * @return static
     */
    public function setFileExtension(?string $extension): static
    {
        $this->fileExtension = $extension;

        return $this;
    }

    /**
     * Gets the defualt file extension
     *
     * @return string|null
     */
    public function getFileExtension(): ?string
    {
        return $this->fileExtension;
    }

    /**
     * Returns a new instance with the new extension
     */
    public function withFileExtension(?string $extension): static
    {
        $template = clone $this;
        $template->fileExtension = $extension;

        return $template;
    }

    /**
     * Extend another template. Single inheritance only. This is ignored when using
     * the render function within 
     *
     * @param string $template
     * @return void
     */
    protected function extend(string $template): void
    {
        if($this->obStartLevel && $this->obCurrentLevel === $this->obStartLevel){
            $this->extends = $template;
        } 
    }

    /**
     * Renders a template and returns a response string
     *
     * @param string $path articles/index or /var/www/app/resources/views/articles/index.php
     */
    public function render(string $template, array $variables = [], array $options = []): string
    {
        /**
         * Determine output buffer starting and current positions
         */
        $this->obCurrentLevel = ob_get_level();
        if ($this->obStartLevel === null) {
            $this->obStartLevel = $this->obCurrentLevel;
        }

        $content = $this->renderTemplate($template, $variables);

        // Reset first time calling if all complete
        if ($this->obStartLevel === ob_get_level()) {
            $this->obStartLevel = null;
        }

        /**
         * Only render the extended if it is the main render call
         */
        if ($this->extends && $this->obStartLevel === null) {
            $content = $this->renderTemplate($this->extends, ['content' => $content] + $variables);
            $this->extends = null;
        }
       
        return $content;
    }

  /**
   * Renders a template (without inheritance)
   * @note this was renderPartial but since render is now functional from within templates this has been benched.
   * @param string $path
   * @param array $variables
   * @return string
   */
    private function renderTemplate(string $template, array $variables = []): string
    {
        $path = strncmp($template, '/', 1) === 0 ? $template : $this->path . '/' . trim($template, '/') . ($this->fileExtension ? '.' . $this->fileExtension : null);

        if (! is_readable($path)) {
            throw new TemplateRendererException(sprintf('Template `%s` not found', ltrim(str_replace($this->path, '', $path), '/')));
        }
        return $this->doRender($this->compile($path), $variables);
    }

    /**
     * Render logic
     */
    private function doRender(string $__filename__, array $__variables__ = []): string
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
     * Convert special characters to HTML entities
     */
    protected function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, $this->charset);
    }

    /**
     * Gets the compiled templates filename, if the view has been changed or the compiled version does not exist
     * then it will be compiled.
     */
    private function compile(string $path): string
    {
        $compiledFilename = $this->cachePath . '/' . md5($path) . '.php';

        $isCompiled = file_exists($compiledFilename) && filemtime($compiledFilename) > filemtime($path);
        if (! $isCompiled && file_put_contents($compiledFilename, $this->compileTemplate($path)) === false) {
            throw new TemplateRendererException('Error saving compiled view');
        }

        return $compiledFilename;
    }

    private function compileTemplate(string $path): string
    {
        return preg_replace('/\{\{\s(.+?)\s\}\}/', '<?= $this->escape($1) ?>', file_get_contents($path));
    }
}
