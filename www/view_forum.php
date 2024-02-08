<?php
include 'conn.php'; // Include your database connection

$forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$threadsPerPage = 20;
$offset = ($page - 1) * $threadsPerPage;

try {
    $sql = "SELECT thread_id, thread_title, created_at FROM kjak_thread WHERE forum_id = :forum_id ORDER BY created_at DESC LIMIT :offset, :threadsPerPage";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':forum_id', $forum_id, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':threadsPerPage', $threadsPerPage, PDO::PARAM_INT);
    $stmt->execute();
    $threads = $stmt->fetchAll();

    // Count total threads for pagination
    $sqlTotal = "SELECT COUNT(*) FROM kjak_thread WHERE forum_id = :forum_id";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->bindParam(':forum_id', $forum_id, PDO::PARAM_INT);
    $stmtTotal->execute();
    $totalThreads = $stmtTotal->fetchColumn();
    $totalPages = ceil($totalThreads / $threadsPerPage);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$forum_name = ''; // Default to empty

if ($forum_id > 0) {
    try {
        $sqlForum = "SELECT forum_name FROM kjak_table WHERE forum_id = :forum_id";
        $stmtForum = $pdo->prepare($sqlForum);
        $stmtForum->execute(['forum_id' => $forum_id]);
        $forum_name = $stmtForum->fetchColumn();
    } catch (Exception $e) {
        die("Error fetching forum name: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($forum_name); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($forum_name); ?></h1>
    <ul>
        <?php foreach ($threads as $thread): ?>
            <li>
                <a href="view_thread.php?thread_id=<?php echo htmlspecialchars($thread['thread_id']); ?>">
                    <?php echo htmlspecialchars($thread['thread_title']); ?>
                </a>
                - <?php echo htmlspecialchars($thread['created_at']); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li><a href="?forum_id=<?php echo $forum_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
    
    <a href="create_thread.php?forum_id=<?php echo $forum_id; ?>&forum_name=<?php echo urlencode($forum_name); ?>">Nýggjan Tráð</a>
</body>
</html>
