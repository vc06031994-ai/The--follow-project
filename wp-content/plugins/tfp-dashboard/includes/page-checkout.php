<?php
if (!defined('ABSPATH')) exit;

function tfp_dashboard_render_checkout_tab()
{
    $user_id = get_current_user_id();
    $home_url = function_exists('tfp_dashboard_get_url') ? tfp_dashboard_get_url('tfp-dashboard-home') : '#';
    $cart_items = [
        ['title'=>'Walking in Freedom', 'desc'=>'A 30-Day Galatians Devotional & Small Grou...', 'qty'=>2, 'price'=>'$61.49'],
        ['title'=>'Walking in Freedom', 'desc'=>'A 30-Day Galatians Devotional & Small Grou...', 'qty'=>1, 'price'=>'$61.49'],
        ['title'=>'Walking in Freedom', 'desc'=>'A 30-Day Galatians Devotional & Small Grou...', 'qty'=>1, 'price'=>'$61.49']
    ];
    ?>
    <div class="tfp-checkout-wrap">
        <div class="tfp-checkout-main">
            <div class="tfp-checkout-tabs" role="tablist">
                <a href="#" class="tfp-checkout-tab is-active">Cart</a>
                <a href="#" class="tfp-checkout-tab">Contact & Shipping</a>
                <a href="#" class="tfp-checkout-tab">Delivery</a>
                <a href="#" class="tfp-checkout-tab">Payment</a>
            </div>

            <!-- CART -->
            <div class="tfp-checkout-panel">
                <div class="tfp-checkout-step-label">Your Cart</div>
                <h2 class="tfp-checkout-step-title">Confirm the contents of your shopping cart. All Sales are Final.</h2>
                <?php foreach ($cart_items as $item) : ?>
                    <div class="tfp-checkout-product-row">
                        <div style="width:80px;height:80px;background:#EFEFEF;border-radius:8px;flex-shrink:0;"></div>
                        <div style="flex:1;">
                            <h4 style="font-weight:700;margin:0 0 4px;font-size:15px;">Review Your Cart, Edit Your Order</h4>
                            <p style="font-size:13px;color:#2E2E2E;margin:0;"><?php echo esc_html($item['desc']); ?></p>
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:13px;color:#570506;">-</span>
                            <span style="font-size:15px;font-weight:700;">0</span>
                            <span style="font-size:13px;color:#570506;">+</span>
                        </div>
                        <strong style="font-size:15px;min-width:60px;text-align:right;"><?php echo esc_html($item['price']); ?></strong>
                        <a href="#" style="font-size:12px;color:#570506;text-decoration:none;">Remove</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CONTACT -->
            <div class="tfp-checkout-panel">
                <div class="tfp-checkout-step-label">Step 1 of 3</div>
                <h2 class="tfp-checkout-step-title">Contact Information</h2>
                <p style="font-size:15px;color:#2E2E2E;margin-bottom:22px;">We'll use this to send your order confirmation and shipping updates.</p>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>First Name</label><input type="text" value="Jane"></div>
                    <div class="tfp-checkout-form-field"><label>Last Name</label><input type="text" value="Doe"></div>
                </div>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>Email Address</label><input type="email" value="jane@example.com"></div>
                </div>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>Phone Number</label><input type="tel" value="+1 (555) 000-0000"></div>
                </div>
                <h3 style="font-size:18px;font-weight:700;margin:20px 0 12px;">Shipping Address</h3>
                <p style="font-size:14px;color:#2E2E2E;margin-bottom:16px;">Where Should We Ship?</p>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>Street Address</label><input type="text" value="123 Main Street"></div>
                </div>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>Apt / Suite</label><input type="text" value="Apt 4A"></div>
                </div>
                <div class="tfp-checkout-form-row">
                    <div class="tfp-checkout-form-field"><label>City</label><input type="text" value="Las Vegas"></div>
                    <div class="tfp-checkout-form-field"><label>State</label><input type="text" value="NV"></div>
                    <div class="tfp-checkout-form-field"><label>Zip Code</label><input type="text" value="86790"></div>
                </div>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--primary" style="margin-top:24px;">Continue to Delivery</a>
            </div>

            <!-- DELIVERY -->
            <div class="tfp-checkout-panel">
                <div class="tfp-checkout-step-label">Step 2 of 3</div>
                <h2 class="tfp-checkout-step-title">Delivery</h2>
                <p style="font-size:15px;color:#2E2E2E;margin-bottom:22px;">Choose how you'd like your order shipped.</p>
                <div class="tfp-checkout-delivery-option is-active">
                    <h4>Standard Shipping <span style="float:right;font-weight:700;">$4.99</span></h4>
                    <p>5-8 business days after production</p>
                </div>
                <div class="tfp-checkout-delivery-option">
                    <h4>Expedited Shipping <span style="float:right;font-weight:700;">$14.99</span></h4>
                    <p>2-4 business days after production</p>
                </div>
                <div class="tfp-checkout-delivery-option">
                    <h4>Priority Shipping <span style="float:right;font-weight:700;">$24.99</span></h4>
                    <p>1-2 business days after production</p>
                </div>
                <p style="font-size:13px;color:#2E2E2E;background:#EFEFEF;padding:14px;border-radius:8px;margin-top:20px;">Books and Merch are printed on demand. Final Sale. Please allow <strong>3-5 business days</strong> for production before your order ships.</p>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--outline" style="margin-top:20px;">Back to Contact</a>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--primary" style="margin-top:12px;">Continue to Payment</a>
            </div>

            <!-- PAYMENT -->
            <div class="tfp-checkout-panel">
                <div class="tfp-checkout-step-label">Step 2 of 3</div>
                <h2 class="tfp-checkout-step-title">Payment</h2>
                <p style="font-size:15px;color:#2E2E2E;margin-bottom:22px;">Final Sale. Please allow 3-5 business days for production.</p>
                <h4 style="font-size:15px;font-weight:700;margin-bottom:16px;">Choose a Payment Method</h4>
                <div class="tfp-checkout-payment-methods">
                    <div class="tfp-checkout-payment-card is-active">
                        <h4>Credit/Debit</h4>
                        <p>Visa, Mastercard, Amex</p>
                    </div>
                    <div class="tfp-checkout-payment-card">
                        <h4>PayPal</h4>
                        <p>Complete with PayPal</p>
                    </div>
                    <div class="tfp-checkout-payment-card">
                        <h4>Apple Pay</h4>
                        <p>Pay with Apple Pay</p>
                    </div>
                    <div class="tfp-checkout-payment-card">
                        <h4>Google Pay</h4>
                        <p>Pay with Google Pay</p>
                    </div>
                </div>
                <div class="tfp-checkout-payment-form is-active">
                    <h4 style="font-size:16px;font-weight:700;margin-bottom:14px;">Complete with PayPal</h4>
                    <p style="font-size:14px;color:#2E2E2E;margin-bottom:16px;">Complete your purchase securely using your saved Google Pay payment method. Your order will be confirmed once payment is approved.</p>
                    <a href="#" class="tfp-dash-btn tfp-dash-btn--primary" style="width:100%;">Pay with Google Pay</a>
                </div>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--outline" style="margin-top:24px;">Back to Delivery</a>
                <a href="#" class="tfp-dash-btn tfp-dash-btn--primary" style="margin-top:12px;">Place Order</a>
            </div>
        </div>
        <div class="tfp-checkout-sidebar">
            <div class="tfp-checkout-panel">
                <h3>Order Summary</h3>
                <?php foreach ($cart_items as $item) : ?>
                    <div class="tfp-checkout-product-row" style="padding:12px 0;">
                        <div style="width:60px;height:60px;background:#EFEFEF;border-radius:6px;flex-shrink:0;"></div>
                        <div style="flex:1;">
                            <h4 style="font-size:14px;font-weight:700;margin:0 0 2px;"><?php echo esc_html($item['title']); ?></h4>
                            <p style="font-size:12px;color:#2E2E2E;margin:0;">Physical Book · Qty: <?php echo esc_html($item['qty']); ?></p>
                        </div>
                        <strong style="font-size:15px;min-width:50px;text-align:right;"><?php echo esc_html($item['price']); ?></strong>
                    </div>
                <?php endforeach; ?>
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
}
