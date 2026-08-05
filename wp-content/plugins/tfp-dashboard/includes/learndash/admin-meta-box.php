<?php
if (!defined('ABSPATH')) exit;

/**
 * Registers tfp_week_video_url / meeting fields as REST-visible post meta
 * so the block editor's own data store (core/editor) can read and save
 * them — this is what powers the Gutenberg sidebar panel added in
 * assets/js/admin-week-meta-panel.js.
 *
 * We deliberately do NOT use the classic add_meta_box() mechanism here:
 * LearnDash strips third-party meta boxes from its Lesson edit screen,
 * so a classic meta box never renders there. The Gutenberg sidebar-panel
 * approach hooks into core editor data instead, which LearnDash doesn't
 * (and can't reasonably) block.
 */
add_action('init', function () {
    // IMPORTANT: Gutenberg meta fields via REST API ONLY work if the post type supports 'custom-fields'.
    // Since LearnDash disables this by default, we must re-enable it for our Sidebar Panel to save data.
    add_post_type_support('sfwd-lessons', 'custom-fields');

    $fields = [
        'tfp_week_video_url'          => 'esc_url_raw',
        'tfp_week_meeting_date'       => 'sanitize_text_field',
        'tfp_week_meeting_time'       => 'sanitize_text_field',
        'tfp_week_facilitator_name'   => 'sanitize_text_field',
    ];

    foreach ($fields as $key => $sanitizer) {
        register_post_meta('sfwd-lessons', $key, [
            'show_in_rest'      => true,
            'single'            => true,
            'type'              => 'string',
            'sanitize_callback' => $sanitizer,
            'auth_callback'     => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    // Homework Questions (JSON)
    register_post_meta('sfwd-lessons', 'tfp_week_homework_questions', [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => function ($value) {
            if (empty($value)) return '';
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return ''; // Fallback to empty if invalid JSON
            }
            // Re-encode to ensure safe, compact storage
            return wp_json_encode($decoded);
        },
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ]);
}, 20);

add_action('enqueue_block_editor_assets', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || $screen->post_type !== 'sfwd-lessons') {
        return;
    }

    wp_enqueue_script(
        'tfp-week-meta-panel',
        TFP_DASH_URL . 'assets/js/admin-week-meta-panel.js',
        ['wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n', 'wp-compose'],
        TFP_DASH_VERSION,
        true
    );
});
