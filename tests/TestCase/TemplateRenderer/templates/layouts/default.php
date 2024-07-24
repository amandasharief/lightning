<!doctype html>
<html lang="en">
  <head>
    <title><?= $title ?? 'Web Application' ?></title>
  </head>
  <body>
    <?= $this->fetch('content') ?>
  </body>
</html>