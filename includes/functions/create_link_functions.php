<?php

// Handle form submission for creating new links
if (isset($_POST['action']) && $_POST['action'] === 'create_link') {
    $original_url = sanitizeInput($_POST['original_url']);
    $no_expiry = isset($_POST['no_expiry']) ? 1 : 0;
    $password = $_POST['password'];

    // Validate input
    if (!isValidUrl($original_url)) {
        redirectWithMessage('index.php', 'Please enter a valid URL', 'error');
    }

    if (strlen($password) < 3) {
        redirectWithMessage('index.php', 'Password must be at least 3 characters long', 'error');
    }

    // Generate unique short code
    $short_code = generateShortCode();

    // Set expiry date (default 30 days if not set to never expire)
    // This is done for security reasons
    $expiry_date = $no_expiry ? NULL : date('Y-m-d H:i:s', strtotime('+30 days'));

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO links (original_url, short_code, password, expiry_date, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$original_url, $short_code, $hashed_password, $expiry_date]);

    // Get the link ID for email notification
    $linkId = $conn->lastInsertId();

    // Send email notification for new link creation
    sendNewLinkNotification($linkId);

    redirectWithMessage('index.php', 'Link created successfully! Short URL: ' . BASE_URL . $short_code, 'success');
}
