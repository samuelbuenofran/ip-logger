/**
 * Utility Functions
 * Common utility functions used across the application
 */

/**
 * Copy text to clipboard
 * @param {string} text - Text to copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(text).then(function() {
            showNotification('Copied to clipboard!', 'success');
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            fallbackCopyToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(text);
    }
}

/**
 * Fallback copy to clipboard for older browsers
 * @param {string} text - Text to copy to clipboard
 */
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showNotification('Copied to clipboard!', 'success');
    } catch (err) {
        console.error('Fallback copy failed: ', err);
        showNotification('Failed to copy to clipboard', 'error');
    }
    
    document.body.removeChild(textArea);
}

/**
 * Toggle row actions visibility
 * @param {HTMLElement} button - The button that was clicked
 */
function toggleRowActions(button) {
    const row = button.closest('tr');
    const actionsCell = row.querySelector('.actions-cell');
    
    if (actionsCell) {
        actionsCell.classList.toggle('show');
    }
}

/**
 * Show link details in modal
 * @param {string} shortUrl - Short URL to display
 * @param {string} originalUrl - Original URL to display
 */
function showLinkDetails(shortUrl, originalUrl) {
    // This function would show link details in a modal
    // Implementation depends on your modal system
    console.log('Showing details for:', shortUrl, originalUrl);
}

/**
 * Show copy success notification
 */
function showCopySuccess() {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-success';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> URL copied to clipboard!';
    document.body.appendChild(toast);

    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

/**
 * Show copy error notification
 */
function showCopyError() {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-error';
    toast.innerHTML = '<i class="fas fa-exclamation-circle"></i> Failed to copy to clipboard';
    document.body.appendChild(toast);

    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);

    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

/**
 * Initialize copy buttons and action toggles
 */
function initCopyButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-copy-url]')) {
            const button = e.target.closest('[data-copy-url]');
            const url = button.getAttribute('data-copy-url');
            copyToClipboard(url);
        }
        
        if (e.target.closest('[data-copy-modal]')) {
            const button = e.target.closest('[data-copy-modal]');
            const modalId = button.getAttribute('data-copy-modal');
            const modalElement = document.getElementById(modalId);
            if (modalElement) {
                copyToClipboard(modalElement.value);
            }
        }
        
        if (e.target.closest('[data-toggle-actions]')) {
            const button = e.target.closest('[data-toggle-actions]');
            toggleRowActions(button);
        }
        
        if (e.target.closest('[data-show-details]')) {
            const button = e.target.closest('[data-show-details]');
            const shortUrl = button.getAttribute('data-short-url');
            const originalUrl = button.getAttribute('data-original-url');
            showLinkDetails(shortUrl, originalUrl);
        }
    });
}

/**
 * Show notification message
 * @param {string} message - Message to display
 * @param {string} type - Type of notification (success, error, info, warning)
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(function() {
        notification.remove();
    }, 3000);
}

// Initialize copy buttons when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initCopyButtons();
});
