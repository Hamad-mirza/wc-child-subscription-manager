<?php
/**
 * WC Child Subscription Manager Checkout Class
 *
 * Handles checkout integration for linking subscriptions to children.
 *
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_Child_Subscription_Manager_Checkout
 */
class WC_Child_Subscription_Manager_Checkout {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager_Checkout
     */
    private static $instance = null;

    /**
     * Get the class instance.
     *
     * @return WC_Child_Subscription_Manager_Checkout
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Register hooks
        add_filter('woocommerce_checkout_fields', array($this, 'add_child_dropdown'));
        add_action('woocommerce_checkout_process', array($this, 'validate_child_selection'));
        add_action('woocommerce_checkout_create_order', array($this, 'save_child_selection'));
    }

    /**
     * Add child selection dropdown to checkout fields.
     *
     * @param array $fields Existing checkout fields.
     * @return array Modified fields.
     */
    public function add_child_dropdown($fields) {
        error_log('WC Child Subscription Manager: add_child_dropdown method called');
        
        $user_id = get_current_user_id();
        error_log('WC Child Subscription Manager: Current user ID: ' . $user_id);
        
        $children = $this->get_children_for_user($user_id);
        error_log('WC Child Subscription Manager: User ID ' . $user_id . ' has ' . count($children) . ' children');

        // Only show dropdown if user has children AND there are subscription products in cart
        if (!empty($children) && $this->has_subscription_products()) {
            // Add the child dropdown to the billing section instead of order to make it more prominent
            $fields['billing']['child_id'] = array(
                'type'        => 'select',
                'meta_key'    => 'child_id',
                'label'       => __('Select Child', 'wc-child-subscription-manager'),
                'class'       => array('form-row-wide wc-child-subscription-dropdown'),
                'required'    => true,
                'options'     => array('' => __('Select a child', 'wc-child-subscription-manager')) + $this->get_child_options($children),
            );
            
            // Debug logging
            error_log('WC Child Subscription Manager: Added child dropdown to billing section');
        } else {
            // Debug logging
            if (empty($children)) {
                error_log('WC Child Subscription Manager: No children found for user ' . $user_id);
            }
            if (!$this->has_subscription_products()) {
                error_log('WC Child Subscription Manager: No subscription products found in cart for user ' . $user_id);
            }
        }

        return $fields;
    }

    /**
     * Render child selection dropdown after order notes.
     */
    public function render_child_selection() {
        // This method is no longer needed as we're using WooCommerce's built-in field system
    }

    /**
     * Validate child selection during checkout.
     */
    public function validate_child_selection() {
        $user_id = get_current_user_id();
        $children = $this->get_children_for_user($user_id);

        // Only validate if there are subscription products in the cart and user has children
        if ($this->has_subscription_products() && !empty($children) && empty($_POST['child_id'])) {
            // Add error notice if no child is selected
            wc_add_notice(__('Please select a child for your subscription.', 'wc-child-subscription-manager'), 'error');
        }
        
        // Debug logging
        if ($this->has_subscription_products()) {
            error_log('WC Child Subscription Manager: Validating child selection for user ' . $user_id);
            if (!empty($_POST['child_id'])) {
                error_log('WC Child Subscription Manager: Child ID ' . $_POST['child_id'] . ' selected');
            }
        }
    }

    /**
     * Save child selection to order meta.
     *
     * @param object $order WooCommerce order object.
     */
    public function save_child_selection($order) {
        if ($this->has_subscription_products()) {
            $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
            if ($child_id) {
                $order->update_meta_data('_child_id', $child_id);
                $order->update_meta_data('_child_name', get_post($child_id)->post_title);
            }
        }
    }

    /**
     * Check if cart contains subscription products.
     *
     * @return bool True if cart contains subscription products.
     */
    private function has_subscription_products() {
        error_log('WC Child Subscription Manager: Checking for subscription products in cart');
        
        if (function_exists('wcs_is_subscription') && function_exists('WC')) {
            $cart_contents = WC()->cart->get_cart_contents();
            error_log('WC Child Subscription Manager: Found ' . count($cart_contents) . ' items in cart');
            
            foreach ($cart_contents as $cart_item) {
                // Check both product ID and variation ID
                $product_id = $cart_item['product_id'];
                $variation_id = isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0;
                
                // Check product
                $product = wc_get_product($product_id);
                if ($product && wcs_is_subscription($product)) {
                    error_log('WC Child Subscription Manager: Found subscription product: ' . $product_id);
                    return true;
                }
                
                // Check variation if exists
                if ($variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation && wcs_is_subscription($variation)) {
                        error_log('WC Child Subscription Manager: Found subscription variation: ' . $variation_id);
                        return true;
                    }
                }
            }
        }
        
        error_log('WC Child Subscription Manager: No subscription products found in cart');
        return false;
    }

    /**
     * Get children for a specific user.
     *
     * @param int $user_id User ID.
     * @return array Array of child posts.
     */
    private function get_children_for_user($user_id) {
        $args = array(
            'post_type'      => 'wc_child',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_parent_user_id',
                    'value'   => $user_id,
                    'compare' => '=',
                ),
            ),
        );

        return get_posts($args);
    }

    /**
     * Get child options for dropdown.
     *
     * @param array $children Array of child posts.
     * @return array Child options for dropdown.
     */
    private function get_child_options($children) {
        $options = array();
        foreach ($children as $child) {
            $options[$child->ID] = esc_html($child->post_title);
        }
        return $options;
    }
}
