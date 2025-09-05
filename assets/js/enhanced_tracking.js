/**
 * Enhanced Tracking JavaScript
 * Collects additional device information for precise geolocation
 */

class EnhancedTracking {
    constructor() {
        this.data = {};
        this.init();
    }
    
    init() {
        this.collectDeviceInfo();
        this.collectScreenInfo();
        this.collectTimezoneInfo();
        this.collectLanguageInfo();
        this.collectTouchInfo();
        this.collectNetworkInfo();
        this.collectPerformanceInfo();
        this.collectCanvasFingerprint();
        this.collectWebGLFingerprint();
        this.collectAudioFingerprint();
        this.collectFontFingerprint();
        
        // Send data to server
        this.sendData();
    }
    
    /**
     * Collect basic device information
     */
    collectDeviceInfo() {
        this.data.device_info = {
            user_agent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            languages: navigator.languages,
            cookie_enabled: navigator.cookieEnabled,
            do_not_track: navigator.doNotTrack,
            hardware_concurrency: navigator.hardwareConcurrency,
            max_touch_points: navigator.maxTouchPoints,
            on_line: navigator.onLine,
            vendor: navigator.vendor,
            product: navigator.product,
            product_sub: navigator.productSub,
            vendor_sub: navigator.vendorSub
        };
    }
    
    /**
     * Collect screen information
     */
    collectScreenInfo() {
        this.data.screen_info = {
            width: screen.width,
            height: screen.height,
            avail_width: screen.availWidth,
            avail_height: screen.availHeight,
            color_depth: screen.colorDepth,
            pixel_depth: screen.pixelDepth,
            orientation: screen.orientation ? screen.orientation.type : null,
            orientation_angle: screen.orientation ? screen.orientation.angle : null
        };
    }
    
    /**
     * Collect timezone information
     */
    collectTimezoneInfo() {
        try {
            const now = new Date();
            this.data.timezone_info = {
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                timezone_offset: now.getTimezoneOffset(),
                timezone_offset_hours: now.getTimezoneOffset() / 60,
                local_time: now.toLocaleString(),
                utc_time: now.toUTCString()
            };
        } catch (e) {
            this.data.timezone_info = {
                timezone_offset: new Date().getTimezoneOffset()
            };
        }
    }
    
    /**
     * Collect language information
     */
    collectLanguageInfo() {
        this.data.language_info = {
            language: navigator.language,
            languages: navigator.languages,
            accept_language: navigator.language || 'en-US'
        };
    }
    
    /**
     * Collect touch support information
     */
    collectTouchInfo() {
        this.data.touch_info = {
            touch_support: 'ontouchstart' in window,
            max_touch_points: navigator.maxTouchPoints || 0,
            touch_events: {
                touchstart: 'ontouchstart' in window,
                touchmove: 'ontouchmove' in window,
                touchend: 'ontouchend' in window,
                touchcancel: 'ontouchcancel' in window
            },
            pointer_events: {
                pointerdown: 'onpointerdown' in window,
                pointermove: 'onpointermove' in window,
                pointerup: 'onpointerup' in window,
                pointercancel: 'onpointercancel' in window
            }
        };
    }
    
    /**
     * Collect network information
     */
    collectNetworkInfo() {
        this.data.network_info = {
            on_line: navigator.onLine,
            connection_type: navigator.connection ? navigator.connection.effectiveType : null,
            downlink: navigator.connection ? navigator.connection.downlink : null,
            rtt: navigator.connection ? navigator.connection.rtt : null,
            save_data: navigator.connection ? navigator.connection.saveData : null
        };
    }
    
