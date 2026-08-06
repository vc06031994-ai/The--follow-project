<?php
if (!defined('ABSPATH')) exit;

// Add Meta Box
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tfp_financial_aid_details',
        __('Application Details & Approval', 'tfp-dashboard'),
        'tfp_financial_aid_meta_box_html',
        'tfp_financial_aid',
        'normal',
        'high'
    );
});

function tfp_financial_aid_meta_box_html($post) {
    wp_nonce_field('tfp_financial_aid_save_meta', 'tfp_financial_aid_meta_nonce');

    $fields = tfp_financial_aid_field_map();
    $status = get_post_meta($post->ID, '_tfp_status', true) ?: 'pending';
    $discount = get_post_meta($post->ID, '_tfp_discount_percentage', true) ?: '100';
    $generated_coupon = get_post_meta($post->ID, '_tfp_generated_coupon', true);

    echo '<table class="form-table"><tbody>';
    
    // Display Applicant Info
    foreach ($fields as $key => $config) {
        $value = get_post_meta($post->ID, '_tfp_' . $key, true);
        echo '<tr>';
        echo '<th scope="row">' . esc_html($config['label']) . '</th>';
        echo '<td>' . esc_html($value !== '' ? $value : '—') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<hr style="margin: 20px 0;">';
    echo '<h3>' . __('Approval Settings', 'tfp-dashboard') . '</h3>';

    echo '<table class="form-table"><tbody>';
    
    // Status
    echo '<tr>';
    echo '<th scope="row"><label for="tfp_status">' . __('Status', 'tfp-dashboard') . '</label></th>';
    echo '<td>';
    echo '<select name="tfp_status" id="tfp_status">';
    foreach (tfp_financial_aid_statuses() as $val => $label) {
        $selected = selected($status, $val, false);
        echo '<option value="' . esc_attr($val) . '" ' . $selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    // Discount Percentage
    echo '<tr>';
    echo '<th scope="row"><label for="tfp_discount_percentage">' . __('Discount Percentage (%)', 'tfp-dashboard') . '</label></th>';
    echo '<td>';
    echo '<input type="number" name="tfp_discount_percentage" id="tfp_discount_percentage" value="' . esc_attr($discount) . '" min="1" max="100" class="small-text"> %';
    echo '<p class="description">' . __('This discount will be applied automatically to the user\'s checkout if approved.', 'tfp-dashboard') . '</p>';
    echo '</td>';
    echo '</tr>';

    if ($generated_coupon) {
        echo '<tr>';
        echo '<th scope="row">' . __('Generated Coupon', 'tfp-dashboard') . '</th>';
        echo '<td><strong>' . esc_html($generated_coupon) . '</strong></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// Save Meta Box Data
add_action('save_post_tfp_financial_aid', function ($post_id) {
    if (!isset($_POST['tfp_financial_aid_meta_nonce']) || !wp_verify_nonce($_POST['tfp_financial_aid_meta_nonce'], 'tfp_financial_aid_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $old_status = get_post_meta($post_id, '_tfp_status', true);
    $new_status = sanitize_text_field($_POST['tfp_status'] ?? 'pending');
    $discount_percentage = absint($_POST['tfp_discount_percentage'] ?? 100);

    update_post_meta($post_id, '_tfp_status', $new_status);
    update_post_meta($post_id, '_tfp_discount_percentage', $discount_percentage);

    if ($old_status !== 'approved' && $new_status === 'approved') {
        tfp_financial_aid_process_approval($post_id, $discount_percentage);
    } elseif ($old_status !== 'rejected' && $new_status === 'rejected') {
        tfp_financial_aid_process_rejection($post_id);
    }
});

function tfp_financial_aid_process_approval($post_id, $discount_percentage) {
    $student_id = get_post_meta($post_id, '_tfp_student_id', true);
    $user = get_userdata($student_id);
    $program_id = get_post_meta($post_id, '_tfp_program_id', true); // Currently not forcing course restriction to keep it simple, but could.

    if (!$user) return;

    // Generate Coupon if WooCommerce is active
    if (class_exists('WooCommerce')) {
        $coupon_code = 'FA-' . strtoupper(wp_generate_password(8, false, false));
        
        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('percent');
        $coupon->set_amount($discount_percentage);
        $coupon->set_email_restrictions([$user->user_email]);
        // Set individual use and not applicable with other coupons if needed
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1); // Optional: can be used once
        $coupon->save();

        update_post_meta($post_id, '_tfp_generated_coupon', $coupon_code);
    }

    // Send Email
    $site_name = get_bloginfo('name');
    $subject = sprintf(__('[%s] Your Financial Aid Application is Approved!', 'tfp-dashboard'), $site_name);
    
    $body = sprintf(__("Hi %s,\n\nGreat news! Your financial aid application has been approved with a %d%% discount.\n\nWhen you proceed to checkout using this email address (%s), your discount will be automatically applied.\n\nThank you,\n%s", 'tfp-dashboard'), 
        $user->first_name ?: $user->display_name,
        $discount_percentage,
        $user->user_email,
        $site_name
    );

    wp_mail($user->user_email, $subject, $body);
}

function tfp_financial_aid_process_rejection($post_id) {
    $student_id = get_post_meta($post_id, '_tfp_student_id', true);
    $user = get_userdata($student_id);

    if (!$user) return;

    // Send Email
    $site_name = get_bloginfo('name');
    $subject = sprintf(__('[%s] Update on your Financial Aid Application', 'tfp-dashboard'), $site_name);
    
    $body = sprintf(__("Hi %s,\n\nThank you for applying for financial aid. Unfortunately, we are unable to approve your application at this time.\n\nIf you have any questions, please contact our support team.\n\nThank you,\n%s", 'tfp-dashboard'), 
        $user->first_name ?: $user->display_name,
        $site_name
    );

    wp_mail($user->user_email, $subject, $body);
}

// -----------------------------------------------------------------------------
// CSV Export Functionality
// -----------------------------------------------------------------------------

// 1. Add "Export CSV" button to the CPT list table
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type === 'tfp_financial_aid') {
        echo '<input type="submit" name="tfp_export_financial_aid_csv" id="tfp_export_financial_aid_csv" class="button button-primary" value="' . esc_attr__('Export to CSV', 'tfp-dashboard') . '">';
    }
});

// 2. Handle the CSV Generation
add_action('admin_init', function () {
    if (isset($_GET['tfp_export_financial_aid_csv']) && isset($_GET['post_type']) && $_GET['post_type'] === 'tfp_financial_aid') {
        
        // Ensure user has permission
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get all fields map to build headers
        $fields = tfp_financial_aid_field_map();
        
        // Define CSV Headers
        $headers = ['Date Submitted', 'Status', 'Applicant Name', 'Email'];
        foreach ($fields as $key => $config) {
            $headers[] = $config['label'];
        }
        $headers[] = 'Discount %';
        $headers[] = 'Generated Coupon';

        // Set Headers for CSV Download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=financial-aid-applications-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Add BOM to fix UTF-8 in Excel
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        fputcsv($output, $headers);

        // Fetch all financial aid posts
        $args = [
            'post_type'      => 'tfp_financial_aid',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ];
        $applications = get_posts($args);

        foreach ($applications as $app) {
            $student_id = get_post_meta($app->ID, '_tfp_student_id', true);
            $user = get_userdata($student_id);
            
            $status = get_post_meta($app->ID, '_tfp_status', true) ?: 'pending';
            $discount = get_post_meta($app->ID, '_tfp_discount_percentage', true);
            $coupon = get_post_meta($app->ID, '_tfp_generated_coupon', true);

            $row = [
                get_the_date('Y-m-d H:i:s', $app->ID),
                ucfirst($status),
                $user ? $user->display_name : 'Unknown',
                $user ? $user->user_email : 'Unknown',
            ];

            // Append mapped fields data
            foreach ($fields as $key => $config) {
                $value = get_post_meta($app->ID, '_tfp_' . $key, true);
                
                // Program ID ko Program Name me convert karein
                if ($key === 'program_id' && !empty($value)) {
                    $program_title = get_the_title($value);
                    if ($program_title) {
                        $value = $program_title;
                    }
                }
                
                $row[] = $value !== '' ? $value : '';
            }
            
            $row[] = $discount ? $discount . '%' : '';
            $row[] = $coupon ?: '';

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
});
