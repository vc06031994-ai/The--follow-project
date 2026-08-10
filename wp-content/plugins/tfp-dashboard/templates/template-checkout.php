<?php
if (!defined('ABSPATH')) exit;

tfp_dashboard_require_login();
add_filter('body_class', function($classes){ $classes[] = 'tfp-checkout-page'; return $classes; });
tfp_dashboard_shell_start('checkout');
tfp_dashboard_render_checkout_tab();
tfp_dashboard_shell_end();