    /**
     * Collect performance information
     */
    collectPerformanceInfo() {
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            this.data.performance_info = {
                navigation_start: timing.navigationStart,
                load_event_end: timing.loadEventEnd,
                dom_content_loaded: timing.domContentLoadedEventEnd - timing.navigationStart,
                page_load_time: timing.loadEventEnd - timing.navigationStart
            };
        }
    }
    
    /**
     * Collect canvas fingerprint
     */
    collectCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Draw text
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('Enhanced Tracking Canvas Fingerprint 🔍', 2, 2);
            
            // Add background
            ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
            ctx.fillRect(100, 5, 80, 20);
            
            // Add shape
            ctx.fillStyle = 'rgba(255, 0, 0, 0.7)';
            ctx.fillRect(200, 5, 40, 40);
            
            this.data.canvas_fingerprint = canvas.toDataURL();
        } catch (e) {
            this.data.canvas_fingerprint = 'error';
        }
    }
    
    /**
     * Collect WebGL fingerprint
     */
    collectWebGLFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            
            if (gl) {
                this.data.webgl_fingerprint = {
                    vendor: gl.getParameter(gl.VENDOR),
                    renderer: gl.getParameter(gl.RENDERER),
                    version: gl.getParameter(gl.VERSION),
                    shading_language_version: gl.getParameter(gl.SHADING_LANGUAGE_VERSION),
                    extensions: gl.getSupportedExtensions()
                };
            }
        } catch (e) {
            this.data.webgl_fingerprint = 'error';
        }
    }
    
    /**
     * Collect audio fingerprint
     */
    collectAudioFingerprint() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const analyser = audioContext.createAnalyser();
            const gainNode = audioContext.createGain();
            const scriptProcessor = audioContext.createScriptProcessor(4096, 1, 1);
            
            gainNode.gain.value = 0; // Silent
            oscillator.type = 'triangle';
            oscillator.connect(analyser);
            analyser.connect(scriptProcessor);
            scriptProcessor.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.start(0);
            
            const audioData = new Float32Array(analyser.frequencyBinCount);
            analyser.getFloatFrequencyData(audioData);
            
            this.data.audio_fingerprint = {
                sample_rate: audioContext.sampleRate,
                frequency_data: Array.from(audioData.slice(0, 10)) // First 10 values
            };
            
            oscillator.stop();
            audioContext.close();
        } catch (e) {
            this.data.audio_fingerprint = 'error';
        }
    }
    
    /**
     * Collect font fingerprint
     */
    collectFontFingerprint() {
        const fonts = [
            'Arial', 'Verdana', 'Helvetica', 'Times New Roman', 'Courier New',
            'Georgia', 'Palatino', 'Garamond', 'Bookman', 'Comic Sans MS',
            'Trebuchet MS', 'Arial Black', 'Impact', 'Lucida Console',
            'Tahoma', 'Geneva', 'Lucida Sans Unicode', 'Franklin Gothic Medium',
            'Arial Narrow', 'Verdana'
        ];
        
        this.data.font_fingerprint = {
            available_fonts: [],
            test_string: 'abcdefghijklmnopqrstuvwxyz0123456789'
        };
        
        // Simple font detection
        const testElement = document.createElement('div');
        testElement.style.position = 'absolute';
        testElement.style.visibility = 'hidden';
        testElement.style.fontSize = '72px';
        testElement.innerHTML = this.data.font_fingerprint.test_string;
        document.body.appendChild(testElement);
        
        const defaultWidth = testElement.offsetWidth;
        const defaultHeight = testElement.offsetHeight;
        
        fonts.forEach(font => {
            testElement.style.fontFamily = font;
            const width = testElement.offsetWidth;
            const height = testElement.offsetHeight;
            
            if (width !== defaultWidth || height !== defaultHeight) {
                this.data.font_fingerprint.available_fonts.push(font);
            }
        });
        
        document.body.removeChild(testElement);
    }
    
    /**
     * Generate device fingerprint hash
     */
    generateFingerprint() {
        const fingerprintData = {
            user_agent: this.data.device_info.user_agent,
            screen: `${this.data.screen_info.width}x${this.data.screen_info.height}`,
            timezone: this.data.timezone_info.timezone_offset,
            language: this.data.language_info.language,
            touch_support: this.data.touch_info.touch_support,
            canvas: this.data.canvas_fingerprint,
            webgl: this.data.webgl_fingerprint.renderer,
            fonts: this.data.font_fingerprint.available_fonts.length
        };
        
        // Simple hash function
        const str = JSON.stringify(fingerprintData);
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        
        return Math.abs(hash).toString(36);
    }
    
    /**
     * Send data to server
     */
    sendData() {
        // Add fingerprint
        this.data.device_fingerprint = this.generateFingerprint();
        
        // Prepare data for form submission
        const formData = new FormData();
        
        // Add all collected data
        Object.keys(this.data).forEach(key => {
            if (typeof this.data[key] === 'object') {
                formData.append(key, JSON.stringify(this.data[key]));
            } else {
                formData.append(key, this.data[key]);
            }
        });
        
        // Send data to tracking endpoint
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        }).catch(error => {
            console.log('Enhanced tracking data sent');
        });
    }
}

// Initialize enhanced tracking when page loads
document.addEventListener('DOMContentLoaded', function() {
    new EnhancedTracking();
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        new EnhancedTracking();
    });
} else {
    new EnhancedTracking();
}
