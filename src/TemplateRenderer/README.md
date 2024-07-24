# Template Renderer

Template Renderer is a PHP templating engine. Template files are regular PHP files and you can use the [alternative PHP syntax](https://www.php.net/manual/en/control-structures.alternative-syntax.php) for control structures and output. Furthermore, you can add a compiler which can give additional features such as autoescaping output through the use of double curly brackets `{{ $user->name }}` and  control structure tags like `@foreach ($fruits as $fruit)` to make code easier without having to do `<?php foreach($fruits as $fruit):  >`.

> The constructor has been purposely left empty to allow you to add helper objects which can be accessed from within the view template whilst rendering, offering a nice way implement functionality within your views, and can be configured easily in the DI container.

## Usage

To create the `TemplateRenderer` object. 

```php
$templateRenderer = new TemplateRenderer();
```

To render a template using a full path just make sure the line starts with `/`.

```php
echo $templateRenderer->render('/var/www/templates/articles/index.php');
```

The Template Renderer also supports a short form e.g. `articles/index`, you will need to set the path on the template renderer and the `.php` extension will be added automaically by default. To change this behavior you can set or remove the file extension using the `setFileExtension` method.

```php
$templateRenderer = (new TemplateRenderer())
  ->setPath('/var/www/templates')
  ->setFileExtension('php');
  
echo $templateRenderer->render('articles/index'); // /var/www/templates/articles/index.php
```

To pass variables to a template use the second argument

```php
$templateRenderer->render('users/view', ['name' => 'Jon Smith']); // 
```

Variables will be now available in the template it is rendering. To echo the variable use the PHP short tag

```php
<h1><?= $article->title ?></h1>
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

## Layouts

Create a template that you want to use as a layout, and use the `fetch` method to get the `content` block.

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
    <title><?= $title =></title>
  </head>
  <body>
    <?= $this->fetch('content') ?>
  </body>
</html>
```

Then to use this use this layout, call the `layout` method in the template that you want to use it, now whenever this new template is rendered it will be rendered within the template above which is used as the layout

```php
<?php
/**
 * @var \Lightning\TemplateRenderer\TemplateRenderer $this
 */
$this->layout('layouts/default');
?>
<h1>Hello World</h1>
```

## Rendering Partial Templates

Occasionally you might need to break up some code into smaller reusuable pieces that can be called from other templates. A partial template is just a template and does not support layouts. You can render a partial template by calling the `include` method from within a template.

Create a template, this will be our partial

```php
<!-- partials/logged_in_users.php -->
<ul>
<?php foreach ($users as $user): ?>
  <li><?= $this->escape($user->name) ?></li>
<?php endforeach; ?>
</ul>
```

Now in your normal template call the `include` method, remembering to echo the return

```php
<!-- index.php -->
<?php
/**
 * @var \Lightning\TemplateRenderer\TemplateRenderer $this
 */
$this->layout('layouts/default');
?>
<h1>Dashboard</h1>
<?= $this->include('partials/logged_in_users.php') ?>
```

## Compiler

You can add extra template language features by adding the `TemplateCompiler` to the `TemplateRenderer` object. The compiled template is generated when the template is rendered and the template has been changed since the last compile, so the template is only compiled once (by default it uses the system temp dir, so on linux systems it cleans out non accessed temp files every 10 days). You can also create your own compilers by implementing the `TemplateCompilerInterface`.


```php
$templateRenderer = new TemplateRenderer();
$templateRenderer->setCompiler(new TemplateCompiler());
```

### Echoing Content

After add this you be able to use automatic escaping which works with variables or functions or whatever returns a value.

```php
{{ $variable }}
```

The compiled code will look like this `<?= $this->escape($variable) ?>`.

### Control Structure Tags

The control structure tags use the same naming convention as the PHP ones. The `TemplateCompiler`, supports `for`, `foreach`,`while` and `if` control structures. The `switch` control structure has been purposely left out.

```php 
@for ($i = 1; $i <= 10; $i++) 
    <?= $i ?>
@endfor
```

This will be compiled to:

```php
<?php for ($i = 1; $i <= 10; $i++): ?>
    <?= $i ?>
<?php endfor; ?>
```




```php
@foreach ($users as $user)
  {{ $user->name }}
@endforeach
```

```php
@while ($i <= 10)
    <?php 
        echo $i;
        $i++;
    ?>
@endwhile
```

```php
@if (count($records) > 1)
    <?php echo 'You have many records' ?>
@elseif (count($records) === 1)
    <?php echo 'You have 1 record' ?>
@else
    <?php echo 'You have no records' ?>
@endif
```