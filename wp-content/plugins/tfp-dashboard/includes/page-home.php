<?php
if (!defined('ABSPATH')) exit;

/**
 * A single reusable task card: name, heading, description, progress ring,
 * button. $type lets future logic (billing, payment) plug in their own %
 * source via the tfp_dashboard_task_card_percent filter without rewriting
 * this.
 */
function tfp_dashboard_render_task_card($type, $name, $heading, $description, $button_text, $button_url)
{
    $percent = 0;

    if ($type === 'profile') {
        $percent = tfp_dashboard_profile_completion();
    } elseif ($type === 'billing' && function_exists('tfp_billing_user_has_saved_payment_method')) {
        $percent = tfp_billing_user_has_saved_payment_method() ? 100 : 0;
    } elseif ($type === 'program_payment' && function_exists('tfp_billing_user_has_paid')) {
        $percent = tfp_billing_user_has_paid() ? 100 : 0;
    }

    // Future task types (billing, program_payment, etc.) can hook in here.
    $percent = apply_filters('tfp_dashboard_task_card_percent', $percent, $type);

    ?>
    <div class="tfp-dash-panel tfp-dash-taskcard">
        <div class="tfp-dash-taskcard__body">
            <h3 class="tfp-dash-taskcard__title"><?php echo esc_html($name); ?></h3>
            <p class="tfp-dash-taskcard__label"><?php echo esc_html($heading); ?></p>
            <p class="tfp-dash-taskcard__desc"><?php echo esc_html($description); ?></p>
            <a href="<?php echo esc_url($button_url); ?>" class="tfp-dash-btn tfp-dash-btn--primary">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
        <div class="tfp-dash-taskcard__ring">
            <?php echo tfp_dashboard_render_progress_ring($percent, 88, 7); ?>
            <span class="tfp-dash-taskcard__ring-label"><?php esc_html_e('complete', 'tfp-dashboard'); ?></span>
        </div>
    </div>
    <?php
}

function tfp_dashboard_render_home_content()
{
    $name = tfp_dashboard_user_first_name();
    if (empty($name)) {
        $name = tfp_dashboard_user_name();
    }

    $has_paid = function_exists('tfp_billing_user_has_paid') && tfp_billing_user_has_paid();

    tfp_dashboard_render_page_header(
        __('Home', 'tfp-dashboard'),
        sprintf(esc_html__('Welcome back, %s', 'tfp-dashboard'), esc_html(tfp_dashboard_user_name())),
        __("Here's where you are today — your progress, what's due, and what's coming up.", 'tfp-dashboard')
    );

    if ($has_paid && function_exists('tfp_ld_get_program_course_id')) {
        $course_id = tfp_ld_get_program_course_id();
        if ($course_id && !empty(tfp_ld_get_weeks($course_id))) {
            tfp_dashboard_render_class_journey_home($course_id);
            return;
        }
    }

    tfp_dashboard_render_home_task_cards($name);
}

/**
 * The "before you're fully set up" view — Complete Profile / Set Up
 * Billing / Pay for Program task cards. Original Home page behaviour,
 * unchanged, just extracted into its own function.
 */
