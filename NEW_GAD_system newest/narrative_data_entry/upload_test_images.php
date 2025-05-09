<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_images'])) {
    
    // Create photos directory if not exists
    $uploadDir = 'photos/';
    if (!file_exists($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            $message .= "Created directory: $uploadDir<br>";
        } else {
            $message .= "Failed to create directory: $uploadDir<br>";
        }
    }
    
    // Upload files
    $uploaded = 0;
    $failed = 0;
    $paths = [];
    
    for ($i = 0; $i < count($_FILES['test_images']['name']); $i++) {
        if ($_FILES['test_images']['error'][$i] === 0) {
            $originalName = $_FILES['test_images']['name'][$i];
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            // Use the name format from the example
            $timestamp = time();
            $fileName = "narrative_18_{$timestamp}_{$i}.{$extension}";
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['test_images']['tmp_name'][$i], $targetPath)) {
                $message .= "Uploaded: {$originalName} -> {$fileName}<br>";
                $uploaded++;
                $paths[] = "photos/{$fileName}";
            } else {
                $message .= "Failed to upload: {$originalName}<br>";
                $failed++;
            }
        } else {
            $message .= "Error with file {$i}: " . $_FILES['test_images']['error'][$i] . "<br>";
            $failed++;
        }
    }
    
    if ($uploaded > 0) {
        $message .= "<strong>Successfully uploaded {$uploaded} files.</strong><br>";
        
        // Create JSON array of paths
        $jsonPaths = json_encode($paths);
        $message .= "JSON array for database: {$jsonPaths}<br>";
        
        // Offer to update database
        $message .= "<form action='force_set_test_images.php' method='get'>";
        $message .= "<button type='submit' style='margin-top: 10px;' class='btn btn-primary'>Store these paths in database</button>";
        $message .= "</form>";
    }
    
    if ($failed > 0) {
        $message .= "<strong>Failed to upload {$failed} files.</strong><br>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Test Images</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Upload Test Images</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-info">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5>Test Image Upload</h5>
            </div>
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="test_images" class="form-label">Select Images</label>
                        <input type="file" class="form-control" id="test_images" name="test_images[]" accept="image/*" multiple required>
                        <div class="form-text">You can select multiple images. They will be uploaded to the 'photos' directory.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Upload Images</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Debugging Tools</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="debug_photo_path.php" target="_blank" class="list-group-item list-group-item-action">
                        Check Database Column Info
                    </a>
                    <a href="fix_photo_paths.php" target="_blank" class="list-group-item list-group-item-action">
                        Fix Existing Photo Paths
                    </a>
                    <a href="force_set_test_images.php" target="_blank" class="list-group-item list-group-item-action">
                        Set Sample Image Paths in Database
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 