<?php
include 'conn.php'; // Make sure this points to your actual database connection script

$forum_name = ""; // Initialize forum_name variable

// Check if forum_id is provided and is a valid integer
if (isset($_GET['forum_id']) && filter_var($_GET['forum_id'], FILTER_VALIDATE_INT)) {
    $forum_id = $_GET['forum_id'];

    try {
        // Prepare a statement for fetching the forum name
        $sqlForum = "SELECT forum_name FROM kjak_table WHERE forum_id = :forum_id";
        $stmtForum = $pdo->prepare($sqlForum);
        $stmtForum->execute(['forum_id' => $forum_id]);
        
        // Fetch the forum name
        $result = $stmtForum->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $forum_name = $result['forum_name'];
        } else {
            // Handle case where no forum is found for the given id
            throw new Exception("No forum found with the provided ID.");
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        // Optionally redirect to a default page or display a specific message
        exit();
    }
} else {
    echo "Invalid forum ID provided.";
    // Optionally redirect to a default page or display a specific message
    exit();
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
        header("Location: view_thread.php?thread_id=".$thread_id); // Redirect to the newly created thread
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
</head>
<body>
    <h1>Stovna nýggjan tráð í <?php echo htmlspecialchars($forum_name); ?></h1>
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
