<?php
// MySQL Database Viewer for TMS
require_once 'config.php';

try {
    $db = getDBConnection();
} catch (PDOException $e) {
    die("<h2>Database connection failed!</h2><p>Error: " . $e->getMessage() . "</p>");
}

// Get all tables
$tables = [];
$result = $db->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
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
    <h1>TMS MySQL Database Viewer</h1>
    <p><strong>Database:</strong> <?= DB_NAME ?> on <?= DB_HOST ?></p>
    
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
                <tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>
                <?php
                $result = $db->query("DESCRIBE $table");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)):
                ?>
                <tr>
                    <td><?= $row['Field'] ?></td>
                    <td><?= $row['Type'] ?></td>
                    <td><?= $row['Null'] ?></td>
                    <td><?= $row['Key'] ?></td>
                    <td><?= $row['Default'] ?? 'NULL' ?></td>
                    <td><?= $row['Extra'] ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php endforeach; ?>
    
    <?php else: ?>
        <h2><?= ucfirst($selectedTable) ?> Data</h2>
        <?php
        $result = $db->query("SELECT * FROM $selectedTable");
        if ($result && $result->rowCount() > 0):
            $columns = [];
            if ($result->rowCount() > 0) {
                // Get column names
                for ($i = 0; $i < $result->columnCount(); $i++) {
                    $columns[] = $result->getColumnMeta($i)['name'];
                }
            }
        ?>
        <table>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th><?= $column ?></th>
                <?php endforeach; ?>
            </tr>
            <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
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

    <hr>
    <p><small>Connected to MySQL database: <?= DB_NAME ?></small></p>
</body>
</html>