<?php
// header.php - top of every page: sidebar menu + message boxes.
$current = basename($_SERVER['PHP_SELF']);
$nav = [
    'index.php'          => 'Dashboard',
    'farmers.php'        => 'Farmers',
    'fields.php'         => 'Fields',
    'crops.php'          => 'Crops',
    'irrigation.php'     => 'Irrigation',
    'fertilizers.php'    => 'Fertilizers',
    'diseases.php'       => 'Diseases',
    'market.php'         => 'Market Prices',
    'smart_insights.php' => 'Smart Crop Insights',
    'dbms_features.php'  => 'DBMS Features',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Smart Agriculture') ?> - Smart Agriculture Management System</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
  <nav class="sidebar">
    <div class="brand">Smart Agriculture<br><small>Management System</small></div>
    <?php foreach ($nav as $file => $label): ?>
      <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <main class="content">
    <?php if (!empty($_GET['msg'])): ?><div class="alert ok"><?= h($_GET['msg']) ?></div><?php endif; ?>
    <?php if (!empty($_GET['err'])): ?><div class="alert bad"><?= h($_GET['err']) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert bad"><?= h($error) ?></div><?php endif; ?>
