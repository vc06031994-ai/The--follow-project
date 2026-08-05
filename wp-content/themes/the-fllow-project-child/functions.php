<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function the_fllow_project_child_enqueue_styles() {

	wp_enqueue_style(
		'hello-elementor-theme-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( 'hello-elementor' )->get( 'Version' )
	);

	wp_enqueue_style(
		'the-fllow-project-child-style',
		get_stylesheet_uri(),
		array( 'hello-elementor-theme-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'the_fllow_project_child_enqueue_styles' );

/**
 * Buy Now Button Shortcode
 * Usage: [buy_now_button]
 * 
 * - Single product page pe shortcode rakho
 * - Click karne pe product cart me add hota hai aur checkout pe redirect hota hai
 * - Simple aur Variable dono products support karta hai
 */

// ─── 1. Shortcode Register ───────────────────────────────────────────────────

add_shortcode( 'buy_now_button', 'tfp_buy_now_button_shortcode' );

function tfp_buy_now_button_shortcode( $atts ) {
    // Sirf product page pe render karo
    if ( ! is_product() ) return '';

    global $product;

    if ( ! $product ) {
        $product = wc_get_product( get_the_ID() );
    }

    if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
        return '';
    }

    $product_id   = $product->get_id();
    $product_type = $product->get_type(); // simple / variable / grouped etc.

    ob_start();
    ?>
    <div class="tfp-buy-now-wrap">
        <?php if ( $product_type === 'variable' ) : ?>
            <?php /* Variable product: JS se selected variation ID pick karega */ ?>
            <button
                type="button"
                id="tfp-buy-now-btn"
                class="tfp-buy-now-btn"
                data-product-id="<?php echo esc_attr( $product_id ); ?>"
                data-product-type="variable"
            >
                Buy Now
            </button>
        <?php else : ?>
            <?php /* Simple product: direct URL approach — cleanest */ ?>
            <a
                href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => $product_id, 'tfp_buy_now' => '1' ), home_url('/') ) ); ?>"
                class="tfp-buy-now-btn"
                data-product-id="<?php echo esc_attr( $product_id ); ?>"
                data-product-type="simple"
            >
                Buy Now
            </a>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}


// ─── 2. Simple Product: URL click pe checkout redirect ───────────────────────

add_filter( 'woocommerce_add_to_cart_redirect', 'tfp_buy_now_redirect', 10, 2 );

function tfp_buy_now_redirect( $url, $product ) {
    if ( isset( $_GET['tfp_buy_now'] ) && $_GET['tfp_buy_now'] === '1' ) {
        
        $product_id = absint( $_GET['add-to-cart'] );
        
        // Cart me same product already hai to uski quantity 1 karo
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( $cart_item['product_id'] === $product_id ) {
                WC()->cart->set_quantity( $cart_item_key, 1 );
                break;
            }
        }
        
        return wc_get_checkout_url();
    }
    return $url;
}


// ─── 3. Variable Product: AJAX handler ───────────────────────────────────────

add_action( 'wp_ajax_tfp_buy_now_variable', 'tfp_buy_now_variable_handler' );
add_action( 'wp_ajax_nopriv_tfp_buy_now_variable', 'tfp_buy_now_variable_handler' );

function tfp_buy_now_variable_handler() {
    check_ajax_referer( 'tfp_buy_now_nonce', 'nonce' );

    $product_id   = isset( $_POST['product_id'] )   ? absint( $_POST['product_id'] )   : 0;
    $variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
    $quantity     = isset( $_POST['quantity'] )     ? absint( $_POST['quantity'] )     : 1;

    if ( ! $product_id ) {
        wp_send_json_error( array( 'message' => 'Invalid product.' ) );
    }

    // Variation ID nahi mila to product_id hi use karo
    $cart_item_id = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );

    if ( $cart_item_id ) {
        wp_send_json_success( array( 'redirect' => wc_get_checkout_url() ) );
    } else {
        wp_send_json_error( array( 'message' => 'Could not add to cart.' ) );
    }
}


// ─── 4. JS + CSS Enqueue (sirf product pages pe) ─────────────────────────────

add_action( 'wp_enqueue_scripts', 'tfp_buy_now_assets' );

function tfp_buy_now_assets() {
    if ( ! is_product() ) return;

    // Inline JS
    $js = "
    (function($){
        // Variable product: button click
        $(document).on('click', '#tfp-buy-now-btn[data-product-type=\"variable\"]', function(e) {
            e.preventDefault();

            var \$btn        = $(this);
            var productId   = \$btn.data('product-id');
            var variationId = $('input[name=\"variation_id\"]').val() || 0;
            var quantity    = $('input.qty').val() || 1;

            if ( ! variationId || variationId == 0 ) {
                alert('Please select product options before purchasing.');
                return;
            }

            \$btn.prop('disabled', true).text('Please wait...');

            $.ajax({
                url: '" . esc_js( admin_url('admin-ajax.php') ) . "',
                type: 'POST',
                data: {
                    action:       'tfp_buy_now_variable',
                    nonce:        '" . esc_js( wp_create_nonce('tfp_buy_now_nonce') ) . "',
                    product_id:   productId,
                    variation_id: variationId,
                    quantity:     quantity
                },
                success: function(response) {
                    if ( response.success ) {
                        window.location.href = response.data.redirect;
                    } else {
                        alert('Something went wrong. Please try again.');
                        \$btn.prop('disabled', false).text('Buy Now');
                    }
                },
                error: function() {
                    alert('Something went wrong. Please try again.');
                    \$btn.prop('disabled', false).text('Buy Now');
                }
            });
        });

        // Simple product anchor: already handles via URL redirect (no JS needed)
        // But we add loading state for better UX
        $(document).on('click', '.tfp-buy-now-btn[data-product-type=\"simple\"]', function() {
            $(this).addClass('tfp-loading').text('Please wait...');
        });

    })(jQuery);
    ";

    // Inline CSS — matching screenshot exactly
    $css = "
    .tfp-buy-now-wrap {
        display: inline-block;
        width: 100%;
    }
    .tfp-buy-now-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        background-color: #00666E;
        color: #ffffff !important;
        font-family: 'Eudoxus Sans', sans-serif;
        font-size: 18px;
        font-weight: 500;
        line-height: 130%;
        letter-spacing: 0;
        text-align: center;
        text-decoration: none !important;
        border: 1px solid #00666E;
        border-radius: 10px;
        padding: 12px 35px;
        cursor: pointer;
        transition: background-color 0.2s ease, opacity 0.2s ease;
        box-sizing: border-box;
    }
    .tfp-buy-now-btn:hover {
        background-color: #ffffff;
        color: #00666E !important;
		border-color: #00666E !important;
    }
    .tfp-buy-now-btn:disabled,
    .tfp-buy-now-btn.tfp-loading {
        opacity: 0.7;
        cursor: not-allowed;
    }
    ";

    wp_add_inline_script( 'jquery', $js );
    wp_add_inline_style( 'woocommerce-general', $css );
}

function curriculum_category_shortcode() {

    $product = wc_get_product( get_the_ID() );

    if ( ! $product ) {
        return '';
    }

    $terms = wc_get_product_terms(
        $product->get_id(),
        'pa_curriculum-category',
        array(
            'fields' => 'names',
        )
    );

    if ( empty( $terms ) ) {
        return '';
    }

    return '<p class="curriculum-category">' . esc_html( implode( ', ', $terms ) ) . '</p>';
}
add_shortcode( 'curriculum_category', 'curriculum_category_shortcode' );
