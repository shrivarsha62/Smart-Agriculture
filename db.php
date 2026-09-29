<?php
// db.php - database connection + small helper functions used by every page.
// XAMPP defaults: host=localhost, user=root, no password.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // errors become exceptions we can catch

try {
    $conn = new mysqli('localhost', 'root', '', 'smart_agriculture');
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    die('<h2>Database connection failed</h2><p>' . htmlspecialchars($e->getMessage()) .
        '</p><p>Check: (1) MySQL is started in XAMPP, (2) database name is <b>smart_agriculture</b>, (3) user <b>root</b> has no password.</p>');
}

// Escape text before printing it in HTML (prevents XSS).
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Run a SELECT with prepared-statement values; returns array of rows.
function db_all($sql, $params = []) {
    global $conn;
    $st = $conn->prepare($sql);
    if ($params) { $st->bind_param(str_repeat('s', count($params)), ...$params); }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}
// First row only (or null).
function db_one($sql, $params = []) { $r = db_all($sql, $params); return $r ? $r[0] : null; }
// First column of first row (e.g. COUNT(*)).
function db_val($sql, $params = []) { $r = db_one($sql, $params); return $r ? array_values($r)[0] : null; }
// INSERT / UPDATE / DELETE with prepared values.
function db_run($sql, $params = []) {
    global $conn;
    $st = $conn->prepare($sql);
    if ($params) { $st->bind_param(str_repeat('s', count($params)), ...$params); }
    $st->execute();
    $st->close();
}

// Empty string -> NULL (so empty optional fields are stored as NULL).
function nul($v) { $v = trim((string)$v); return $v === '' ? null : $v; }

// Redirect back to a page with a message. $type = 'msg' (green) or 'err' (red).
function go($page, $message, $type = 'msg') {
    header('Location: ' . $page . '?' . $type . '=' . urlencode($message));
    exit;
}

// Turn a database exception into a friendly sentence.
function friendly_error($e) {
    $c = $e->getCode();
    if ($c == 1451) return 'Cannot delete/change this record because other records depend on it.';
    if ($c == 1452) return 'The selected parent record does not exist (foreign key error).';
    return 'Database error: ' . $e->getMessage();
}

function valid_date($d) {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

// Build <option> tags from rows that have 'id' and 'label' columns.
function opts($rows, $selected = '') {
    $o = '';
    foreach ($rows as $r) {
        $s = ((string)$r['id'] === (string)$selected) ? ' selected' : '';
        $o .= '<option value="' . h($r['id']) . '"' . $s . '>' . h($r['label']) . '</option>';
    }
    return $o;
}

// Dropdown data (id + label) read from the database.
function farmer_rows() {
    return db_all("SELECT farmer_id AS id, name AS label FROM Farmer ORDER BY name");
}
function field_rows() {
    return db_all("SELECT f.field_id AS id, CONCAT('Field #', f.field_id, ' - ', fr.name, ' (', IFNULL(f.soil_type,'-'), ')') AS label
                   FROM Field f JOIN Farmer fr ON f.farmer_id = fr.farmer_id ORDER BY f.field_id");
}
function crop_rows() {
    return db_all("SELECT crop_id AS id, CONCAT(crop_name, ' (Field #', field_id, ')') AS label FROM Crop ORDER BY crop_name, crop_id");
}

// Print any result set as an HTML table (used by DBMS Features + Smart Insights).
function show_table($rows) {
    if (!$rows) { echo '<p class="muted">No rows returned.</p>'; return; }
    echo '<div class="table-wrap"><table><tr>';
    foreach (array_keys($rows[0]) as $c) echo '<th>' . h($c) . '</th>';
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $v) echo '<td>' . h($v) . '</td>';
        echo '</tr>';
    }
    echo '</table></div>';
}
