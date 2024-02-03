<?php
include 'conn.php'; // Include your database connection file

try {
    $sql = "SELECT forum_id, forum_name, description FROM kjak_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $forums = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forums</title>
</head>
<body>
    <h1>Vælkomin til Kjakið</h1>
    <ul>
        <?php foreach ($forums as $forum): ?>
            <li>
                <a href="view_forum.php?forum_id=<?php echo htmlspecialchars($forum['forum_id']); ?>">
                    <?php echo htmlspecialchars($forum['forum_name']); ?>
                </a>
                - <?php echo htmlspecialchars($forum['description']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
