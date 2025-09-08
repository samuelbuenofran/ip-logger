<?php
/**
 * QR Code Generator for IP Logger
 * Simple QR Code generation using Google Charts API
 */

class QRCodeGenerator {
    
    /**
     * Generate QR Code URL using Google Charts API
     */
    public static function generateQRCode($url, $size = 200) {
        // Validate size
        $size = max(100, min(500, $size)); // Between 100 and 500 pixels
        
        // Encode URL for QR Code
        $encodedUrl = urlencode($url);
        
        // Google Charts QR Code API
        $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedUrl}";
        
        return $qrUrl;
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
