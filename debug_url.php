<?php
require_once 'config/config.php';

echo "BASE_URL: " . BASE_URL . "<br>";
echo "Test shortcode: ABC123<br>";
echo "Test extension: .avi<br>";

// Test URL construction
$shortcode = 'ABC123';
$extension = '.avi';
$use_custom_domain = false;
$custom_domain = '';

$final_url = ($use_custom_domain ? $custom_domain . '/' : BASE_URL) . $shortcode . $extension;

echo "Generated URL: " . $final_url . "<br>";
echo "Expected: https://keizai-tech.com/projects/ip-logger/ABC123.avi<br>";

// Check for double slashes
if (strpos($final_url, '//') !== false && strpos($final_url, '://') === false) {
    echo "<span style='color: red;'>WARNING: Double slash detected!</span><br>";
} else {
    echo "<span style='color: green;'>URL looks good!</span><br>";
}
?>
