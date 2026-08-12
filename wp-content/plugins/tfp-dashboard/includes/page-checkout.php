<?php
if (!defined('ABSPATH')) exit;

function tfp_dashboard_render_checkout_tab()
{
    get_header();
    $user_id = get_current_user_id();
    $cart_items = [];
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $cart_items[] = [
                'title' => $product ? $product->get_name() : 'Walking in Freedom',
                'desc'  => 'A 30-Day Galatians Devotional & Small Grou...',
                'qty'   => $cart_item['quantity'],
                'price' => WC()->cart->get_product_price($product),
            ];
        }
    }
    if (empty($cart_items)) {
        $cart_items = [
            ['title'=>'Walking in Freedom','desc'=>'A 30-Day Galatians Devotional & Small Grou...','qty'=>2,'price'=>'61.49'],
            ['title'=>'Walking in Freedom','desc'=>'A 30-Day Galatians Devotional & Small Grou...','qty'=>1,'price'=>'61.49'],
            ['title'=>'Walking in Freedom','desc'=>'A 30-Day Galatians Devotional & Small Grou...','qty'=>1,'price'=>'61.49']
        ];
    }
    ?>
    <div class="tfp-checkout-wrap">
        <div class="tfp-checkout-main">
            <div class="tfp-checkout-tabs" role="tablist">
                <a href="#" class="tfp-checkout-tab is-active">Cart</a>
                <a href="#" class="tfp-checkout-tab">Contact & Shipping</a>
                <a href="#" class="tfp-checkout-tab">Delivery</a>
                <a href="#" class="tfp-checkout-tab">Payment</a>
            </div>

            <div class="tfp-checkout-panel">
                <h2 class="tfp-checkout-step-title">Your Cart</h2>
                <p>Confirm the contents of your shopping cart. All Sales are Final.</p>
                <?php foreach ($cart_items as $item) : ?>
                <div class="tfp-checkout-product-row">
                    <div class="tfp-checkout-product-img"></div>
                    <div class="tfp-checkout-product-info">
                        <h4>Review Your Cart, Edit Your Order</h4>
                        <p><?php echo esc_html($item['desc']); ?></p>
                    </div>
                    <div class="tfp-checkout-qty">
                        <a href="#" style="font-size:13px;color:#570506;text-decoration:none;">-</a>
                        <span style="font-size:15px;font-weight:700;">0</span>
                        <a href="#" style="font-size:13px;color:#570506;text-decoration:none;">+</a>
                    </div>
                    <strong style="font-size:15px;min-width:60px;text-align:right;">$<?php echo esc_html($item['price']); ?></strong>
                    <a href="#" style="font-size:12px;color:#570506;text-decoration:none;">Remove</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="tfp-checkout-sidebar">
            <div class="tfp-checkout-panel">
                <h3>Order Summary</h3>
                <div class="tfp-checkout-summary-row"><span>Subtotal</span><span>$122.97</span></div>
                <div class="tfp-checkout-summary-row"><span>Shipping</span><span>Calculated at next step</span></div>
                <div class="tfp-checkout-summary-row"><span>Shipping</span><span>Calculated at next step</span></div>
                <div class="tfp-checkout-summary-row"><span>Tax (Tax Exempt)</span><span>$0.00</span></div>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--outline" style="width:100%;margin-top:12px;">Edit Cart</a>
                <div class="tfp-checkout-summary-total"><span>Estimated Total</span><span>$132.81</span></div>
                <p style="font-size:13px;color:#2E2E2E;background:#EFEFEF;padding:14px;border-radius:8px;margin-top:16px;"><strong>Allow 3-5 business days for production.</strong></p>
            </div>
        </div>
    </div>
    <?php
    get_footer();
}
