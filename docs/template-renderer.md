# Template Renderer

Template Renderer is a PHP templating engine, with built in security and caching of the complied scripts to ensure fast execution. Template files are regular PHP files and you can use the [alternative PHP syntax](https://www.php.net/manual/en/control-structures.alternative-syntax.php) for control structures and output.


## Usage

To create the `TemplateRenderer` object.

```php
$templateRenderer = new TemplateRenderer('/var/www/templates');
```

To render the template `/var/www/templates/articles/index.php`

```php
echo $templateRenderer->render('articles/index');
```

To pass variables to a template use the second argument

```php
$templateRenderer->render('users/view', ['name' => 'Jon Smith']); // 
```

Variables will be now available in the template it is rendering. To echo the variable use the PHP short tag

```php
<h1><?= $article->title ?></h1>
```

Or you can use template renderer double curly braces feature `{{ $variable }}`, this will both escape and echo out the value and helps protect your application against vulnerabilities such as XSS.

```php
<h1>{{ $article->title }}</h1>
```

PHP has alternative syntax for some of its control structures such `if`, `while`, `for`, `foreach`, and `switch`. These are what are used within the templates making it easy to write templates.

For example the `if` control structure which can be combined easily with HTML.

```php
<?php if (count($users) === 0 ): ?>
<p>No users found</p>
<?php endif; ?>
```

Here is an example of using the `foreach` within a template

```php
<ul>
<?php foreach ($articles as $article): ?>
  <li>{{ $article->title }}</li>
<?php endforeach; ?>
</ul>
```

## Template Inheritance (Layouts)

Create a template that you want to use as a layout, and make sure to echo the `content` variable. You can only extend one template, TemplateRender does not support multiple inheritance.

```php
<?php
/**
 * @var \Lightning\TemplateRenderer\TemplateRenderer $this
 */
?>
<!-- /var/www/templates/layouts/default.php -->
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Web Application</title>
  </head>
  <body>
    <?= $content ?>
  </body>
</html>
```

Then to use this use this template, call the `extends` method in the template that you want to use it, now whenever this new template is rendered it will be rendered within the template above which is used as the layout

```php
<?php
/**
 * @var \Lightning\TemplateRenderer\TemplateRenderer $this
 */
$this->extend('layouts/default');
?>
<h1>Hello World</h1>
```