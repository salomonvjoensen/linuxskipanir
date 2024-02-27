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
    <title>Kjakið</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<div class="nav-bar">
    <!-- "Go Back" Button -->
    <a href="#" onclick="goBackStructured()">Go Back</a>
</div>

<body>
    <div id="forum" class="forum">
        <div class="small-title">Vælkomin til</div>
        <div class="title">Kjakið</div>
    </div>
    <div id="forums-container">
        <?php foreach ($forums as $forum): ?>
            <div class="forum">
            <a class="forum-link" href="view_forum.php?forum_id=<?php echo htmlspecialchars($forum['forum_id']); ?>&forum_name=<?php echo urlencode($forum['forum_name']); ?>">
                    <?php echo htmlspecialchars($forum['forum_name']); ?>
                </a>
                <?php echo htmlspecialchars($forum['description']); ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
