<?php
// Simple SQLite Database Viewer
$dbFile = 'database.sqlite';

if (!file_exists($dbFile)) {
    die("<h2>Database file not found!</h2><p>Expected: $dbFile</p>");
}

$db = new SQLite3($dbFile);

// Get all tables
$tables = [];
$result = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
while ($row = $result->fetchArray()) {
    $tables[] = $row['name'];
}

$selectedTable = $_GET['table'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>TMS Database Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .nav { margin: 20px 0; }
        .nav a { margin-right: 10px; padding: 5px 10px; background: #007cba; color: white; text-decoration: none; border-radius: 3px; }
        .nav a:hover { background: #005a8b; }
        .nav a.active { background: #28a745; }
    </style>
</head>
<body>
    <h1>TMS Database Viewer</h1>
    <p><strong>Database:</strong> <?= $dbFile ?></p>
    
    <div class="nav">
        <a href="db_viewer.php">All Tables</a>
        <?php foreach ($tables as $table): ?>
            <a href="?table=<?= $table ?>" class="<?= $selectedTable === $table ? 'active' : '' ?>">
                <?= ucfirst($table) ?>
            </a>
        <?php endforeach; ?>
        <a href="dashboard.html" style="background: #6c757d;">← Back to Dashboard</a>
    </div>

    <?php if (empty($selectedTable)): ?>
        <h2>Database Schema</h2>
        <?php foreach ($tables as $table): ?>
            <h3><?= ucfirst($table) ?> Table</h3>
            <table>
                <tr><th>Column</th><th>Type</th><th>Not Null</th><th>Default</th><th>Primary Key</th></tr>
                <?php
                $result = $db->query("PRAGMA table_info($table)");
                while ($row = $result->fetchArray()):
                ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['type'] ?></td>
                    <td><?= $row['notnull'] ? 'Yes' : 'No' ?></td>
                    <td><?= $row['dflt_value'] ?? 'NULL' ?></td>
                    <td><?= $row['pk'] ? 'Yes' : 'No' ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php endforeach; ?>
    
    <?php else: ?>
        <h2><?= ucfirst($selectedTable) ?> Data</h2>
        <?php
        $result = $db->query("SELECT * FROM $selectedTable");
        if ($result):
            $firstRow = $result->fetchArray(SQLITE3_ASSOC);
            if ($firstRow):
                $result->reset();
        ?>
        <table>
            <tr>
                <?php foreach (array_keys($firstRow) as $column): ?>
                    <th><?= $column ?></th>
                <?php endforeach; ?>
            </tr>
            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <?php foreach ($row as $value): ?>
                    <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>No data found in <?= $selectedTable ?> table.</p>
        <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <hr>
    <p><small>Database file size: <?= number_format(filesize($dbFile)) ?> bytes</small></p>
</body>
</html>
<?php $db->close(); ?>