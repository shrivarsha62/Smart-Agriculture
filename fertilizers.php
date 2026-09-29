<?php
require 'db.php';
$pageTitle = 'Fertilizers';
$error = '';
$f = ['fertilizer_id' => '', 'crop_id' => '', 'fertilizer_name' => '', 'recommended_quantity' => '', 'application_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            db_run("DELETE FROM Fertilizer WHERE fertilizer_id = ?", [(int)$_POST['delete_id']]);
            go('fertilizers.php', 'Fertilizer record deleted.');
        } else {
            $id   = (int)($_POST['fertilizer_id'] ?? 0);
            $crop = (int)($_POST['crop_id'] ?? 0);
            $name = trim($_POST['fertilizer_name'] ?? '');
            $qty  = trim($_POST['recommended_quantity'] ?? '');
            $date = trim($_POST['application_date'] ?? '');
            if ($crop <= 0) $error = 'Please select a crop.';
            elseif ($name === '' || strlen($name) > 100) $error = 'Fertilizer name is required (max 100 characters).';
            elseif ($qty !== '' && (!is_numeric($qty) || $qty <= 0)) $error = 'Quantity must be a number greater than 0.';
            elseif ($date !== '' && !valid_date($date)) $error = 'Please enter a valid date.';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Fertilizer SET crop_id = ?, fertilizer_name = ?, recommended_quantity = ?, application_date = ? WHERE fertilizer_id = ?", [$crop, $name, nul($qty), nul($date), $id]);
                    go('fertilizers.php', 'Fertilizer record updated.');
                } else {
                    db_run("INSERT INTO Fertilizer (crop_id, fertilizer_name, recommended_quantity, application_date) VALUES (?, ?, ?, ?)", [$crop, $name, nul($qty), nul($date)]);
                    go('fertilizers.php', 'Fertilizer record added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Fertilizer WHERE fertilizer_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

$q = trim($_GET['q'] ?? '');
$cid = (int)($_GET['crop'] ?? 0);
$sql = "SELECT fe.*, c.crop_name FROM Fertilizer fe JOIN Crop c ON fe.crop_id = c.crop_id WHERE fe.fertilizer_name LIKE ?";
$params = ['%' . $q . '%'];
if ($cid > 0) { $sql .= " AND fe.crop_id = ?"; $params[] = $cid; }
$rows = db_all($sql . " ORDER BY fe.fertilizer_id", $params);
$crops = crop_rows();
require 'header.php';
?>
<h1>Fertilizers</h1>

<div class="card">
  <h2><?= $f['fertilizer_id'] ? 'Edit Fertilizer' : 'Add Fertilizer' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="fertilizer_id" value="<?= h($f['fertilizer_id']) ?>">
    <label>Crop *
      <select name="crop_id" required><option value="">-- Select crop --</option><?= opts($crops, $f['crop_id']) ?></select>
    </label>
    <label>Fertilizer name *<input type="text" name="fertilizer_name" maxlength="100" required value="<?= h($f['fertilizer_name']) ?>"></label>
    <label>Recommended quantity (kg)<input type="number" name="recommended_quantity" step="0.01" min="0.01" value="<?= h($f['recommended_quantity']) ?>"></label>
    <label>Application date<input type="date" name="application_date" value="<?= h($f['application_date']) ?>"></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['fertilizer_id'] ? 'Update Fertilizer' : 'Add Fertilizer' ?></button>
      <?php if ($f['fertilizer_id']): ?><a class="btn btn-gray" href="fertilizers.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Fertilizers</h2>
  <form method="get" class="search-bar">
    <div><label>Search fertilizer name<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <div><label>Crop<select name="crop"><option value="0">All crops</option><?= opts($crops, $cid) ?></select></label></div>
    <button class="btn btn-blue" type="submit">Search / Filter</button>
    <a class="btn btn-gray" href="fertilizers.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>ID</th><th>Crop</th><th>Fertilizer</th><th>Recommended Qty</th><th>Application Date</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['fertilizer_id']) ?></td><td><?= h($r['crop_name']) ?> (Crop #<?= h($r['crop_id']) ?>)</td>
      <td><?= h($r['fertilizer_name']) ?></td><td><?= h($r['recommended_quantity']) ?></td><td><?= h($r['application_date']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="fertilizers.php?edit=<?= (int)$r['fertilizer_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this fertilizer record?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['fertilizer_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No fertilizer records found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
