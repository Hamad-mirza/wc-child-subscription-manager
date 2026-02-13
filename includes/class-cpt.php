<?php
/**
 * WC Child Subscription Manager CPT Class
 *
 * Handles the custom post type for children and related meta fields.
 *
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_Child_Subscription_Manager_CPT
 */
class WC_Child_Subscription_Manager_CPT {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager_CPT
     */
    private static $instance = null;

    /**
     * Get the class instance.
     *
     * @return WC_Child_Subscription_Manager_CPT
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
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_meta_fields'));
        add_action('save_post', array($this, 'save_meta_fields'), 10, 3);
        add_filter('manage_wc_child_posts_columns', array($this, 'columns'));
        add_filter('manage_wc_child_posts_custom_column', array($this, 'column_content'), 10, 3);
    }

    /**
     * Register the custom post type.
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __('Children', 'wc-child-subscription-manager'),
            'singular_name'         => __('Child', 'wc-child-subscription-manager'),
            'menu_name'              => __('Children', 'wc-child-subscription-manager'),
            'parent_item_colon'      => __('Parent Child:', 'wc-child-subscription-manager'),
            'all_items'              => __('All Children', 'wc-child-subscription-manager'),
            'view_item'              => __('View Child', 'wc-child-subscription-manager'),
            'add_new_item'           => __('Add New Child', 'wc-child-subscription-manager'),
            'add_new'                => __('Add New', 'wc-child-subscription-manager'),
            'edit_item'              => __('Edit Child', 'wc-child-subscription-manager'),
            'update_item'            => __('Update Child', 'wc-child-subscription-manager'),
            'search_items'           => __('Search Children', 'wc-child-subscription-manager'),
            'not_found'              => __('Not found', 'wc-child-subscription-manager'),
            'not_found_in_trash'     => __('Not found in Trash', 'wc-child-subscription-manager'),
        );

        $args = array(
            'label'                 => __('Children', 'wc-child-subscription-manager'),
            'description'           => __('Children for subscription management', 'wc-child-subscription-manager'),
            'labels'                => $labels,
            'supports'              => array('title', 'thumbnail'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'show_in_admin_bar'     => true,
            'show_in_rest'          => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'map_meta_cap'          => true,
        );

        register_post_type('wc_child', $args);
    }

    /**
     * Register meta fields.
     */
    public function register_meta_fields() {
        // Parent User ID
        register_meta(
            'post',
            '_parent_user_id',
            array(
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'default_value'     => 0,
                'type'             => 'integer',
            )
        );

        // Date of Birth
        register_meta(
            'post',
            '_dob',
            array(
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'default_value'     => '',
                'type'             => 'string',
            )
        );

        // Gender
        register_meta(
            'post',
            '_gender',
            array(
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'default_value'     => '',
                'type'             => 'string',
            )
        );

        // Age
        register_meta(
            'post',
            '_age',
            array(
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'default_value'     => 0,
                'type'             => 'integer',
            )
        );

        // Club
        register_meta(
            'post',
            '_club',
            array(
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'default_value'     => '',
                'type'             => 'string',
            )
        );
    }

    /**
     * Save meta fields.
     *
     * @param int $post_id The post ID.
     * @param int $post The post object.
     * @param bool $update Whether this is an update.
     * @return null
     */
    public function save_meta_fields($post_id, $post, $update) {
        // Check if our post type
        if ('wc_child' !== $post->post_type) {
            return;
        }

        // Check permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['wc_child_manager_nonce']) || !wp_verify_nonce($_POST['wc_child_manager_nonce'], 'wc_child_manager')) {
            return;
        }

        // Save fields
        $fields = array(
            '_parent_user_id' => 'absint',
            '_dob'           => 'sanitize_text_field',
            '_gender'        => 'sanitize_text_field',
            '_age'           => 'absint',
            '_club'          => 'sanitize_text_field',
        );

        foreach ($fields as $key => $sanitize) {
            if (isset($_POST[$key])) {
                $value = call_user_func($sanitize, $_POST[$key]);
                update_post_meta($post_id, $key, $value);
            } else {
                delete_post_meta($post_id, $key);
            }
        }
    }

    /**
     * Columns for the CPT list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function columns($columns) {
        $new_columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __('Name', 'wc-child-subscription-manager'),
            'parent'    => __('Parent', 'wc-child-subscription-manager'),
            'dob'       => __('DOB', 'wc-child-subscription-manager'),
            'gender'    => __('Gender', 'wc-child-subscription-manager'),
            'age'       => __('Age', 'wc-child-subscription-manager'),
            'club'      => __('Club', 'wc-child-subscription-manager'),
            'date'      => __('Date', 'wc-child-subscription-manager'),
        );

        return $new_columns;
    }

    /**
     * Content for custom columns.
     *
     * @param string $column Column name.
     * @param int $post_id Post ID.
     */
    public function column_content($column, $post_id) {
        global $post;

        switch ($column) {
            case 'parent':
                $parent_id = get_post_meta($post_id, '_parent_user_id', true);
                $parent    = get_user_by('id', $parent_id);
                if ($parent) {
                    echo esc_html($parent->display_name);
                } else {
                    echo __('Unknown', 'wc-child-subscription-manager');
                }
                break;

            case 'dob':
                $dob = get_post_meta($post_id, '_dob', true);
                echo esc_html($dob);
                break;

            case 'gender':
                $gender = get_post_meta($post_id, '_gender', true);
                echo esc_html($gender);
                break;

            case 'age':
                $age = get_post_meta($post_id, '_age', true);
                echo esc_html($age);
                break;

            case 'club':
                $club = get_post_meta($post_id, '_club', true);
                echo esc_html($club);
                break;
        }
    }
}
