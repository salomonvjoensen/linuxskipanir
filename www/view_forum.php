<?php
include 'conn.php'; // Include your database connection

$forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$threadsPerPage = 10;
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

// Only check for forum name if it is not in the URL.
if ($forum_name == '') {
    try {
        $sqlForum = "SELECT forum_name FROM kjak_table WHERE forum_id = :forum_id";
        $stmtForum = $pdo->prepare($sqlForum);
        $stmtForum->execute(['forum_id' => $forum_id]);
        $forum_name = $stmtForum->fetchColumn();
    } catch (Exception $e) {
        die("Error fetching forum name: " . $e->getMessage());
    }
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo->beginTransaction(); // Start transaction

    try {
        // Extract and sanitize input
        $forum_id = isset($_POST['forum_id']) ? (int)$_POST['forum_id'] : 0;
        $thread_title = filter_input(INPUT_POST, 'thread_title', FILTER_SANITIZE_STRING);
        $author_name = filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_STRING) ?? 'Anonymous'; // Default to 'Anonymous' if empty
        $post_text = filter_input(INPUT_POST, 'post_text', FILTER_SANITIZE_STRING);
        $created_at = date('Y-m-d H:i:s');

        // Insert new thread
        $sqlThread = "INSERT INTO kjak_thread (forum_id, thread_title, created_at) VALUES (:forum_id, :thread_title, :created_at)";
        $stmtThread = $pdo->prepare($sqlThread);
        $stmtThread->execute(['forum_id' => $forum_id, 'thread_title' => $thread_title, 'created_at' => $created_at]);
        $thread_id = $pdo->lastInsertId();

        // Insert opening post without image first
        $sqlPost = "INSERT INTO kjak_post (thread_id, author_name, post_text, created_at) VALUES (:thread_id, :author_name, :post_text, :created_at)";
        $stmtPost = $pdo->prepare($sqlPost);
        $stmtPost->execute(['thread_id' => $thread_id, 'author_name' => $author_name, 'post_text' => $post_text, 'created_at' => $created_at]);
        $post_id = $pdo->lastInsertId();

        // Handle file upload
        if (!empty($_FILES["post_image"]["name"])) {
            $target_dir = "uploads/";
            $fileExtension = pathinfo($_FILES["post_image"]["name"], PATHINFO_EXTENSION);
            // Extract the original filename without the extension
            $originalFilename = pathinfo($_FILES["post_image"]["name"], PATHINFO_FILENAME);
            // Sanitize the filename to remove spaces and special characters
            $safeFilename = preg_replace("/[^a-zA-Z0-9_-]/", "", $originalFilename);
            // Construct the new filename including the directory, the sanitized original filename, the post ID, and the file extension
            $newFileName = $safeFilename . "_" . $post_id . "." . $fileExtension;
            $target_file = $target_dir . $newFileName;

            if (move_uploaded_file($_FILES["post_image"]["tmp_name"], $target_file)) {
                // Successfully uploaded, update post record with new image path
                $sqlUpdatePost = "UPDATE kjak_post SET post_image = :post_image WHERE post_id = :post_id";
                $stmtUpdatePost = $pdo->prepare($sqlUpdatePost);
                $stmtUpdatePost->execute(['post_image' => $target_file, 'post_id' => $post_id]);
            } else {
                throw new Exception("Failed to upload file.");
            }
        }

        $pdo->commit(); // Commit transaction
        header("Location: view_thread.php?thread_id=" . $thread_id . "&forum_id=" . $forum_id); // Redirect to the newly created thread
        exit();
    } catch (Exception $e) {
        $pdo->rollback(); // Rollback transaction on error
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title><?php echo htmlspecialchars($forum_name); ?></title>
</head>
<div class="nav-bar">
    <!-- "Go Back" Button -->
    <button onclick="window.location.href='index.php'">Go Back</button>
</div>
<body>
    <div id="forum" class="forum">
        <div class="small-title">Forum:</div>
        <div class="title"><?php echo htmlspecialchars($forum_name); ?></div>
    </div>
    <?php foreach ($threads as $thread): ?>
        <div class="thread">
            <a href="view_thread.php?thread_id=<?php echo htmlspecialchars($thread['thread_id']); ?>&forum_id=<?php echo htmlspecialchars($forum_id); ?>">
                <?php echo htmlspecialchars($thread['thread_title']); ?>
            </a>
            - <?php echo htmlspecialchars($thread['created_at']); ?>
        </div>
    <?php endforeach; ?>
    
    <?php if ($totalPages > 1): ?>
        <nav>
            <ul>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li><a href="?forum_id=<?php echo $forum_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
    
    <!-- <a href="create_thread.php?forum_id=<?php echo $forum_id; ?>&forum_name=<?php echo urlencode($forum_name); ?>">Nýggjan Tráð</a> -->
    
    <div class="button-form">
        <button id="toggleCreateThreadButton" onClick="toggleThreadForm();">Vís Tráð Form</button>
    </div>
    <form id="toggleCreateThread" style="visibility: hidden;" action="create_thread.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="forum_id" value="<?php echo $_GET['forum_id']; ?>">
        <label for="thread_title">Tráð Navn:</label>
        <input type="text" id="thread_title" name="thread_title" required><br><br>
        <label for="author_name">Títt Navn (valfrítt):</label>
        <input type="text" id="author_name" name="author_name"><br><br>
        <label for="post_text">Tekstur: </label>
        <textarea id="post_text" name="post_text" required></textarea><br><br>
        <label for="post_image">Viðheft mynd (valfrítt):</label>
        <input type="file" id="post_image" name="post_image"><br><br>
        <input type="submit" value="Stovna Tráð">
    </form>
    <script src="script.js"></script>
</body>
</html>
