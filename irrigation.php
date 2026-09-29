<?php
require 'db.php';
$pageTitle = 'Irrigation';
$error = '';
$f = ['irrigation_id' => '', 'field_id' => '', 'irrigation_date' => date('Y-m-d'), 'water_quantity' => '', 'irrigation_method' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            db_run("DELETE FROM Irrigation WHERE irrigation_id = ?", [(int)$_POST['delete_id']]);
            go('irrigation.php', 'Irrigation record deleted.');
        } else {
            $id     = (int)($_POST['irrigation_id'] ?? 0);
            $field  = (int)($_POST['field_id'] ?? 0);
            $date   = trim($_POST['irrigation_date'] ?? '');
            $qty    = trim($_POST['water_quantity'] ?? '');
            $method = trim($_POST['irrigation_method'] ?? '');
            if ($field <= 0) $error = 'Please select a field.';
            elseif (!valid_date($date)) $error = 'Please enter a valid date.';
            elseif (!is_numeric($qty) || $qty <= 0) $error = 'Water quantity must be a number greater than 0.';
            elseif ($method === '' || strlen($method) > 50) $error = 'Irrigation method is required (max 50 characters).';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Irrigation SET field_id = ?, irrigation_date = ?, water_quantity = ?, irrigation_method = ? WHERE irrigation_id = ?", [$field, $date, $qty, $method, $id]);
                    go('irrigation.php', 'Irrigation record updated.');
                } else {
                    db_run("INSERT INTO Irrigation (field_id, irrigation_date, water_quantity, irrigation_method) VALUES (?, ?, ?, ?)", [$field, $date, $qty, $method]);
                    go('irrigation.php', 'Irrigation record added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Irrigation WHERE irrigation_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

$fid = (int)($_GET['field'] ?? 0);
$sql = "SELECT i.*, fr.name AS farmer FROM Irrigation i JOIN Field f ON i.field_id = f.field_id JOIN Farmer fr ON f.farmer_id = fr.farmer_id";
$params = [];
if ($fid > 0) { $sql .= " WHERE i.field_id = ?"; $params[] = $fid; }
$rows = db_all($sql . " ORDER BY i.irrigation_date DESC, i.irrigation_id DESC", $params);
$fields = field_rows();
require 'header.php';
?>
<h1>Irrigation</h1>

<div class="card">
  <h2><?= $f['irrigation_id'] ? 'Edit Irrigation Record' : 'Add Irrigation Record' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="irrigation_id" value="<?= h($f['irrigation_id']) ?>">
    <label>Field *
      <select name="field_id" required><option value="">-- Select field --</option><?= opts($fields, $f['field_id']) ?></select>
    </label>
    <label>Date *<input type="date" name="irrigation_date" required value="<?= h($f['irrigation_date']) ?>"></label>
    <label>Water quantity (litres) *<input type="number" name="water_quantity" step="0.01" min="0.01" required value="<?= h($f['water_quantity']) ?>"></label>
    <label>Method *<input type="text" name="irrigation_method" list="methods" maxlength="50" required value="<?= h($f['irrigation_method']) ?>">
      <datalist id="methods"><option value="Drip"><option value="Sprinkler"><option value="Flood"><option value="Canal"></datalist>
    </label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['irrigation_id'] ? 'Update Record' : 'Add Record' ?></button>
      <?php if ($f['irrigation_id']): ?><a class="btn btn-gray" href="irrigation.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Irrigation Records</h2>
  <form method="get" class="search-bar">
    <div><label>Filter by field<select name="field"><option value="0">All fields</option><?= opts($fields, $fid) ?></select></label></div>
    <button class="btn btn-blue" type="submit">Filter</button>
    <a class="btn btn-gray" href="irrigation.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>ID</th><th>Field</th><th>Date</th><th>Water Quantity</th><th>Method</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['irrigation_id']) ?></td>
      <td>Field #<?= h($r['field_id']) ?> (<?= h($r['farmer']) ?>)</td>
      <td><?= h($r['irrigation_date']) ?></td><td><?= h($r['water_quantity']) ?></td><td><?= h($r['irrigation_method']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="irrigation.php?edit=<?= (int)$r['irrigation_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this irrigation record?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['irrigation_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No irrigation records found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
