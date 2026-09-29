<?php
require 'db.php';
$pageTitle = 'Fields';
$error = '';
$f = ['field_id' => '', 'farmer_id' => '', 'area' => '', 'soil_type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            $id = (int)$_POST['delete_id'];
            $crops = (int)db_val("SELECT COUNT(*) FROM Crop WHERE field_id = ?", [$id]);
            $irr   = (int)db_val("SELECT COUNT(*) FROM Irrigation WHERE field_id = ?", [$id]);
            if ($crops > 0 || $irr > 0) go('fields.php', "Cannot delete: this field still has $crops crop(s) and $irr irrigation record(s). Delete those first.", 'err');
            db_run("DELETE FROM Field WHERE field_id = ?", [$id]);
            go('fields.php', 'Field deleted.');
        } else {
            $id     = (int)($_POST['field_id'] ?? 0);
            $farmer = (int)($_POST['farmer_id'] ?? 0);
            $area   = trim($_POST['area'] ?? '');
            $soil   = trim($_POST['soil_type'] ?? '');
            if ($farmer <= 0) $error = 'Please select a farmer.';
            elseif (!is_numeric($area) || $area <= 0) $error = 'Area must be a number greater than 0.';
            elseif ($soil === '' || strlen($soil) > 50) $error = 'Soil type is required (max 50 characters).';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Field SET farmer_id = ?, area = ?, soil_type = ? WHERE field_id = ?", [$farmer, $area, $soil, $id]);
                    go('fields.php', 'Field updated.');
                } else {
                    db_run("INSERT INTO Field (farmer_id, area, soil_type) VALUES (?, ?, ?)", [$farmer, $area, $soil]);
                    go('fields.php', 'Field added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Field WHERE field_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

// Search + filter
$q = trim($_GET['q'] ?? '');
$fid = (int)($_GET['farmer'] ?? 0);
$sql = "SELECT f.field_id, fr.name, f.area, f.soil_type FROM Field f JOIN Farmer fr ON f.farmer_id = fr.farmer_id WHERE (fr.name LIKE ? OR f.soil_type LIKE ?)";
$params = ['%' . $q . '%', '%' . $q . '%'];
if ($fid > 0) { $sql .= " AND f.farmer_id = ?"; $params[] = $fid; }
$rows = db_all($sql . " ORDER BY f.field_id", $params);
$farmers = farmer_rows();
require 'header.php';
?>
<h1>Fields</h1>

<div class="card">
  <h2><?= $f['field_id'] ? 'Edit Field' : 'Add Field' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="field_id" value="<?= h($f['field_id']) ?>">
    <label>Farmer *
      <select name="farmer_id" required><option value="">-- Select farmer --</option><?= opts($farmers, $f['farmer_id']) ?></select>
    </label>
    <label>Area (acres) *<input type="number" name="area" step="0.01" min="0.01" required value="<?= h($f['area']) ?>"></label>
    <label>Soil type *<input type="text" name="soil_type" list="soils" maxlength="50" required value="<?= h($f['soil_type']) ?>">
      <datalist id="soils"><option value="Red Soil"><option value="Black Soil"><option value="Sandy Soil"><option value="Loamy Soil"><option value="Clay Soil"></datalist>
    </label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['field_id'] ? 'Update Field' : 'Add Field' ?></button>
      <?php if ($f['field_id']): ?><a class="btn btn-gray" href="fields.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Fields</h2>
  <form method="get" class="search-bar">
    <div><label>Search (farmer or soil)<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <div><label>Farmer<select name="farmer"><option value="0">All farmers</option><?= opts($farmers, $fid) ?></select></label></div>
    <button class="btn btn-blue" type="submit">Search / Filter</button>
    <a class="btn btn-gray" href="fields.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>Field ID</th><th>Farmer</th><th>Area (acres)</th><th>Soil Type</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['field_id']) ?></td><td><?= h($r['name']) ?></td><td><?= h($r['area']) ?></td><td><?= h($r['soil_type']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="fields.php?edit=<?= (int)$r['field_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this field?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['field_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="muted">No fields found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
