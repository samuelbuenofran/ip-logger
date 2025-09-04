<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$success = true;
$messages = [];

try {
    // Check if columns exist first
    $stmt = $conn->query("DESCRIBE links");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add custom_domain column if it doesn't exist
    if (!in_array('custom_domain', $existing_columns)) {
        $conn->exec("ALTER TABLE links ADD COLUMN custom_domain VARCHAR(255) NULL AFTER expiry_date");
        $messages[] = "✅ Added custom_domain column";
    } else {
        $messages[] = "ℹ️ custom_domain column already exists";
    }
    
    // Add extension column if it doesn't exist
    if (!in_array('extension', $existing_columns)) {
        $conn->exec("ALTER TABLE links ADD COLUMN extension VARCHAR(20) NULL AFTER custom_domain");
        $messages[] = "✅ Added extension column";
    } else {
        $messages[] = "ℹ️ extension column already exists";
    }
    
    // Add tracking_code column if it doesn't exist
    if (!in_array('tracking_code', $existing_columns)) {
        $conn->exec("ALTER TABLE links ADD COLUMN tracking_code VARCHAR(50) UNIQUE NOT NULL AFTER extension");
        $messages[] = "✅ Added tracking_code column";
    } else {
        $messages[] = "ℹ️ tracking_code column already exists";
    }
    
    // Add index for tracking_code if it doesn't exist
    $stmt = $conn->query("SHOW INDEX FROM links WHERE Key_name = 'idx_tracking_code'");
    if (!$stmt->fetch()) {
        $conn->exec("ALTER TABLE links ADD INDEX idx_tracking_code (tracking_code)");
        $messages[] = "✅ Added tracking_code index";
    } else {
        $messages[] = "ℹ️ tracking_code index already exists";
    }
    
    // Update existing records to have tracking codes if they don't have them
    $stmt = $conn->query("SELECT id FROM links WHERE tracking_code IS NULL OR tracking_code = ''");
    $records_to_update = $stmt->fetchAll();
    
    if (count($records_to_update) > 0) {
        foreach ($records_to_update as $record) {
            $tracking_code = generateRandomString(12);
            $update_stmt = $conn->prepare("UPDATE links SET tracking_code = ? WHERE id = ?");
            $update_stmt->execute([$tracking_code, $record['id']]);
        }
        $messages[] = "✅ Updated " . count($records_to_update) . " existing records with tracking codes";
    }
    
} catch (Exception $e) {
    $success = false;
    $messages[] = "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update - IP Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-database"></i> Database Schema Update</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> 
                                <strong>Database updated successfully!</strong>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Database update failed!</strong>
                            </div>
                        <?php endif; ?>
                        
                        <h5>Update Results:</h5>
                        <ul class="list-group mb-3">
                            <?php foreach ($messages as $message): ?>
                                <li class="list-group-item"><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="text-center">
                            <a href="create_link.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Try Create Link Now
                            </a>
                            <a href="index.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-home"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
