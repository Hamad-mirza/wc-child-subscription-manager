<?php
/**
 * WC Child Subscription Manager Admin Class
 *
 * Handles admin enhancements and column management.
 *
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_Child_Subscription_Manager_Admin
 */
class WC_Child_Subscription_Manager_Admin {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager_Admin
     */
    private static $instance = null;

    /**
     * Get the class instance.
     *
     * @return WC_Child_Subscription_Manager_Admin
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
        add_filter('manage_wc_child_posts_columns', array($this, 'add_columns'));
        add_filter('manage_wc_child_posts_custom_column', array($this, 'column_content'), 10, 3);
        add_filter('manage_shop_subscription_posts_columns', array($this, 'add_subscription_columns'));
        add_filter('manage_shop_subscription_posts_custom_column', array($this, 'subscription_column_content'), 10, 3);
    }

    /**
     * Add columns to WC Child CPT.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_columns($columns) {
        $new_columns = array(
            'parent_name' => __('Parent Name', 'wc-child-subscription-manager'),
            'parent_email' => __('Parent Email', 'wc-child-subscription-manager'),
        );

        return array_merge($columns, $new_columns);
    }

    /**
     * Content for custom columns in WC Child CPT.
     *
     * @param string $column Column name.
     * @param int $post_id Post ID.
     */
    public function column_content($column, $post_id) {
        switch ($column) {
            case 'parent_name':
            case 'parent_email':
                $parent_id = get_post_meta($post_id, '_parent_user_id', true);
                $parent    = get_user_by('id', $parent_id);
                
                if ($parent) {
                    $value = $column === 'parent_name' ? $parent->display_name : $parent->user_email;
                    echo esc_html($value);
                } else {
                    echo __('Unknown', 'wc-child-subscription-manager');
                }
                break;
        }
    }

    /**
     * Add columns to Shop Subscription post type.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_subscription_columns($columns) {
        $new_columns = array(
            'assigned_child' => __('Assigned Child', 'wc-child-subscription-manager'),
        );

        // Insert after "Customer" column
        $new_columns = array_merge(
            array_slice($columns, 0, 3),
            $new_columns,
            array_slice($columns, 3)
        );

        return $new_columns;
    }

    /**
     * Content for custom columns in Shop Subscription post type.
     *
     * @param string $column Column name.
     * @param int $post_id Post ID.
     */
    public function subscription_column_content($column, $post_id) {
        if ('assigned_child' === $column) {
            $child_id = get_post_meta($post_id, '_child_id', true);
            if ($child_id) {
                $child = get_post($child_id);
                if ($child) {
                    echo '<a href="' . esc_url(get_edit_post_link($child_id)) . '">' . esc_html($child->post_title) . '</a>';
                } else {
                    echo __('Child not found', 'wc-child-subscription-manager');
                }
            } else {
                echo __('Not assigned', 'wc-child-subscription-manager');
            }
        }
    }
}
