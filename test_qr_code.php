<?php
/**
 * Test QR Code APIs
 * This file tests different QR Code APIs to see which ones are working
 */

require_once 'includes/qr_generator.php';

$testUrl = 'https://example.com';
$size = 150;

echo "<h1>QR Code API Test</h1>";
echo "<p>Testing URL: <strong>$testUrl</strong></p>";
echo "<p>Size: <strong>{$size}x{$size}</strong></p>";

$apis = [
    'QR Server API' => "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($testUrl),
    'Google Charts API' => "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($testUrl),
    'QR Code Generator API' => "https://qr-code-generator.com/api/qr?size={$size}&data=" . urlencode($testUrl),
];

echo "<h2>Testing APIs:</h2>";

foreach ($apis as $name => $url) {
    echo "<h3>$name</h3>";
    echo "<p>URL: <code>$url</code></p>";
    
    // Test if URL is accessible
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'HEAD'
        ]
    ]);
    
    $headers = @get_headers($url, 1, $context);
    $isWorking = $headers && strpos($headers[0], '200') !== false;
    
    if ($isWorking) {
        echo "<p style='color: green;'>✅ <strong>Working</strong></p>";
        echo "<img src='$url' alt='QR Code from $name' style='border: 1px solid #ccc; margin: 10px;'>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Not Working</strong></p>";
        if ($headers) {
            echo "<p>Response: <code>" . $headers[0] . "</code></p>";
        } else {
            echo "<p>No response received</p>";
        }
    }
    echo "<hr>";
}

echo "<h2>Using QRCodeGenerator Class:</h2>";
$qrUrl = QRCodeGenerator::generateQRCode($testUrl, $size);
echo "<p>Generated URL: <code>$qrUrl</code></p>";
echo "<img src='$qrUrl' alt='QR Code from QRCodeGenerator' style='border: 1px solid #ccc; margin: 10px;'>";

echo "<h2>Test Complete</h2>";
echo "<p><a href='create_link.php'>← Back to Create Link</a></p>";
?>
