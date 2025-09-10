<?php
/**
 * Sidebar Helper Functions
 * Provides consistent sidebar navigation across all pages
 */

/**
 * Get the current page name from the script name
 */
function getCurrentPage() {
    $scriptName = basename($_SERVER['PHP_SELF']);
    return str_replace('.php', '', $scriptName);
}

/**
 * Check if a navigation link should be active
 */
function isNavLinkActive($pageName) {
    $currentPage = getCurrentPage();
    
    // Special cases for page mapping
    $pageMapping = [
        'index' => 'index',
        'links' => 'links', 
        'create_link' => 'create_link',
        'view_targets' => 'view_targets',
        'admin' => 'admin',
        'privacy' => 'privacy',
        'terms' => 'terms',
        'cookies' => 'cookies',
        'password_recovery' => 'password_recovery'
    ];
    
    return isset($pageMapping[$currentPage]) && $pageMapping[$currentPage] === $pageName;
}

/**
 * Generate sidebar navigation HTML
 */
function generateSidebarNav() {
    $navItems = [
        [
            'url' => 'index.php',
            'icon' => 'fas fa-home',
            'text' => 'Dashboard',
            'page' => 'index'
        ],
        [
            'url' => 'links.php',
            'icon' => 'fas fa-link',
            'text' => 'Meus Links',
            'page' => 'links'
        ],
        [
            'url' => 'create_link.php',
            'icon' => 'fas fa-plus',
            'text' => 'Criar Link',
            'page' => 'create_link'
        ],
        [
            'url' => 'view_targets.php',
            'icon' => 'fas fa-map-marker-alt',
            'text' => 'Geolocalização',
            'page' => 'view_targets'
        ],
        [
            'url' => 'admin.php',
            'icon' => 'fas fa-cog',
            'text' => 'Painel Admin',
            'page' => 'admin'
        ],
        [
            'url' => 'privacy.php',
            'icon' => 'fas fa-user-shield',
            'text' => 'Política de Privacidade',
            'page' => 'privacy'
        ],
        [
            'url' => 'terms.php',
            'icon' => 'fas fa-file-contract',
            'text' => 'Termos de Uso',
            'page' => 'terms'
        ],
        [
            'url' => 'cookies.php',
            'icon' => 'fas fa-cookie-bite',
            'text' => 'Política de Cookies',
            'page' => 'cookies'
        ],
        [
            'url' => 'password_recovery.php',
            'icon' => 'fas fa-key',
            'text' => 'Recuperar Senha',
            'page' => 'password_recovery'
        ]
    ];
    
    $html = '<ul class="apple-sidebar-nav">';
    
    foreach ($navItems as $item) {
        $activeClass = isNavLinkActive($item['page']) ? ' active' : '';
        $html .= '<li>';
        $html .= '<a class="apple-nav-link' . $activeClass . '" href="' . $item['url'] . '">';
        $html .= '<i class="' . $item['icon'] . '"></i> ' . $item['text'];
        $html .= '</a>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    
    return $html;
}

/**
 * Generate mobile header HTML
 */
function generateMobileHeader() {
    $html = '<div class="mobile-header">';
    $html .= '<a href="index.php" class="navbar-brand">';
    $html .= '<i class="fas fa-shield-alt"></i> IP Logger';
    $html .= '</a>';
    $html .= '<button class="apple-btn" type="button" id="sidebarToggle" title="Abrir Menu">';
    $html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">';
    $html .= '<line x1="3" y1="6" x2="21" y2="6"></line>';
    $html .= '<line x1="3" y1="12" x2="21" y2="12"></line>';
    $html .= '<line x1="3" y1="18" x2="21" y2="18"></line>';
    $html .= '</svg>';
    $html .= '</button>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate sidebar overlay HTML
 */
function generateSidebarOverlay() {
    return '<div class="sidebar-overlay" id="sidebarOverlay"></div>';
}

/**
 * Generate complete sidebar HTML
 */
function generateSidebar() {
    $html = '<nav class="col-md-3 col-lg-2 apple-sidebar sidebar" id="sidebar">';
    $html .= '<div class="position-sticky pt-3">';
    $html .= '<div class="apple-sidebar-header text-center">';
    $html .= '<a href="index.php" class="apple-nav-brand">';
    $html .= '<i class="fas fa-shield-alt"></i> IP Logger';
    $html .= '</a>';
    $html .= '<p class="apple-subhead" style="color: var(--apple-text-white); opacity: 0.8; margin-top: var(--apple-space-xs);">URL Shortener & Tracker</p>';
    $html .= '</div>';
    $html .= generateSidebarNav();
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}
?>
