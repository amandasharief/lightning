<?php $this->section('header') ?>
<h1>Header</h1>
<?php $this->end() ?>
<?php $this->section('footer') ?>
<h1>Footer</h1>
<?php $this->end() ?>
<?= $this->fetch('header') ?>
<div>Main content</div>
<?= $this->fetch('footer') ?>
