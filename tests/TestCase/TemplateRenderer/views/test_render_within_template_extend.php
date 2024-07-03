<?php
/**
 * @var \Lightning\TemplateRender\TemplateRenderer $this
 */
$this->extend('layouts/div');
?>
<?= $this->render('snippets/js') ?>
<h1>Render Within Template<h1>
<?= $this->render('snippets/css') ?>
