<?php
include 'conn.php'; // Include your database connection

$thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;

// Fetch thread details (optional, if you want to display thread title or other info)
try {
    $sqlThread = "SELECT thread_title FROM kjak_thread WHERE thread_id = :thread_id";
    $stmtThread = $pdo->prepare($sqlThread);
    $stmtThread->execute(['thread_id' => $thread_id]);
    $thread = $stmtThread->fetch();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Define number of posts per page
$postsPerPage = 10;

// Calculate total number of posts in this thread
$sqlCountPosts = "SELECT COUNT(*) FROM kjak_post WHERE thread_id = :thread_id";
$stmtCountPosts = $pdo->prepare($sqlCountPosts);
$stmtCountPosts->execute(['thread_id' => $thread_id]);
$totalPosts = $stmtCountPosts->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalPosts / $postsPerPage);

// Determine current page
$page = isset($_GET['page']) ? (int)$_GET['page'] : $totalPages; // Default to last page

// Calculate offset
$offset = ($page - 1) * $postsPerPage;

// Fetch posts for the thread
try {
    $sqlPosts = "SELECT post_id, author_name, post_text, post_image, created_at FROM kjak_post WHERE thread_id = :thread_id ORDER BY created_at ASC LIMIT :offset, :postsPerPage";
    $stmtPosts = $pdo->prepare($sqlPosts);
    $stmtPosts->bindParam(':thread_id', $thread_id, PDO::PARAM_INT);
    $stmtPosts->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmtPosts->bindParam(':postsPerPage', $postsPerPage, PDO::PARAM_INT);
    $stmtPosts->execute();
    $posts = $stmtPosts->fetchAll();    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Check if the form has been submitted to add a new post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
    $author_name = $_POST['author_name'] ?? 'Anonymous';
    $post_text = $_POST['post_text'];
    $post_image = ''; // Implement image upload logic here
    $created_at = date('Y-m-d H:i:s');

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert new post without image first
        $sqlInsertPost = "INSERT INTO kjak_post (thread_id, author_name, post_text, created_at) VALUES (:thread_id, :author_name, :post_text, :created_at)";
        $stmtInsertPost = $pdo->prepare($sqlInsertPost);
        $stmtInsertPost->execute(['thread_id' => $thread_id, 'author_name' => $author_name, 'post_text' => $post_text, 'created_at' => $created_at]);
        $post_id = $pdo->lastInsertId(); // Get the ID of the newly created post

        // Handle file upload
        if (isset($_FILES["post_image"]["name"]) && $_FILES["post_image"]["name"] != '') {
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
                // Update the post with the new image filename
                $sqlUpdatePost = "UPDATE kjak_post SET post_image = :post_image WHERE post_id = :post_id";
                $stmtUpdatePost = $pdo->prepare($sqlUpdatePost);
                $stmtUpdatePost->execute(['post_image' => $target_file, 'post_id' => $post_id]);
            }
        }
        // what posts should be shown.
        $newPage = ceil(($totalPosts + 1) / $postsPerPage);

        // Commit transaction
        $pdo->commit();

        header("Location: view_thread.php?thread_id=" . $thread_id . "&page=" . $newPage); // Redirect to the last page with the new post
        exit();
    } catch (Exception $e) {
        $pdo->rollback();
        echo "Error adding post: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($thread['thread_title'] ?? 'Thread'); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($thread['thread_title']); ?></h1>

    <?php foreach ($posts as $post): ?>
        <div class="post">
            <h2><?php echo htmlspecialchars($post['author_name']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($post['post_text'])); ?></p>
            <?php if ($post['post_image']): ?>
                <img src="<?php echo htmlspecialchars($post['post_image']); ?>" alt="Post Image">
            <?php endif; ?>
            <small>Skriva á: <?php echo htmlspecialchars($post['created_at']); ?></small>
        </div>
    <?php endforeach; ?>

    <nav>
        <div>
            Síða: 
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?thread_id=<?php echo $thread_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        </div>
    </nav>

    <h2>Svara tráð</h2>
    <form action="view_thread.php?thread_id=<?php echo $thread_id; ?>" method="post" enctype="multipart/form-data">
        <label for="author_name">Títt navn (valfrítt):</label>
        <input type="text" id="author_name" name="author_name"><br><br>
        <label for="post_text">Títt svar:</label>
        <textarea id="post_text" name="post_text" required></textarea><br><br>
        <input type="file" id="post_image" name="post_image"><br><br>
        <input type="submit" name="submit_post" value="Post Reply">
    </form>
</body>
</html>
