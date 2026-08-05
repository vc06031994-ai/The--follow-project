<?php
if (!defined('ABSPATH')) exit;

function tfp_dashboard_render_billing_content()
{
    $user_id  = get_current_user_id();
    $name     = tfp_dashboard_user_name();
    $product  = tfp_billing_get_program_product($user_id);
    $has_paid = tfp_billing_user_has_paid($user_id);

    tfp_dashboard_render_page_header(
        __('Billing', 'tfp-dashboard'),
        sprintf(esc_html__('%s, Setup Your Profile', 'tfp-dashboard'), esc_html(tfp_dashboard_user_first_name() ?: $name)),
        __('Keep your profile up to date so we can connect you with classes, facilitators, and reminders.', 'tfp-dashboard')
    );
    ?>
  <div class="tfp-dash-billing-detail__intro">
          <h2 class="tfp-dash-section__hi"><?php printf(esc_html__('Hi, %s Complete Your Enrollment', 'tfp-dashboard'), esc_html($name)); ?></h2>
            <p class="tfp-dash-section__lead"><?php esc_html_e("Save your payment method and pay your program bill to unlock your class dashboard.", 'tfp-dashboard'); ?></p>
        </div>
    <div class="tfp-dash-billingcard tfp-dash-panel">
        <?php if (!$product) : ?>
            <p><?php esc_html_e('No program selected yet. Please complete your profile first.', 'tfp-dashboard'); ?></p>
        <?php elseif ($has_paid) :
            $order = tfp_billing_get_program_order($user_id);
            ?>
            <div class="tfp-dash-billingcard__paid">
                <span class="tfp-dash-badge tfp-dash-badge--success"><?php esc_html_e('Paid', 'tfp-dashboard'); ?></span>
                <h3><?php echo esc_html($product->get_name()); ?></h3>
                <p>
                    <?php
                    if ($order) {
                        printf(
                            /* translators: 1: order total, 2: order date */
                            esc_html__('You paid %1$s on %2$s. Your class dashboard is unlocked.', 'tfp-dashboard'),
                            wp_kses_post($order->get_formatted_order_total()),
                            esc_html(wc_format_datetime($order->get_date_paid() ?: $order->get_date_created()))
                        );
                    } else {
                        esc_html_e('Your payment is confirmed. Your class dashboard is unlocked.', 'tfp-dashboard');
                    }
                    ?>
                </p>
            </div>
        <?php else : ?>
            <?php $product_image = $product->get_image('medium') ?: wc_placeholder_img('medium'); ?>
            <div class="tfp-dash-billingcard__visual">
                <?php echo $product_image; ?>
            </div>
            <div class="tfp-dash-billingcard__content">
                <p class="tfp-dash-billingcard__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
                <h3><?php echo esc_html($product->get_name()); ?></h3>
                <p class="tfp-dash-billingcard__desc"><?php esc_html_e('Confirm your spot by paying your tuition. Once payment is complete, your class dashboard and resources will unlock.', 'tfp-dashboard'); ?></p>
                <div class="tfp-dash-billingcard__actions">
                    <a href="<?php echo esc_url(tfp_billing_pay_now_url($user_id)); ?>" class="tfp-dash-btn tfp-dash-btn--primary">
                        <?php esc_html_e('Pay Now', 'tfp-dashboard'); ?>
                    </a>
                </div>
                <span class="tfp-dash-billingcard__status-badge"><?php esc_html_e('Unpaid', 'tfp-dashboard'); ?></span>
            </div>

        <?php endif; ?>
    </div>

    <?php if (!$has_paid) : ?>
        <div class="tfp-dash-panel tfp-dash-financialaid-banner">
            <div class="tfp-dash-billingcard__visual">
                <img src="<?php echo esc_url( TFP_DASH_URL . 'assets/images/financial.webp' ); ?>" alt="<?php esc_attr_e('Financial assistance', 'tfp-dashboard'); ?>">
            </div>
            <div class="tfp-dash-financialaid-banner__text">
                <h3><?php esc_html_e('Financial Assistance Application', 'tfp-dashboard'); ?></h3>
                <p class="tfp-dash-billingcard__desc"><?php esc_html_e('Complete this form to be considered for partial or full tuition assistance.', 'tfp-dashboard'); ?></p>
                <a href="<?php echo esc_url(tfp_dashboard_get_url('tfp-dashboard-financial-aid')); ?>" class="tfp-dash-btn tfp-dash-btn--primary">
                    <?php esc_html_e('Apply for Scholarship', 'tfp-dashboard'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
    <?php
}
