// IP Logger JavaScript Functions

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerList);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Form validation
    $('form').on('submit', function(e) {
        var isValid = true;
        
        // Validate required fields
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
            }
        });
        
        // Validate URL format
        var urlField = $(this).find('input[type="url"]');
        if (urlField.length && urlField.val()) {
            if (!isValidUrl(urlField.val())) {
                urlField.addClass('is-invalid');
                isValid = false;
            } else {
                urlField.removeClass('is-invalid').addClass('is-valid');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            showMessage('Please fill in all required fields correctly.', 'error');
        }
    });

    // Real-time URL validation
    $('input[type="url"]').on('input', function() {
        var url = $(this).val();
        if (url && !isValidUrl(url)) {
            $(this).addClass('is-invalid');
            $(this).next('.invalid-feedback').text('Please enter a valid URL');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });

    // Password strength indicator
    $('input[type="password"]').on('input', function() {
        var password = $(this).val();
        var strength = getPasswordStrength(password);
        updatePasswordStrengthIndicator($(this), strength);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Table row hover effects
    $('.table tbody tr').hover(
        function() { $(this).addClass('table-hover'); },
        function() { $(this).removeClass('table-hover'); }
    );

    // Initialize data tables with search and pagination
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
});

// Utility Functions

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Use the modern clipboard API
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess();
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            fallbackCopyToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess();
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showMessage('Failed to copy to clipboard', 'error');
    }
    
    textArea.remove();
}

function showCopySuccess() {
    var button = event.target.closest('button');
    if (button) {
        var originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }
    
    showMessage('Copied to clipboard!', 'success');
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

function getPasswordStrength(password) {
    var strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}

function updatePasswordStrengthIndicator(field, strength) {
    var indicator = field.next('.password-strength');
    if (!indicator.length) {
        indicator = $('<div class="password-strength mt-1"></div>');
        field.after(indicator);
    }
    
    var strengthText = '';
    var strengthClass = '';
    
    switch(strength) {
        case 0:
        case 1:
            strengthText = 'Very Weak';
            strengthClass = 'text-danger';
            break;
        case 2:
            strengthText = 'Weak';
            strengthClass = 'text-warning';
            break;
        case 3:
            strengthText = 'Medium';
            strengthClass = 'text-info';
            break;
        case 4:
            strengthText = 'Strong';
            strengthClass = 'text-success';
            break;
        case 5:
            strengthText = 'Very Strong';
            strengthClass = 'text-success';
            break;
    }
    
    indicator.html('<small class="' + strengthClass + '">' + strengthText + '</small>');
}

function showMessage(message, type) {
    var alertClass = 'alert-' + (type || 'info');
    var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                   message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                   '</div>';
    
    // Remove existing alerts
    $('.alert').remove();
    
    // Add new alert
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function timeAgo(date) {
    var seconds = Math.floor((new Date() - new Date(date)) / 1000);
    
    var interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    
    return Math.floor(seconds) + " seconds ago";
}

function debounce(func, wait, immediate) {
    var timeout;
    return function executedFunction() {
        var context = this;
        var args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

function throttle(func, limit) {
    var inThrottle;
    return function() {
        var args = arguments;
        var context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// AJAX Functions

function loadTargets(linkId, password) {
    $.ajax({
        url: 'ajax/get_targets.php',
        method: 'POST',
        data: {
            link_id: linkId,
            password: password
        },
        success: function(response) {
            if (response.success) {
                $('#targetsTable tbody').html(response.html);
                updateStatistics(response.stats);
            } else {
                showMessage(response.message, 'error');
            }
        },
        error: function() {
            showMessage('Failed to load targets', 'error');
        }
    });
}

function deleteLink(linkId) {
    if (confirm('Are you sure you want to delete this link? This action cannot be undone.')) {
        $.ajax({
            url: 'ajax/delete_link.php',
            method: 'POST',
            data: { link_id: linkId },
            success: function(response) {
                if (response.success) {
                    showMessage('Link deleted successfully', 'success');
                    location.reload();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function() {
                showMessage('Failed to delete link', 'error');
            }
        });
    }
}

function exportData(linkId, format) {
    window.open('export.php?link_id=' + linkId + '&format=' + format, '_blank');
}

// Chart Functions (if using Chart.js)

function createChart(canvasId, data, type) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    return new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
}

function updateChart(chart, newData) {
    chart.data = newData;
    chart.update();
}

// Map Functions

function initMap(containerId, locations) {
    if (typeof google === 'undefined') {
        console.error('Google Maps API not loaded');
        return;
    }
    
    var map = new google.maps.Map(document.getElementById(containerId), {
        zoom: 2,
        center: { lat: 0, lng: 0 },
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    
    locations.forEach(function(location) {
        var marker = new google.maps.Marker({
            position: { lat: location.lat, lng: location.lng },
            map: map,
            title: location.title
        });
        
        var infowindow = new google.maps.InfoWindow({
            content: location.info
        });
        
        marker.addListener('click', function() {
            infowindow.open(map, marker);
        });
    });
    
    return map;
}

// Privacy and Security Functions

function anonymizeIP(ip) {
    if (!ip) return '';
    var parts = ip.split('.');
    if (parts.length === 4) {
        return parts[0] + '.' + parts[1] + '.*.*';
    }
    return ip;
}

function validateCSRF(token) {
    return $('meta[name="csrf-token"]').attr('content') === token;
}

// Performance Functions

function lazyLoadImages() {
    var images = document.querySelectorAll('img[data-src]');
    var imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(function(img) {
        imageObserver.observe(img);
    });
}

// Initialize lazy loading
$(document).ready(function() {
    lazyLoadImages();
});

// Mobile Navigation Functions
function initMobileNavigation() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (!sidebarToggle || !sidebar || !sidebarOverlay) {
        console.error('Mobile navigation elements not found');
        return;
    }
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        } else {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
        }
    });
    
    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });
    
    // Close sidebar when clicking on nav links (mobile only)
    const navLinks = document.querySelectorAll('.sidebar .pearlight-nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
    
}

// Row Actions Functions
function toggleRowActions(button) {
    const rowActions = button.nextElementSibling;
    const isExpanded = button.classList.contains('expanded');
    
    // Close all other expanded rows
    document.querySelectorAll('.expand-btn.expanded').forEach(btn => {
        if (btn !== button) {
            btn.classList.remove('expanded');
            btn.nextElementSibling.style.display = 'none';
        }
    });
    
    // Toggle current row
    if (isExpanded) {
        button.classList.remove('expanded');
        rowActions.style.display = 'none';
    } else {
        button.classList.add('expanded');
        rowActions.style.display = 'block';
    }
}

// Initialize mobile navigation when DOM is ready
$(document).ready(function() {
    console.log('DOM ready, initializing mobile navigation...');
    initMobileNavigation();
});

// Also try vanilla JS approach
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded with vanilla JS, initializing mobile navigation...');
    initMobileNavigation();
});