<?php
include 'conn.php'; // Make sure this points to your actual database connection script

$forum_name = isset($_GET['forum_name']) ? urldecode($_GET['forum_name']) : ''; // Initialize forum_name variable

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
    <title>Stovna tráð í <?php echo htmlspecialchars($forum_name); ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="gradient-box"><div class="small-title">Stovna nýggjan tráð í</div><div class="title"><?php echo htmlspecialchars($forum_name); ?></div></div>
    <br>
    <form action="create_thread.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="forum_id" value="<?php echo $_GET['forum_id']; ?>">
        <label for="thread_title">Tráð Navn:</label>
        <input type="text" id="thread_title" name="thread_title" required><br><br>
        <label for="author_name">Títt Navn (valfrítt):</label>
        <input type="text" id="author_name" name="author_name"><br><br>
        <label for="post_text">Tekstur: </label>
        <textarea id="post_text" name="post_text" required></textarea><br><br>
        <label for="post_image">Viðheft mynd (valfrítt):</label>
        <input type="file" id="post_image" name="post_image"><br><br>
        <input type="submit" value="Create Thread">
    </form>
</body>
</html>
