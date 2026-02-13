<?php
/**
 * WC Child Subscription Manager Subscription Class
 *
 * Handles subscription meta data storage and management.
 *
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_Child_Subscription_Manager_Subscription
 */
class WC_Child_Subscription_Manager_Subscription {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager_Subscription
     */
    private static $instance = null;

    /**
     * Get the class instance.
     *
     * @return WC_Child_Subscription_Manager_Subscription
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
        add_action('wcs_checkout_subscription_created', array($this, 'save_subscription_meta'), 10, 2);
    }

    /**
     * Save subscription meta data.
     *
     * @param int $subscription_id Subscription ID.
     * @param array $order_data Order data.
     */
    public function save_subscription_meta($subscription_id, $order_data) {
        $order = wc_get_order($order_data['order_id']);

        if (!$order) {
            return;
        }

        $child_id = $order->get_meta('_child_id');
        $child_name = $order->get_meta('_child_name');

        if ($child_id && $child_name) {
            update_post_meta($subscription_id, '_child_id', $child_id);
            update_post_meta($subscription_id, '_child_name', $child_name);
        }
    }
}
