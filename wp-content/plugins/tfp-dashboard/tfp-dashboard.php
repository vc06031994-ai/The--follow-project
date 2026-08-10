<?php
/**
 * Plugin Name: TFP Dashboard
 * Description: Custom-coded student dashboard (Home, Grades, Communication, Documents, Calendar, Profile) for The Follow Project. Reuses helper functions from the TFP Authentication plugin. Not built with Elementor — fully custom templates for app-like behaviour and pixel-perfect design control.
 * Version: 1.0.0
 * Author: The Follow Project
 * Text Domain: tfp-dashboard
 */

if (!defined('ABSPATH')) exit;

define('TFP_DASH_VERSION', '1.0.0');
define('TFP_DASH_PATH', plugin_dir_path(__FILE__));
define('TFP_DASH_URL', plugin_dir_url(__FILE__));

/**
 * Activation: create the chat messages table.
 */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table           = $wpdb->prefix . 'tfp_ticket_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ticket_id BIGINT UNSIGNED NOT NULL,
        sender_id BIGINT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY ticket_id (ticket_id),
        KEY created_at (created_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
});

/**
 * Core includes.
 */
require_once TFP_DASH_PATH . 'includes/helpers.php';
require_once TFP_DASH_PATH . 'includes/templates.php';
require_once TFP_DASH_PATH . 'includes/shell.php';
require_once TFP_DASH_PATH . 'includes/icons.php';
require_once TFP_DASH_PATH . 'includes/page-home.php';
require_once TFP_DASH_PATH . 'includes/communication/cpt-tickets.php';
require_once TFP_DASH_PATH . 'includes/communication/db.php';
require_once TFP_DASH_PATH . 'includes/communication/ajax.php';
require_once TFP_DASH_PATH . 'includes/communication/page-communication.php';
require_once TFP_DASH_PATH . 'includes/billing/helpers.php';
require_once TFP_DASH_PATH . 'includes/page-billing.php';
require_once TFP_DASH_PATH . 'includes/page-payment-details.php';
require_once TFP_DASH_PATH . 'includes/financial-aid/cpt.php';
require_once TFP_DASH_PATH . 'includes/financial-aid/ajax.php';
require_once TFP_DASH_PATH . 'includes/financial-aid/admin.php';
require_once TFP_DASH_PATH . 'includes/financial-aid/checkout.php';
require_once TFP_DASH_PATH . 'includes/page-financial-aid.php';
require_once TFP_DASH_PATH . 'includes/profile/ajax.php';
require_once TFP_DASH_PATH . 'includes/page-profile.php';
require_once TFP_DASH_PATH . 'includes/page-update-profile.php';
require_once TFP_DASH_PATH . 'includes/learndash/helpers.php';
require_once TFP_DASH_PATH . 'includes/learndash/admin-meta-box.php';
require_once TFP_DASH_PATH . 'includes/learndash/syllabus-shortcode.php';
require_once TFP_DASH_PATH . 'includes/week/cpt-readings.php';
require_once TFP_DASH_PATH . 'includes/week/ajax.php';
require_once TFP_DASH_PATH . 'includes/week/homework-helpers.php';
require_once TFP_DASH_PATH . 'includes/week/homework-ajax.php';
require_once TFP_DASH_PATH . 'includes/page-checkout.php';
require_once TFP_DASH_PATH . 'includes/page-week.php';

/**
 * Dashboard pages are logged-in, per-user, dynamic content — they must
 * never be served from a page cache (WP Rocket, LiteSpeed Cache, etc.).
 * DONOTCACHEPAGE is a widely-respected constant across most WordPress
 * caching plugins, including WP Rocket.
 */
add_action('template_redirect', function () {
    if (function_exists('tfp_dashboard_is_dashboard_page') && tfp_dashboard_is_dashboard_page()) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
    }
}, 1);

/**
 * Assets. Dashboard pages use their own custom templates (no theme
 * header/footer), so we enqueue everything unconditionally on those
 * templates only, via the templates.php logic (see is_tfp_dashboard_page()).
 */
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('tfp_dashboard_is_dashboard_page') || !tfp_dashboard_is_dashboard_page()) {
        return;
    }

    wp_enqueue_style('tfp-dashboard-core', TFP_DASH_URL . 'assets/css/dashboard.css', [], TFP_DASH_VERSION);
    wp_enqueue_script('tfp-dashboard-core', TFP_DASH_URL . 'assets/js/dashboard.js', [], TFP_DASH_VERSION, true);

    $template = tfp_dashboard_current_template_slug();

    if ($template === 'tfp-dashboard-communication') {
        wp_enqueue_style('tfp-dashboard-communication', TFP_DASH_URL . 'assets/css/communication.css', ['tfp-dashboard-core'], TFP_DASH_VERSION);
        wp_enqueue_script('tfp-dashboard-communication', TFP_DASH_URL . 'assets/js/communication.js', [], TFP_DASH_VERSION, true);

        wp_localize_script('tfp-dashboard-communication', 'tfpChatSettings', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('tfp_chat_nonce'),
            'currentUser' => get_current_user_id(),
            'pollInterval'=> 4000,
        ]);
    }

    if (in_array($template, ['tfp-dashboard-billing', 'tfp-dashboard-payment-details', 'tfp-dashboard-financial-aid', 'tfp-dashboard-profile', 'tfp-dashboard-update-profile'], true)) {
        wp_enqueue_style('tfp-dashboard-forms', TFP_DASH_URL . 'assets/css/forms.css', ['tfp-dashboard-core'], TFP_DASH_VERSION);
    }

    if ($template === 'tfp-dashboard-financial-aid') {
        wp_enqueue_script('tfp-dashboard-financial-aid', TFP_DASH_URL . 'assets/js/financial-aid.js', [], TFP_DASH_VERSION, true);
        wp_localize_script('tfp-dashboard-financial-aid', 'tfpFinancialAidSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tfp_financial_aid_nonce'),
        ]);
    }

    if ($template === 'tfp-dashboard-profile' || $template === 'tfp-dashboard-update-profile') {
        wp_enqueue_script('tfp-dashboard-profile', TFP_DASH_URL . 'assets/js/profile.js', [], TFP_DASH_VERSION, true);
        wp_localize_script('tfp-dashboard-profile', 'tfpProfileSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tfp_profile_nonce'),
        ]);
    }

    if ($template === 'tfp-dashboard-week') {
        wp_enqueue_style('tfp-dashboard-forms', TFP_DASH_URL . 'assets/css/forms.css', ['tfp-dashboard-core'], TFP_DASH_VERSION);
        wp_enqueue_style('tfp-dashboard-week', TFP_DASH_URL . 'assets/css/week.css', ['tfp-dashboard-core', 'tfp-dashboard-forms'], TFP_DASH_VERSION);
        wp_enqueue_script('tfp-dashboard-week', TFP_DASH_URL . 'assets/js/week.js', [], TFP_DASH_VERSION, true);
        wp_enqueue_script('tfp-dashboard-week-homework', TFP_DASH_URL . 'assets/js/week-homework.js', ['tfp-dashboard-week'], TFP_DASH_VERSION, true);
        wp_localize_script('tfp-dashboard-week', 'tfpWeekSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('tfp_week_nonce'),
        ]);
    }

    if ($template === 'tfp-dashboard-checkout') {
        wp_enqueue_style('tfp-dashboard-checkout', TFP_DASH_URL . 'assets/css/checkout.css', ['tfp-dashboard-core'], TFP_DASH_VERSION);
    }
});