function tfp_dashboard_render_home_task_cards($name)
{
    $profile_url = function_exists('tfp_dashboard_get_url') ? tfp_dashboard_get_url('tfp-dashboard-profile') : '#';
    ?>
    <div class="tfp-dash-section">
        <h2 class="tfp-dash-section__hi"><?php printf(esc_html__('Hi, %s', 'tfp-dashboard'), esc_html($name)); ?></h2>
        <p class="tfp-dash-section__lead"><?php esc_html_e("You've confirmed your email. Let's finish your registration so you can unlock your class dashboard.", 'tfp-dashboard'); ?></p>

        <div class="tfp-dash-taskcards">
            <?php
            tfp_dashboard_render_task_card(
                'profile',
                tfp_dashboard_user_name(),
                __('Complete Your Profile', 'tfp-dashboard'),
                __("We'll use this information to personalize your class experience and connect you to updates and discussions.", 'tfp-dashboard'),
                __('Go to Profile', 'tfp-dashboard'),
                $profile_url
            );

            $payment_details_url = function_exists('tfp_dashboard_get_url') ? tfp_dashboard_get_url('tfp-dashboard-payment-details') : '#';

            // "Set Up Billing" stays visible even after payment — users may
            // need to come back and update their saved card later.
            if (function_exists('tfp_billing_get_program_product')) {
                tfp_dashboard_render_task_card(
                    'billing',
                    tfp_dashboard_user_name(),
                    __('Set Up Billing', 'tfp-dashboard'),
                    __('Secure your spot by completing your payment details. Billing unlocks your access to videos, assignments, and program resources.', 'tfp-dashboard'),
                    __('Set Up Billing', 'tfp-dashboard'),
                    $payment_details_url
                );
            }

            // "Pay for Your Program" only matters until the program is
            // actually paid for — hidden afterwards.
            if (function_exists('tfp_billing_get_program_product') && !tfp_billing_user_has_paid()) {
                $billing_url = tfp_dashboard_get_url('tfp-dashboard-billing');

                tfp_dashboard_render_task_card(
                    'program_payment',
                    tfp_dashboard_user_name(),
                    __('Pay for Your Program', 'tfp-dashboard'),
                    __('Confirm your spot by paying your tuition. Once payment is complete, your class dashboard and resources will unlock.', 'tfp-dashboard'),
                    __('View & Pay Bill', 'tfp-dashboard'),
                    $billing_url
                );
            }

            /**
             * Once paid, future cards (LearnDash progress, next meeting,
             * etc.) will plug in here.
             */
            do_action('tfp_dashboard_home_after_profile_card');
            ?>
        </div>
    </div>
    <?php
}

/**
 * The paid, "in the program" Home view — weekly journey circles, current
 * lesson card, and the Continue Lesson button that links into the Week
 * page (includes/page-week.php).
 */
function tfp_dashboard_render_class_journey_home($course_id)
{
    $user_id = get_current_user_id();
    $weeks   = tfp_ld_get_weeks($course_id);
    $current_week = tfp_ld_get_current_week($user_id, $course_id);
    $progress = tfp_ld_get_journey_progress($user_id, $course_id);
    $week_url = function_exists('tfp_dashboard_get_url') ? tfp_dashboard_get_url('tfp-dashboard-week') : '#';

    $percent = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;
    ?>
    <div class="tfp-journey">
        <div class="tfp-journey__header">
            <h2><?php printf(esc_html__('%d Week Discipleship Journey', 'tfp-dashboard'), $progress['total']); ?></h2>
        </div>

        <div class="tfp-journey__circles">
            <?php foreach ($weeks as $index => $week) :
                $is_complete = tfp_ld_is_week_complete($user_id, $week->ID);
                $is_current  = $current_week && $current_week->ID === $week->ID;
                $state = $is_complete ? 'complete' : ($is_current ? 'current' : 'upcoming');
                ?>
                <span class="tfp-journey__circle tfp-journey__circle--<?php echo esc_attr($state); ?>" title="<?php echo esc_attr($week->post_title); ?>">
                    <?php echo esc_html($index + 1); ?>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="tfp-journey__progressbar">
            <div class="tfp-journey__progressbar-fill" style="width: <?php echo esc_attr($percent); ?>%;"></div>
        </div>
        <div class="tfp-journey__progress-label">
            <span><?php printf(esc_html__('%1$d of %2$d weeks completed', 'tfp-dashboard'), $progress['completed'], $progress['total']); ?></span>
        </div>
    </div>

    <?php if ($current_week) :
        $continue_url = add_query_arg(['lesson_id' => $current_week->ID], $week_url);
        ?>
        <div class="tfp-dash-panel tfp-journey__current">
            <h3><?php esc_html_e('Current Lesson', 'tfp-dashboard'); ?></h3>
            <p class="tfp-journey__current-title"><?php echo esc_html($current_week->post_title); ?></p>
            <a href="<?php echo esc_url($continue_url); ?>" class="tfp-dash-btn tfp-dash-btn--primary">
                <?php esc_html_e('Continue Lesson', 'tfp-dashboard'); ?>
            </a>
            <a href="<?php echo esc_url(apply_filters('tfp_dashboard_discord_url', '#')); ?>" target="_blank" rel="noopener" class="tfp-dash-btn tfp-dash-btn--outline">
                <?php esc_html_e('Go to Discord', 'tfp-dashboard'); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="tfp-dash-panel tfp-journey__current">
            <h3><?php esc_html_e("You've completed every week!", 'tfp-dashboard'); ?></h3>
            <p><?php esc_html_e('Great work finishing your program.', 'tfp-dashboard'); ?></p>
        </div>
    <?php endif; ?>
    <?php
}
