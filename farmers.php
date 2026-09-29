<?php
require 'db.php';
$pageTitle = 'Farmers';
$error = '';
$f = ['farmer_id' => '', 'name' => '', 'phone' => '', 'location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['delete_id'])) {
            // DELETE: refuse if the farmer still owns fields
            $id = (int)$_POST['delete_id'];
            $n = (int)db_val("SELECT COUNT(*) FROM Field WHERE farmer_id = ?", [$id]);
            if ($n > 0) go('farmers.php', "Cannot delete: this farmer still owns $n field(s). Delete those fields first.", 'err');
            db_run("DELETE FROM Farmer WHERE farmer_id = ?", [$id]);
            go('farmers.php', 'Farmer deleted.');
        } else {
            // ADD or EDIT
            $id       = (int)($_POST['farmer_id'] ?? 0);
            $name     = trim($_POST['name'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $location = trim($_POST['location'] ?? '');
            if ($name === '' || strlen($name) > 100) $error = 'Name is required (max 100 characters).';
            elseif ($phone !== '' && !preg_match('/^[0-9+\- ]{7,15}$/', $phone)) $error = 'Phone must be 7-15 characters: digits, +, - or spaces.';
            elseif (strlen($location) > 100) $error = 'Location is too long (max 100 characters).';

            if ($error === '') {
                if ($id > 0) {
                    db_run("UPDATE Farmer SET name = ?, phone = ?, location = ? WHERE farmer_id = ?", [$name, nul($phone), nul($location), $id]);
                    go('farmers.php', 'Farmer updated.');
                } else {
                    db_run("INSERT INTO Farmer (name, phone, location) VALUES (?, ?, ?)", [$name, nul($phone), nul($location)]);
                    go('farmers.php', 'Farmer added. The trigger also wrote a row into Farmer_Log.');
                }
            }
        }
    } catch (Exception $e) {
        $error = friendly_error($e);
    }
}

if ($error !== '') { $f = array_merge($f, $_POST); }
elseif (isset($_GET['edit'])) {
    $row = db_one("SELECT * FROM Farmer WHERE farmer_id = ?", [(int)$_GET['edit']]);
    if ($row) $f = $row;
}

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$rows = db_all("SELECT * FROM Farmer WHERE name LIKE ? OR phone LIKE ? OR location LIKE ? ORDER BY farmer_id", [$like, $like, $like]);
require 'header.php';
?>
<h1>Farmers</h1>

<div class="card">
  <h2><?= $f['farmer_id'] ? 'Edit Farmer' : 'Add Farmer' ?></h2>
  <form method="post" class="form-grid">
    <input type="hidden" name="farmer_id" value="<?= h($f['farmer_id']) ?>">
    <label>Name *<input type="text" name="name" maxlength="100" required value="<?= h($f['name']) ?>"></label>
    <label>Phone<input type="text" name="phone" maxlength="15" value="<?= h($f['phone']) ?>"></label>
    <label>Location<input type="text" name="location" maxlength="100" value="<?= h($f['location']) ?>"></label>
    <div class="form-actions">
      <button class="btn btn-green" type="submit"><?= $f['farmer_id'] ? 'Update Farmer' : 'Add Farmer' ?></button>
      <?php if ($f['farmer_id']): ?><a class="btn btn-gray" href="farmers.php">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>All Farmers</h2>
  <form method="get" class="search-bar">
    <div><label>Search (name, phone, location)<input type="text" name="q" value="<?= h($q) ?>"></label></div>
    <button class="btn btn-blue" type="submit">Search</button>
    <a class="btn btn-gray" href="farmers.php">Clear</a>
  </form>
  <div class="table-wrap">
  <table>
    <tr><th>ID</th><th>Name</th><th>Phone</th><th>Location</th><th>Actions</th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['farmer_id']) ?></td>
      <td><?= h($r['name']) ?></td>
      <td><?= h($r['phone']) ?></td>
      <td><?= h($r['location']) ?></td>
      <td>
        <a class="btn btn-blue btn-sm" href="farmers.php?edit=<?= (int)$r['farmer_id'] ?>">Edit</a>
        <form method="post" class="inline" onsubmit="return confirm('Delete this farmer?');">
          <input type="hidden" name="delete_id" value="<?= (int)$r['farmer_id'] ?>">
          <button class="btn btn-red btn-sm" type="submit">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="muted">No farmers found.</td></tr><?php endif; ?>
  </table>
  </div>
</div>
<?php require 'footer.php'; ?>
