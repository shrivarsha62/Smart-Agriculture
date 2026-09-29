<?php
require 'db.php';
$pageTitle = 'Crops';
$error = '';
$f = ['crop_id' => '', 'field_id' => '', 'crop_name' => '', 'season' => '', 'duration' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            $id = (int)$_POST['delete_id'];
            $fe = (int)db_val("SELECT COUNT(*) FROM Fertilizer WHERE crop_id = ?", [$id]);
            $di = (int)db_val("SELECT COUNT(*) FROM Disease WHERE crop_id = ?", [$id]);
            $ma = (int)db_val("SELECT COUNT(*) FROM Market WHERE crop_id = ?", [$id]);
            if ($fe + $di + $ma > 0) go('crops.php', "Cannot delete: this crop still has $fe fertilizer, $di disease and $ma market record(s). Delete those first.", 'err');
            db_run("DELETE FROM Crop WHERE crop_id = ?", [$id]);
            go('crops.php', 'Crop deleted.');
        } else {
            $id       = (int)($_POST['crop_id'] ?? 0);
            $field    = (int)($_POST['field_id'] ?? 0);
            $name     = trim($_POST['crop_name'] ?? '');
            $season   = trim($_POST['season'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            if ($field <= 0) $error = 'Please select a field.';
            elseif ($name === '' || strlen($name) > 100) $error = 'Crop name is required (max 100 characters).';
            elseif (strlen($season) > 50) $error = 'Season is too long (max 50 characters).';
            elseif ($duration !== '' && (!ctype_digit($duration) || (int)$duration <= 0)) $error = 'Duration must be a whole number of days (greater than 0).';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Crop SET field_id = ?, crop_name = ?, season = ?, duration = ? WHERE crop_id = ?", [$field, $name, nul($season), nul($duration), $id]);
                    go('crops.php', 'Crop updated.');
                } else {
                    db_run("INSERT INTO Crop (field_id, crop_name, season, duration) VALUES (?, ?, ?, ?)", [$field, $name, nul($season), nul($duration)]);
                    go('crops.php', 'Crop added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Crop WHERE crop_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

$q = trim($_GET['q'] ?? '');
$fid = (int)($_GET['field'] ?? 0);
$sql = "SELECT c.crop_id, c.field_id, fr.name AS farmer, c.crop_name, c.season, c.duration
        FROM Crop c JOIN Field f ON c.field_id = f.field_id JOIN Farmer fr ON f.farmer_id = fr.farmer_id
        WHERE (c.crop_name LIKE ? OR c.season LIKE ?)";
$params = ['%' . $q . '%', '%' . $q . '%'];
if ($fid > 0) { $sql .= " AND c.field_id = ?"; $params[] = $fid; }
$rows = db_all($sql . " ORDER BY c.crop_id", $params);
$fields = field_rows();
require 'header.php';
?>
<h1>Crops</h1>

<div class="card">
  <h2><?= $f['crop_id'] ? 'Edit Crop' : 'Add Crop' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="crop_id" value="<?= h($f['crop_id']) ?>">
    <label>Field *
      <select name="field_id" required><option value="">-- Select field --</option><?= opts($fields, $f['field_id']) ?></select>
    </label>
    <label>Crop name *<input type="text" name="crop_name" maxlength="100" required value="<?= h($f['crop_name']) ?>"></label>
    <label>Season<input type="text" name="season" list="seasons" maxlength="50" value="<?= h($f['season']) ?>">
      <datalist id="seasons"><option value="Summer"><option value="Monsoon"><option value="Winter"></datalist>
    </label>
    <label>Duration (days)<input type="number" name="duration" min="1" step="1" value="<?= h($f['duration']) ?>"></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['crop_id'] ? 'Update Crop' : 'Add Crop' ?></button>
      <?php if ($f['crop_id']): ?><a class="btn btn-gray" href="crops.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Crops</h2>
  <form method="get" class="search-bar">
    <div><label>Search (crop or season)<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <div><label>Field<select name="field"><option value="0">All fields</option><?= opts($fields, $fid) ?></select></label></div>
    <button class="btn btn-blue" type="submit">Search / Filter</button>
    <a class="btn btn-gray" href="crops.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>Crop ID</th><th>Field</th><th>Crop Name</th><th>Season</th><th>Duration (days)</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['crop_id']) ?></td>
      <td>Field #<?= h($r['field_id']) ?> (<?= h($r['farmer']) ?>)</td>
      <td><?= h($r['crop_name']) ?></td><td><?= h($r['season']) ?></td><td><?= h($r['duration']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="crops.php?edit=<?= (int)$r['crop_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this crop?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['crop_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No crops found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
