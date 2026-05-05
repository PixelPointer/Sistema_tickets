<?php
$pageTitle = $pageTitle ?? "Q-Line";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="~/public/css/landing.css">
</head>
<body>
    <?php include $viewPath; ?>
</body>
</html>