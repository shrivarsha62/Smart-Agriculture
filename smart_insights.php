<?php
require 'db.php';
$pageTitle = 'Smart Crop Insights';

// Dropdown choices come from the database
$cropList   = db_all("SELECT DISTINCT crop_name FROM Crop ORDER BY crop_name");
$soilList   = db_all("SELECT DISTINCT soil_type FROM Field WHERE soil_type IS NOT NULL AND soil_type <> '' ORDER BY soil_type");
$seasonList = db_all("SELECT DISTINCT season FROM Crop WHERE season IS NOT NULL AND season <> '' ORDER BY season");

$crop   = trim($_GET['crop'] ?? '');
$soil   = trim($_GET['soil'] ?? '');
$season = trim($_GET['season'] ?? '');
$matches = [];
$error = '';

if ($crop !== '') {
    try {
        // Crops that match the user's choice (soil/season are optional filters)
        $matches = db_all(
            "SELECT c.crop_id, c.field_id, c.crop_name, c.season, c.duration, f.soil_type, f.area, fr.name AS farmer
             FROM Crop c
             JOIN Field f ON c.field_id = f.field_id
             JOIN Farmer fr ON f.farmer_id = fr.farmer_id
             WHERE c.crop_name = ? AND (? = '' OR f.soil_type = ?) AND (? = '' OR c.season = ?)
             ORDER BY c.crop_id",
            [$crop, $soil, $soil, $season, $season]);

        if ($matches) {
            // IDs are cast to integers, so building the IN (...) list is safe
            $cropIds  = array_map('intval', array_column($matches, 'crop_id'));
            $fieldIds = array_values(array_unique(array_map('intval', array_column($matches, 'field_id'))));
            $inC = implode(',', $cropIds);
            $inF = implode(',', $fieldIds);

            $ferts = db_all("SELECT fe.fertilizer_name, fe.recommended_quantity, fe.application_date, fe.crop_id
                             FROM Fertilizer fe WHERE fe.crop_id IN ($inC) ORDER BY fe.fertilizer_name");
            $diseases = db_all("SELECT d.disease_name, d.symptoms, d.recommended_treatment, d.crop_id
                                FROM Disease d WHERE d.crop_id IN ($inC) ORDER BY d.disease_name");
            $irrigation = db_all("SELECT irrigation_method, COUNT(*) AS times_used, ROUND(AVG(water_quantity),2) AS avg_water_quantity
                                  FROM Irrigation WHERE field_id IN ($inF)
                                  GROUP BY irrigation_method ORDER BY times_used DESC");
            $market = db_all("SELECT market_name, price_per_kg, price_date, crop_id
                              FROM Market WHERE crop_id IN ($inC) ORDER BY price_date DESC");
            $price = db_one("SELECT ROUND(AVG(price_per_kg),2) AS avg_price, MIN(price_per_kg) AS min_price, MAX(price_per_kg) AS max_price
                             FROM Market WHERE crop_id IN ($inC)");
            $avgDuration = db_val("SELECT ROUND(AVG(duration)) FROM Crop WHERE crop_id IN ($inC)");
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}
require 'header.php';
?>
<h1>Smart Crop Insights</h1>
<p class="muted">Choose a crop (and optionally soil type and season). The page reads the database and shows fertilizer, disease, irrigation and market information for matching crop records.</p>

<div class="card">
  <form method="get" class="form-grid">
    <label>Crop *
      <select name="crop" required><option value="">-- Select crop --</option>
        <?php foreach ($cropList as $r): ?>
          <option value="<?= h($r['crop_name']) ?>" <?= $crop === $r['crop_name'] ? 'selected' : '' ?>><?= h($r['crop_name']) ?></option>
        <?php endforeach; ?>
      </select></label>
    <label>Soil type
      <select name="soil"><option value="">Any soil</option>
        <?php foreach ($soilList as $r): ?>
          <option value="<?= h($r['soil_type']) ?>" <?= $soil === $r['soil_type'] ? 'selected' : '' ?>><?= h($r['soil_type']) ?></option>
        <?php endforeach; ?>
      </select></label>
    <label>Season
      <select name="season"><option value="">Any season</option>
        <?php foreach ($seasonList as $r): ?>
          <option value="<?= h($r['season']) ?>" <?= $season === $r['season'] ? 'selected' : '' ?>><?= h($r['season']) ?></option>
        <?php endforeach; ?>
      </select></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit">Get Insights</button>
      <a class="btn btn-gray" href="smart_insights.php">Reset</a>
    </div>
  </form>
</div>

<?php if ($error !== ''): ?>
  <div class="alert bad"><?= h($error) ?></div>
<?php elseif ($crop !== '' && !$matches): ?>
  <div class="alert bad">No records found for <b><?= h($crop) ?></b><?= $soil !== '' ? ' on ' . h($soil) : '' ?><?= $season !== '' ? ' in ' . h($season) : '' ?>. Try choosing "Any soil" or "Any season".</div>
<?php elseif ($matches): ?>

  <div class="stats">
    <div class="stat"><div class="num"><?= count($matches) ?></div><div class="lbl">Matching crop records</div></div>
    <div class="stat"><div class="num"><?= h($avgDuration) ?></div><div class="lbl">Average duration (days)</div></div>
    <div class="stat"><div class="num"><?= $price && $price['avg_price'] !== null ? h($price['avg_price']) : '-' ?></div><div class="lbl">Average market price / kg</div></div>
  </div>

  <div class="card"><h2>Matching Crop Records</h2><?php show_table($matches ? array_map(fn($m) => [
      'Crop ID' => $m['crop_id'], 'Crop' => $m['crop_name'], 'Field' => $m['field_id'], 'Farmer' => $m['farmer'],
      'Soil' => $m['soil_type'], 'Area' => $m['area'], 'Season' => $m['season'], 'Duration (days)' => $m['duration']], $matches) : []); ?></div>

  <div class="card"><h2>Fertilizer Information</h2>
    <?php show_table(array_map(fn($r) => ['Fertilizer' => $r['fertilizer_name'], 'Recommended Qty' => $r['recommended_quantity'], 'Application Date' => $r['application_date']], $ferts)); ?></div>

  <div class="card"><h2>Common Diseases, Symptoms and Treatment</h2>
    <?php show_table(array_map(fn($r) => ['Disease' => $r['disease_name'], 'Symptoms' => $r['symptoms'], 'Recommended Treatment' => $r['recommended_treatment']], $diseases)); ?></div>

  <div class="card"><h2>Irrigation Information (fields growing this crop)</h2>
    <?php show_table($irrigation); ?></div>

  <div class="card"><h2>Market Price</h2>
    <?php if ($price && $price['avg_price'] !== null): ?>
      <p>Lowest: <b><?= h($price['min_price']) ?></b> &nbsp; Highest: <b><?= h($price['max_price']) ?></b> &nbsp; Average: <b><?= h($price['avg_price']) ?></b> (per kg)</p>
    <?php endif; ?>
    <?php show_table(array_map(fn($r) => ['Market' => $r['market_name'], 'Price per kg' => $r['price_per_kg'], 'Date' => $r['price_date']], $market)); ?></div>
<?php endif; ?>
<?php require 'footer.php'; ?>
