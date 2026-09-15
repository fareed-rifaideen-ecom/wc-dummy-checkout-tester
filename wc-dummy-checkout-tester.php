<?php
/**
 * Plugin Name: WooCommerce Dummy Checkout Tester
 * Description: A lightweight test gateway to safely simulate checkout scenarios (Success, Decline, Hold, Error).
 * Version: 1.0.0
 * Author: Fareed M. Rifaideen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'init_wc_dummy_checkout_tester' );

function init_wc_dummy_checkout_tester() {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    class WC_Gateway_Dummy_Tester extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'dummy_tester';
            $this->icon               = '';
            $this->has_fields         = true;
            $this->method_title       = 'Dummy Checkout Tester';
            $this->method_description = 'Simulate test payments safely without real transactions.';

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option( 'title', 'Test Credit Card' );
            $this->description = $this->get_option( 'description', 'Simulate payments using test cards.' );
            $this->enabled     = $this->get_option( 'enabled' );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Dummy Checkout Tester',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'Title displayed to customers during checkout.',
                    'default'     => 'Test Credit Card',
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'Instructions displayed below the payment option.',
                    'default'     => 'Starts with: 1000 (Success), 2000 (Decline), 3000 (Hold), or 4000 (Error).',
                ),
            );
        }

        public function payment_fields() {
            if ( $this->description ) {
                echo '<p>' . esc_html( $this->description ) . '</p>';
            }
            ?>
            <fieldset id="wc-dummy-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
                <div class="form-row form-row-wide">
                    <label for="dummy_card_num">Card Number <span class="required">*</span></label>
                    <input id="dummy_card_num" name="dummy_card_num" class="input-text" type="text" maxlength="19" placeholder="1000 0000 0000 0000" autocomplete="off" />
                </div>
                <div class="form-row form-row-first">
                    <label for="dummy_expiry">Expiry (MM/YY) <span class="required">*</span></label>
                    <input id="dummy_expiry" name="dummy_expiry" class="input-text" type="text" placeholder="12/28" maxlength="5" autocomplete="off" />
                </div>
                <div class="form-row form-row-last">
                    <label for="dummy_cvc">Card Code (CVC) <span class="required">*</span></label>
                    <input id="dummy_cvc" name="dummy_cvc" class="input-text" type="password" placeholder="123" maxlength="4" autocomplete="off" />
                </div>
                <div class="clear"></div>
            </fieldset>
            <?php
        }

        public function validate_fields() {
            if ( empty( $_POST['dummy_card_num'] ) ) {
                wc_add_notice( 'Please enter a test card number.', 'error' );
                return false;
            }
            return true;
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            $raw_card = isset( $_POST['dummy_card_num'] ) ? sanitize_text_field( wp_unslash( $_POST['dummy_card_num'] ) ) : '';
            $card = preg_replace( '/\D/', '', $raw_card );

            // Scenario 2: Card Declined
            if ( strpos( $card, '2000' ) === 0 ) {
                wc_add_notice( 'Payment failed: Your card was declined (Insufficient funds).', 'error' );
                return array( 'result' => 'fail' );
            }

            // Scenario 3: Fraud Alert / Manual Verification (On-Hold)
            if ( strpos( $card, '3000' ) === 0 ) {
                $order->update_status( 'on-hold', 'Order held for manual review via Dummy Tester.' );
                WC()->cart->empty_cart();
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                );
            }

            // Scenario 4: Gateway Server Timeout
            if ( strpos( $card, '4000' ) === 0 ) {
                wc_add_notice( 'Gateway Error: Connection timed out. Please try again.', 'error' );
                return array( 'result' => 'fail' );
            }

            // Scenario 1: Approved (1000 or any other number)
            $order->payment_complete();
            $order->add_order_note( 'Transaction approved via Dummy Checkout Tester.' );
            WC()->cart->empty_cart();

            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }
    }
}

add_filter( 'woocommerce_payment_gateways', 'register_wc_dummy_checkout_tester' );
function register_wc_dummy_checkout_tester( $gateways ) {
    $gateways[] = 'WC_Gateway_Dummy_Tester';
    return $gateways;
}

// Inject custom CSS to match Flatsome's checkout styling
add_action( 'wp_head', 'wc_dummy_tester_flatsome_css' );
function wc_dummy_tester_flatsome_css() {
    if ( function_exists( 'is_checkout' ) && is_checkout() ) {
        ?>
        <style>
            #wc-dummy-form {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
            }
            #wc-dummy-form .form-row {
                margin-bottom: 1.2em;
            }
            #wc-dummy-form label {
                display: block;
                font-weight: 700;
                margin-bottom: 0.4em;
                font-size: 0.9em;
                color: #333;
                text-transform: uppercase;
            }
            #wc-dummy-form input.input-text {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #ddd;
                padding: 0 0.75em;
                height: 2.5em;
                border-radius: 0; 
                background-color: #fff;
                box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
                transition: border-color 0.3s, box-shadow 0.3s;
                font-size: 1em;
            }
            #wc-dummy-form input.input-text:focus {
                border-color: #000;
                box-shadow: none;
                outline: none;
            }
            #wc-dummy-form::after {
                content: "";
                display: table;
                clear: both;
            }
        </style>
        <?php
    }
}
