<?php
require 'db.php';
$pageTitle = 'Market Prices';
$error = '';
$f = ['market_id' => '', 'crop_id' => '', 'market_name' => '', 'price_per_kg' => '', 'price_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            db_run("DELETE FROM Market WHERE market_id = ?", [(int)$_POST['delete_id']]);
            go('market.php', 'Market record deleted.');
        } else {
            $id    = (int)($_POST['market_id'] ?? 0);
            $crop  = (int)($_POST['crop_id'] ?? 0);
            $name  = trim($_POST['market_name'] ?? '');
            $price = trim($_POST['price_per_kg'] ?? '');
            $date  = trim($_POST['price_date'] ?? '');
            if ($crop <= 0) $error = 'Please select a crop.';
            elseif ($name === '' || strlen($name) > 100) $error = 'Market name is required (max 100 characters).';
            elseif (!is_numeric($price) || $price <= 0) $error = 'Price per kg must be a number greater than 0.';
            elseif (!valid_date($date)) $error = 'Please enter a valid date.';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Market SET crop_id = ?, market_name = ?, price_per_kg = ?, price_date = ? WHERE market_id = ?", [$crop, $name, $price, $date, $id]);
                    go('market.php', 'Market record updated.');
                } else {
                    db_run("INSERT INTO Market (crop_id, market_name, price_per_kg, price_date) VALUES (?, ?, ?, ?)", [$crop, $name, $price, $date]);
                    go('market.php', 'Market record added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Market WHERE market_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

// Filter by crop NAME (so all "Tomato" records show together) and search market name
$q = trim($_GET['q'] ?? '');
$cname = trim($_GET['crop_name'] ?? '');
$sql = "SELECT m.*, c.crop_name FROM Market m JOIN Crop c ON m.crop_id = c.crop_id WHERE m.market_name LIKE ?";
$params = ['%' . $q . '%'];
if ($cname !== '') { $sql .= " AND c.crop_name = ?"; $params[] = $cname; }
$rows = db_all($sql . " ORDER BY m.price_date DESC, m.market_id DESC", $params);
$crops = crop_rows();
$cropNames = db_all("SELECT DISTINCT crop_name FROM Crop ORDER BY crop_name");
require 'header.php';
?>
<h1>Market Prices</h1>

<div class="card">
  <h2><?= $f['market_id'] ? 'Edit Market Record' : 'Add Market Record' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="market_id" value="<?= h($f['market_id']) ?>">
    <label>Crop *
      <select name="crop_id" required><option value="">-- Select crop --</option><?= opts($crops, $f['crop_id']) ?></select>
    </label>
    <label>Market name *<input type="text" name="market_name" maxlength="100" required value="<?= h($f['market_name']) ?>"></label>
    <label>Price per kg *<input type="number" name="price_per_kg" step="0.01" min="0.01" required value="<?= h($f['price_per_kg']) ?>"></label>
    <label>Price date *<input type="date" name="price_date" required value="<?= h($f['price_date']) ?>"></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['market_id'] ? 'Update Record' : 'Add Record' ?></button>
      <?php if ($f['market_id']): ?><a class="btn btn-gray" href="market.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Market Prices</h2>
  <form method="get" class="search-bar">
    <div><label>Search market name<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <div><label>Filter by crop
      <select name="crop_name"><option value="">All crops</option>
        <?php foreach ($cropNames as $cn): ?>
          <option value="<?= h($cn['crop_name']) ?>" <?= $cname === $cn['crop_name'] ? 'selected' : '' ?>><?= h($cn['crop_name']) ?></option>
        <?php endforeach; ?>
      </select></label></div>
    <button class="btn btn-blue" type="submit">Search / Filter</button>
    <a class="btn btn-gray" href="market.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>Market ID</th><th>Crop</th><th>Market Name</th><th>Price per kg</th><th>Price Date</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['market_id']) ?></td><td><?= h($r['crop_name']) ?> (Crop #<?= h($r['crop_id']) ?>)</td>
      <td><?= h($r['market_name']) ?></td><td><?= h($r['price_per_kg']) ?></td><td><?= h($r['price_date']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="market.php?edit=<?= (int)$r['market_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this market record?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['market_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No market records found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
