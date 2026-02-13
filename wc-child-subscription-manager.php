<?php
/**
 * Plugin Name:       WC Child Subscription Manager
 * Plugin URI:        https://github.com/Hamad-mirza/wc-child-subscription-manager
 * Description:       Allows parents to manage children and link subscriptions to them in WooCommerce.
 * Version:           1.0.2
 * Author:            Hamad Mirza
 * Author URI:        https://mrhammad.com
 * Text Domain:       wc-child-subscription-manager
 * Domain Path:       /languages
 * GitHub Plugin URI: Hamad-mirza/wc-child-subscription-manager
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 *hmm
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_CHILD_SUBSCRIPTION_MANAGER_VERSION', '1.0.0');
define('WC_CHILD_SUBSCRIPTION_MANAGER_FILE', __FILE__);
define('WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR . 'includes/class-cpt.php';
require_once WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR . 'includes/class-frontend.php';
require_once WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR . 'includes/class-checkout.php';
require_once WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR . 'includes/class-subscription.php';
require_once WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * The main plugin class.
 */
class WC_Child_Subscription_Manager {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager
     */
    private static $instance = null;

    /**
     * Get the plugin instance.
     *
     * @return WC_Child_Subscription_Manager
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
        // Initialize components
        add_action('plugins_loaded', array($this, 'init_components'));
        
        // Check requirements
        add_action('admin_notices', array($this, 'check_requirements'));
    }

    /**
     * Initialize plugin components.
     */
    public function init_components() {
        // Initialize CPT
        WC_Child_Subscription_Manager_CPT::get_instance();

        // Initialize Frontend
        WC_Child_Subscription_Manager_Frontend::get_instance();

        // Initialize Checkout
        WC_Child_Subscription_Manager_Checkout::get_instance();

        // Initialize Subscription
        WC_Child_Subscription_Manager_Subscription::get_instance();

        // Initialize Admin
        WC_Child_Subscription_Manager_Admin::get_instance();
        
        // Create default pages
        add_action('admin_init', array($this, 'create_default_pages'));
    }
    
    /**
     * Check plugin requirements
     */
    public function check_requirements() {
        // Check if WooCommerce is active
        $woocommerce_active = file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php');
        
        // Check if WooCommerce Subscriptions is active
        $subscriptions_active = file_exists(WP_PLUGIN_DIR . '/woocommerce-subscriptions/woocommerce-subscriptions.php');
        
        if (!$woocommerce_active) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('WC Child Subscription Manager requires <a href="%s">WooCommerce</a> to be installed and activated.', 'wc-child-subscription-manager'),
                admin_url('plugin-install.php?tab=search&type=plugin&s=woocommerce')
            );
            echo '</p></div>';
        }
        
        if (!$subscriptions_active) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('WC Child Subscription Manager requires <a href="%s">WooCommerce Subscriptions</a> to be installed and activated.', 'wc-child-subscription-manager'),
                admin_url('plugin-install.php?tab=search&type=plugin&s=woocommerce+subscriptions')
            );
            echo '</p></div>';
        }
    }
    
    /**
     * Create default pages for the plugin
     */
    public function create_default_pages() {
        // Manage Children page
        $manage_children_page = get_page_by_title('Manage Children');
        
        if (!$manage_children_page) {
            wp_insert_post(array(
                'post_title'    => 'Manage Children',
                'post_name'     => 'manage-children',
                'post_type'     => 'page',
                'post_status'   => 'publish',
                'post_content'  => '[wc_my_children]',
                'comment_status' => 'closed',
                'ping_status'    => 'closed'
            ));
        }
        
        // Flush rewrite rules to make the new page accessible
        flush_rewrite_rules();
    }

    /**
     * Activation hook.
     */
    public static function activate() {
        // Flush rewrite rules for CPT
        flush_rewrite_rules();
        
        // Create default pages
        $instance = self::get_instance();
        $instance->create_default_pages();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
    }
}

// Register activation and deactivation hooks
register_activation_hook(WC_CHILD_SUBSCRIPTION_MANAGER_FILE, array('WC_Child_Subscription_Manager', 'activate'));
register_deactivation_hook(WC_CHILD_SUBSCRIPTION_MANAGER_FILE, array('WC_Child_Subscription_Manager', 'deactivate'));

// Initialize the plugin
WC_Child_Subscription_Manager::get_instance();
