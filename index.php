<?php
require 'db.php';
$pageTitle = 'Dashboard';

// label => [table name, page to open]. Table names are fixed here (not user input).
$items = [
    'Total Farmers'           => ['Farmer',     'farmers.php'],
    'Total Fields'            => ['Field',      'fields.php'],
    'Total Crops'             => ['Crop',       'crops.php'],
    'Total Irrigation Records'=> ['Irrigation', 'irrigation.php'],
    'Total Fertilizers'       => ['Fertilizer', 'fertilizers.php'],
    'Total Diseases'          => ['Disease',    'diseases.php'],
    'Total Market Records'    => ['Market',     'market.php'],
];
require 'header.php';
?>
<h1>Dashboard</h1>
<p class="muted">Live counts read from the MySQL database. Click a card to open that module.</p>
<div class="stats">
<?php foreach ($items as $label => $info): ?>
  <a class="stat" href="<?= $info[1] ?>">
    <div class="num"><?= (int)db_val("SELECT COUNT(*) FROM " . $info[0]) ?></div>
    <div class="lbl"><?= h($label) ?></div>
  </a>
<?php endforeach; ?>
</div>

<div class="card">
  <h2>Modules</h2>
  <div class="stats">
    <a class="stat" href="farmers.php"><b>Farmers</b><br><span class="muted">Add, edit, delete, search</span></a>
    <a class="stat" href="fields.php"><b>Fields</b><br><span class="muted">Land owned by farmers</span></a>
    <a class="stat" href="crops.php"><b>Crops</b><br><span class="muted">Crops grown in fields</span></a>
    <a class="stat" href="irrigation.php"><b>Irrigation</b><br><span class="muted">Water usage records</span></a>
    <a class="stat" href="fertilizers.php"><b>Fertilizers</b><br><span class="muted">Fertilizer per crop</span></a>
    <a class="stat" href="diseases.php"><b>Diseases</b><br><span class="muted">Symptoms and treatment</span></a>
    <a class="stat" href="market.php"><b>Market Prices</b><br><span class="muted">Price per kg</span></a>
    <a class="stat" href="smart_insights.php"><b>Smart Crop Insights</b><br><span class="muted">Recommendations from data</span></a>
    <a class="stat" href="dbms_features.php"><b>DBMS Features</b><br><span class="muted">JOIN, GROUP BY, trigger...</span></a>
  </div>
</div>
<?php require 'footer.php'; ?>
