<?php
/**
 * QR Code Generator for IP Logger
 * Simple QR Code generation using Google Charts API
 */

class QRCodeGenerator {
    
    /**
     * Generate QR Code URL using multiple APIs with fallback
     */
    public static function generateQRCode($url, $size = 200) {
        // Validate size
        $size = max(100, min(500, $size)); // Between 100 and 500 pixels
        
        // Encode URL for QR Code
        $encodedUrl = urlencode($url);
        
        // Try multiple QR Code APIs with fallback
        $apis = [
            // QR Server API (most reliable)
            "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedUrl}",
            
            // Google Charts API (backup)
            "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedUrl}",
            
            // QR Code API (backup)
            "https://qr-code-generator.com/api/qr?size={$size}&data={$encodedUrl}",
            
            // QR Code Monkey (backup)
            "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedUrl}&format=png"
        ];
        
        // Test each API and return the first working one
        foreach ($apis as $apiUrl) {
            if (self::testQRCodeUrl($apiUrl)) {
                return $apiUrl;
            }
        }
        
        // If all APIs fail, return the first one anyway
        return $apis[0];
    }
    
    /**
     * Test if QR Code URL is accessible
     */
    private static function testQRCodeUrl($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5 second timeout
                'method' => 'HEAD'
            ]
        ]);
        
        $headers = @get_headers($url, 1, $context);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate QR Code with custom styling
     */
    public static function generateStyledQRCode($url, $size = 200, $color = '000000', $bgColor = 'FFFFFF') {
        // Validate size
        $size = max(100, min(500, $size));
        
        // Encode URL for QR Code
        $encodedUrl = urlencode($url);
        
        // Google Charts QR Code API with custom colors
        $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedUrl}&chco={$color}&chf=bg,s,{$bgColor}";
        
        return $qrUrl;
    }
    
    /**
     * Generate QR Code with logo/watermark
     */
    public static function generateQRCodeWithLogo($url, $size = 200, $logoUrl = null) {
        // Validate size
        $size = max(100, min(500, $size));
        
        // Encode URL for QR Code
        $encodedUrl = urlencode($url);
        
        // Google Charts QR Code API
        $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedUrl}";
        
        // If logo URL is provided, we can't embed it directly with Google Charts
        // This would require a more advanced QR Code library
        return $qrUrl;
    }
    
    /**
     * Download QR Code image
     */
    public static function downloadQRCode($url, $filename = null) {
        if (!$filename) {
            $filename = 'qr_code_' . date('Y-m-d_H-i-s') . '.png';
        }
        
        $qrUrl = self::generateQRCode($url);
        
        // Set headers for download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output QR Code image
        readfile($qrUrl);
        exit;
    }
    
    /**
     * Get QR Code as base64 data URL
     */
    public static function getQRCodeAsDataURL($url, $size = 200) {
        $qrUrl = self::generateQRCode($url);
        
        // Get image content
        $imageContent = file_get_contents($qrUrl);
        
        if ($imageContent === false) {
            return false;
        }
        
        // Convert to base64
        $base64 = base64_encode($imageContent);
        
        return 'data:image/png;base64,' . $base64;
    }
    
    /**
     * Validate URL for QR Code
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Get QR Code dimensions
     */
    public static function getDimensions($size = 200) {
        return [
            'width' => $size,
            'height' => $size
        ];
    }
}
?>
