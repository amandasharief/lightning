<?php
/**
 * @var \Lightning\TemplateRender\TemplateRenderer $this
 */
$this->extend('layouts/div');
?>
<h1>Render template with extend</h1>
<?= $this->render('index') ?>
<?= $this->render('snippets/css') ?>