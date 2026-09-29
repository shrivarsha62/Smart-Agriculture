<?php
require 'db.php';
$pageTitle = 'Diseases';
$error = '';
$f = ['disease_id' => '', 'crop_id' => '', 'disease_name' => '', 'symptoms' => '', 'recommended_treatment' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            db_run("DELETE FROM Disease WHERE disease_id = ?", [(int)$_POST['delete_id']]);
            go('diseases.php', 'Disease record deleted.');
        } else {
            $id        = (int)($_POST['disease_id'] ?? 0);
            $crop      = (int)($_POST['crop_id'] ?? 0);
            $name      = trim($_POST['disease_name'] ?? '');
            $symptoms  = trim($_POST['symptoms'] ?? '');
            $treatment = trim($_POST['recommended_treatment'] ?? '');
            if ($crop <= 0) $error = 'Please select a crop.';
            elseif ($name === '' || strlen($name) > 100) $error = 'Disease name is required (max 100 characters).';
            elseif (strlen($symptoms) > 255 || strlen($treatment) > 255) $error = 'Symptoms and treatment can be at most 255 characters each.';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Disease SET crop_id = ?, disease_name = ?, symptoms = ?, recommended_treatment = ? WHERE disease_id = ?", [$crop, $name, nul($symptoms), nul($treatment), $id]);
                    go('diseases.php', 'Disease record updated.');
                } else {
                    db_run("INSERT INTO Disease (crop_id, disease_name, symptoms, recommended_treatment) VALUES (?, ?, ?, ?)", [$crop, $name, nul($symptoms), nul($treatment)]);
                    go('diseases.php', 'Disease record added.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Disease WHERE disease_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

$q = trim($_GET['q'] ?? '');
$cid = (int)($_GET['crop'] ?? 0);
$sql = "SELECT d.*, c.crop_name FROM Disease d JOIN Crop c ON d.crop_id = c.crop_id WHERE (d.disease_name LIKE ? OR d.symptoms LIKE ?)";
$params = ['%' . $q . '%', '%' . $q . '%'];
if ($cid > 0) { $sql .= " AND d.crop_id = ?"; $params[] = $cid; }
$rows = db_all($sql . " ORDER BY d.disease_id", $params);
$crops = crop_rows();
require 'header.php';
?>
<h1>Diseases</h1>

<div class="card">
  <h2><?= $f['disease_id'] ? 'Edit Disease' : 'Add Disease' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="disease_id" value="<?= h($f['disease_id']) ?>">
    <label>Crop *
      <select name="crop_id" required><option value="">-- Select crop --</option><?= opts($crops, $f['crop_id']) ?></select>
    </label>
    <label>Disease name *<input type="text" name="disease_name" maxlength="100" required value="<?= h($f['disease_name']) ?>"></label>
    <label>Symptoms<input type="text" name="symptoms" maxlength="255" value="<?= h($f['symptoms']) ?>"></label>
    <label>Recommended treatment<input type="text" name="recommended_treatment" maxlength="255" value="<?= h($f['recommended_treatment']) ?>"></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['disease_id'] ? 'Update Disease' : 'Add Disease' ?></button>
      <?php if ($f['disease_id']): ?><a class="btn btn-gray" href="diseases.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Diseases</h2>
  <form method="get" class="search-bar">
    <div><label>Search (disease or symptoms)<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <div><label>Crop<select name="crop"><option value="0">All crops</option><?= opts($crops, $cid) ?></select></label></div>
    <button class="btn btn-blue" type="submit">Search / Filter</button>
    <a class="btn btn-gray" href="diseases.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>ID</th><th>Crop</th><th>Disease</th><th>Symptoms</th><th>Recommended Treatment</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['disease_id']) ?></td><td><?= h($r['crop_name']) ?> (Crop #<?= h($r['crop_id']) ?>)</td>
      <td><?= h($r['disease_name']) ?></td><td><?= h($r['symptoms']) ?></td><td><?= h($r['recommended_treatment']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="diseases.php?edit=<?= (int)$r['disease_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this disease record?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['disease_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="6" class="muted">No disease records found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
