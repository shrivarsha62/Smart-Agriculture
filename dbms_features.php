<?php
require 'db.php';
$pageTitle = 'DBMS Features';

// Runs a real SQL query, shows the query text and the result table.
function demo($title, $explain, $sql) {
    echo '<div class="card"><h2>' . h($title) . '</h2><p class="muted">' . h($explain) . '</p>';
    echo '<pre>' . h($sql) . '</pre>';
    try { show_table(db_all($sql)); }
    catch (Exception $e) { echo '<div class="alert bad">' . h($e->getMessage()) . '</div>'; }
    echo '</div>';
}

// Stored procedure: run only when a farmer is chosen
$procRows = null; $procErr = ''; $fid = (int)($_GET['farmer_id'] ?? 0);
if ($fid > 0) {
    try {
        $res = $conn->query("CALL GetFarmerCrops($fid)");   // $fid is an integer, so this is safe
        $procRows = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
        while ($conn->more_results()) { $conn->next_result(); }
    } catch (Exception $e) { $procErr = $e->getMessage(); }
}
$farmers = farmer_rows();
require 'header.php';
?>
<h1>DBMS Features</h1>
<p class="muted">Every section below runs a real SQL query against your database.</p>

<?php
demo('1. JOIN', 'Combines Farmer, Field and Crop using foreign keys.',
"SELECT fr.name AS farmer, f.field_id, f.area, f.soil_type, c.crop_name, c.season
FROM Farmer fr
JOIN Field f ON fr.farmer_id = f.farmer_id
JOIN Crop c ON f.field_id = c.field_id
ORDER BY fr.name");

demo('2. Aggregate Functions', 'COUNT, AVG, MAX, MIN and SUM.',
"SELECT (SELECT COUNT(*) FROM Farmer) AS total_farmers,
       (SELECT COUNT(*) FROM Crop) AS total_crops,
       ROUND(AVG(area),2) AS average_field_area,
       MAX(area) AS maximum_field_area,
       MIN(area) AS minimum_field_area,
       SUM(area) AS total_area
FROM Field");

demo('3. GROUP BY', 'Number of fields owned by each farmer.',
"SELECT fr.name AS farmer, COUNT(f.field_id) AS number_of_fields, IFNULL(SUM(f.area),0) AS total_area
FROM Farmer fr
LEFT JOIN Field f ON fr.farmer_id = f.farmer_id
GROUP BY fr.farmer_id, fr.name
ORDER BY number_of_fields DESC");

demo('4. HAVING', 'Only farmers who own more than one field.',
"SELECT fr.name AS farmer, COUNT(f.field_id) AS number_of_fields
FROM Farmer fr
JOIN Field f ON fr.farmer_id = f.farmer_id
GROUP BY fr.farmer_id, fr.name
HAVING COUNT(f.field_id) > 1");

demo('5. Nested Query (Subquery)', 'The field with the largest area. The inner query finds MAX(area).',
"SELECT f.field_id, fr.name AS farmer, f.area, f.soil_type
FROM Field f
JOIN Farmer fr ON f.farmer_id = fr.farmer_id
WHERE f.area = (SELECT MAX(area) FROM Field)");

demo('6. VIEW: Farmer_Crop_Details', 'A saved query that behaves like a table.',
"SELECT * FROM Farmer_Crop_Details");
?>

<div class="card">
  <h2>7. Stored Procedure: GetFarmerCrops</h2>
  <p class="muted">Choose a farmer and the procedure returns that farmer's crops.</p>
  <form method="get" class="search-bar">
    <div><label>Farmer<select name="farmer_id" required><option value="">-- Select farmer --</option><?= opts($farmers, $fid) ?></select></label></div>
    <button class="btn btn-green" type="submit">Execute Procedure</button>
  </form>
  <pre>CALL GetFarmerCrops(<?= $fid > 0 ? $fid : 'farmer_id' ?>);</pre>
  <?php if ($procErr !== ''): ?><div class="alert bad"><?= h($procErr) ?></div>
  <?php elseif ($procRows !== null): show_table($procRows); endif; ?>
</div>

<?php
demo('8. Trigger: after_farmer_insert', 'Every time a farmer is added, the trigger inserts a row here automatically. Add a farmer on the Farmers page and refresh.',
"SELECT l.log_id, l.farmer_id, fr.name AS farmer_name, l.action, l.log_date
FROM Farmer_Log l
LEFT JOIN Farmer fr ON l.farmer_id = fr.farmer_id
ORDER BY l.log_id DESC");
require 'footer.php';
?>
