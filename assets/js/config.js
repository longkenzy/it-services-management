/**
 * IT CRM - Configuration Page JavaScript
 * File: assets/js/config.js
 * Mục đích: Xử lý tương tác người dùng cho trang cấu hình
 */

// Case Type Functions are now loaded from case-types.js

$(document).ready(function() {
    // Initialize configuration page
    initializeConfigPage();
    
    // Handle form submissions
    setupFormHandlers();
    
    // Handle action buttons
    setupActionButtons();
});

/**
 * Initialize configuration page
 */
function initializeConfigPage() {
    // Tab switching với jQuery (không dùng Bootstrap vì yêu cầu không reload)
    $('#configTabs button').on('click', function() {
        var targetTab = $(this).data('bs-target');
        
        // Remove active class from all tabs and content
        $('#configTabs .nav-link').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Show corresponding content
        $(targetTab).addClass('show active');
        
        // Update URL hash without reloading
        history.pushState(null, null, targetTab);
    });
    
    // Load tab from URL hash
    var hash = window.location.hash;
    if (hash && $(hash).length) {
        $('[data-bs-target="' + hash + '"]').trigger('click');
    }
}

/**
 * Setup form handlers
 */
function setupFormHandlers() {
    // Department form handlers
    setupDepartmentFormHandlers();
    
    // Partner company form handlers
    setupPartnerFormHandlers();
    
    // Case type form handlers
    setupCaseTypeHandlers();
}

/**
 * Setup department form handlers
 */
function setupDepartmentFormHandlers() {
    // Department form handlers are now in departments.js (inline editing)
}

/**
 * Setup partner company form handlers
 */
function setupPartnerFormHandlers() {
    // Partner form handlers are now in partners.js (inline editing)
}

/**
 * Setup case type form handlers
 */
function setupCaseTypeHandlers() {
    // Inline editing handlers are now handled by onclick attributes
    // and the new inline editing functions
}

// Case type functions are now defined globally above

/**
 * Capitalize first letter
 */
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Setup action buttons
 */
function setupActionButtons() {
    // Department action buttons are handled by global functions
    // Partner action buttons are handled by global functions
}

// Department functions are now loaded from departments.js

// Partner functions are now loaded from partners.js

/**
 * Validate email format
 */
function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show alert message - Now using toast notifications from alert.js
 * This function is removed to avoid conflicts with the global alert system
 */
// Function removed - now using global showAlert() from alert.js

// Case Type Functions are now loaded from case-types.js file