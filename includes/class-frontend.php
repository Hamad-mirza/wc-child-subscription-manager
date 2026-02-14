<?php
/**
 * WC Child Subscription Manager Frontend Class
 *
 * Handles frontend functionality for managing children.
 *
 * @package WC_Child_Subscription_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_Child_Subscription_Manager_Frontend
 */
class WC_Child_Subscription_Manager_Frontend {

    /**
     * Plugin instance.
     *
     * @var WC_Child_Subscription_Manager_Frontend
     */
    private static $instance = null;

    /**
     * Get the class instance.
     *
     * @return WC_Child_Subscription_Manager_Frontend
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
        add_shortcode('wc_my_children', array($this, 'shortcode_my_children'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'handle_actions'));
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_scripts() {
        wp_enqueue_style('wc-child-subscription-manager', WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL . 'assets/css/frontend.css', array(), WC_CHILD_SUBSCRIPTION_MANAGER_VERSION);
        
        wp_enqueue_script('wc-child-subscription-manager', WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WC_CHILD_SUBSCRIPTION_MANAGER_VERSION, true);
        
        wp_localize_script('wc-child-subscription-manager', 'wcChildSubscriptionManager', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_child_subscription_manager_nonce'),
            'i18n' => array(
                'confirm_delete' => __('Are you sure you want to delete this child?', 'wc-child-subscription-manager'),
                'error' => __('An error occurred. Please try again.', 'wc-child-subscription-manager'),
                'success' => __('Action completed successfully.', 'wc-child-subscription-manager'),
            )
        ));
        
        // Also enqueue on checkout page
        if (function_exists('is_checkout') && is_checkout()) {
            wp_enqueue_script('wc-child-subscription-checkout', WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL . 'assets/js/checkout.js', array('jquery'), WC_CHILD_SUBSCRIPTION_MANAGER_VERSION, true);
            
            // Get children data for checkout page
            $user_id = get_current_user_id();
            $children = $this->get_children_for_user($user_id);
            
            // Prepare children data for JavaScript
            $children_data = array();
            foreach ($children as $child) {
                $children_data[] = array(
                    'id' => $child->ID,
                    'name' => $child->post_title,
                    'dob' => get_post_meta($child->ID, '_dob', true),
                    'gender' => get_post_meta($child->ID, '_gender', true),
                    'age' => get_post_meta($child->ID, '_age', true),
                    'club' => get_post_meta($child->ID, '_club', true)
                );
            }
            
            // Localize script with children data
            wp_localize_script('wc-child-subscription-checkout', 'wcChildSubscriptionCheckout', array(
                'children' => $children_data
            ));
        }
    }

    /**
     * Shortcode for displaying children's management interface.
     *
     * @return string Shortcode output.
     */
    public function shortcode_my_children() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to manage your children.', 'wc-child-subscription-manager') . '</p>';
        }

        $user_id = get_current_user_id();
        $children = $this->get_children_for_user($user_id);

        ob_start();
        ?>
        <div class="wc-child-subscription-manager">
            <h2><?php esc_html_e('My Children', 'wc-child-subscription-manager'); ?></h2>
            
            <?php if (empty($children)) : ?>
                <p><?php esc_html_e('You don\'t have any children added yet.', 'wc-child-subscription-manager'); ?></p>
                <?php $this->render_add_child_form(); ?>
            <?php else : ?>
                <div class="children-list">
                    <table>
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Name', 'wc-child-subscription-manager'); ?></th>
                                <th><?php esc_html_e('DOB', 'wc-child-subscription-manager'); ?></th>
                                <th><?php esc_html_e('Gender', 'wc-child-subscription-manager'); ?></th>
                                <th><?php esc_html_e('Age', 'wc-child-subscription-manager'); ?></th>
                                <th><?php esc_html_e('Club', 'wc-child-subscription-manager'); ?></th>
                                <th><?php esc_html_e('Actions', 'wc-child-subscription-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($children as $child) : ?>
                                <tr>
                                    <td><?php echo esc_html($child->post_title); ?></td>
                                    <td><?php echo esc_html(get_post_meta($child->ID, '_dob', true)); ?></td>
                                    <td><?php echo esc_html(get_post_meta($child->ID, '_gender', true)); ?></td>
                                    <td><?php echo esc_html(get_post_meta($child->ID, '_age', true)); ?></td>
                                    <td><?php echo esc_html(get_post_meta($child->ID, '_club', true)); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit_child', 'child_id' => $child->ID), remove_query_arg('paged'))); ?>" class="edit-child"><?php esc_html_e('Edit', 'wc-child-subscription-manager'); ?></a> |
                                        <a href="#" data-child-id="<?php echo esc_attr($child->ID); ?>" class="delete-child"><?php esc_html_e('Delete', 'wc-child-subscription-manager'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php $this->render_add_child_form(); ?>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <h3><?php esc_html_e('How to use this plugin', 'wc-child-subscription-manager'); ?></h3>
                <p><?php esc_html_e('1. Add children using the form above.', 'wc-child-subscription-manager'); ?></p>
                <p><?php esc_html_e('2. Add subscription products to your cart.', 'wc-child-subscription-manager'); ?></p>
                <p><?php esc_html_e('3. During checkout, you\'ll see a dropdown to select which child the subscription should be linked to.', 'wc-child-subscription-manager'); ?></p>
                <p><?php esc_html_e('4. View assigned subscriptions in the WooCommerce Subscriptions section of your account.', 'wc-child-subscription-manager'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get children for a specific user.
     *
     * @param int $user_id User ID.
     * @return array Array of child posts.
     */
    public function get_children_for_user($user_id) {
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
     * Render the add/edit child form.
     *
     * @param int $child_id Optional child ID for editing.
     */
    public function render_add_child_form($child_id = 0) {
        $child_data = array(
            'title'   => '',
            'dob'     => '',
            'gender'  => '',
            'age'     => '',
            'club'    => '',
        );

        if ($child_id) {
            $child = get_post($child_id);
            if ($child && $child->post_author == get_current_user_id()) {
                $child_data['title'] = $child->post_title;
                $child_data['dob'] = get_post_meta($child_id, '_dob', true);
                $child_data['gender'] = get_post_meta($child_id, '_gender', true);
                $child_data['age'] = get_post_meta($child_id, '_age', true);
                $child_data['club'] = get_post_meta($child_id, '_club', true);
            }
        }

        ?>
        <div class="add-child-form">
            <h3><?php esc_html_e('Add/Edit Child', 'wc-child-subscription-manager'); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('wc_child_manager', 'wc_child_manager_nonce'); ?>
                <input type="hidden" name="child_id" value="<?php echo esc_attr($child_id); ?>">
                
                <p>
                    <label for="child_name"><?php esc_html_e('Name', 'wc-child-subscription-manager'); ?></label>
                    <input type="text" id="child_name" name="child_name" value="<?php echo esc_attr($child_data['title']); ?>" required>
                </p>
                
                <p>
                    <label for="dob"><?php esc_html_e('Date of Birth', 'wc-child-subscription-manager'); ?></label>
                    <input type="date" id="dob" name="dob" value="<?php echo esc_attr($child_data['dob']); ?>">
                </p>
                
                <p>
                    <label for="gender"><?php esc_html_e('Gender', 'wc-child-subscription-manager'); ?></label>
                    <select id="gender" name="gender">
                        <option value=""><?php esc_html_e('Select', 'wc-child-subscription-manager'); ?></option>
                        <option value="male" <?php selected($child_data['gender'], 'male'); ?>><?php esc_html_e('Male', 'wc-child-subscription-manager'); ?></option>
                        <option value="female" <?php selected($child_data['gender'], 'female'); ?>><?php esc_html_e('Female', 'wc-child-subscription-manager'); ?></option>
                        <option value="other" <?php selected($child_data['gender'], 'other'); ?>><?php esc_html_e('Other', 'wc-child-subscription-manager'); ?></option>
                    </select>
                </p>
                
                <p>
                    <label for="age"><?php esc_html_e('Age', 'wc-child-subscription-manager'); ?></label>
                    <input type="number" id="age" name="age" value="<?php echo esc_attr($child_data['age']); ?>">
                </p>
                
                <p>
                    <label for="club"><?php esc_html_e('Club', 'wc-child-subscription-manager'); ?></label>
                    <input type="text" id="club" name="club" value="<?php echo esc_attr($child_data['club']); ?>">
                </p>
                
                <p>
                    <?php if ($child_id) : ?>
                        <input type="submit" name="update_child" value="<?php esc_html_e('Update Child', 'wc-child-subscription-manager'); ?>">
                    <?php else : ?>
                        <input type="submit" name="add_child" value="<?php esc_html_e('Add Child', 'wc-child-subscription-manager'); ?>">
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle add/edit/delete actions.
     */
    public function handle_actions() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        
        // Add child
        if (isset($_POST['add_child'])) {
            $this->process_add_child($user_id);
        }
        
        // Update child
        if (isset($_POST['update_child'])) {
            $this->process_update_child($user_id);
        }
        
        // Delete child
        if (isset($_GET['action']) && 'delete_child' === $_GET['action']) {
            $this->process_delete_child($user_id, isset($_GET['child_id']) ? intval($_GET['child_id']) : 0);
        }
        
        // Handle AJAX delete
        if (isset($_POST['action']) && 'wc_child_subscription_manager_delete_child' === $_POST['action']) {
            $this->handle_ajax_delete();
        }
    }

    /**
     * Process add child submission.
     *
     * @param int $user_id User ID.
     */
    private function process_add_child($user_id) {
        // Verify nonce
        if (!isset($_POST['wc_child_manager_nonce']) || !wp_verify_nonce($_POST['wc_child_manager_nonce'], 'wc_child_manager')) {
            wp_die(__('Security check failed.', 'wc-child-subscription-manager'));
        }

        // Sanitize and validate data
        $title = sanitize_text_field($_POST['child_name']);
        $dob = sanitize_text_field($_POST['dob']);
        $gender = sanitize_text_field($_POST['gender']);
        $age = absint($_POST['age']);
        $club = sanitize_text_field($_POST['club']);

        // Create post array
        $post_data = array(
            'post_title'    => $title,
            'post_type'     => 'wc_child',
            'post_status'   => 'publish',
            'post_author'   => $user_id,
        );

        // Insert post
        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            // Save meta fields
            update_post_meta($post_id, '_parent_user_id', $user_id);
            update_post_meta($post_id, '_dob', $dob);
            update_post_meta($post_id, '_gender', $gender);
            update_post_meta($post_id, '_age', $age);
            update_post_meta($post_id, '_club', $club);

            // Redirect to same page with success message
            wp_redirect(add_query_arg('child_added', '1', remove_query_arg(array('action', 'child_id'))));
            exit;
        } else {
            // Handle error
            wp_redirect(add_query_arg('error', '1', remove_query_arg(array('action', 'child_id'))));
            exit;
        }
    }

    /**
     * Process update child submission.
     *
     * @param int $user_id User ID.
     */
    private function process_update_child($user_id) {
        // Verify nonce
        if (!isset($_POST['wc_child_manager_nonce']) || !wp_verify_nonce($_POST['wc_child_manager_nonce'], 'wc_child_manager')) {
            wp_die(__('Security check failed.', 'wc-child-subscription-manager'));
        }

        $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
        if (!$child_id) {
            wp_die(__('Invalid child ID.', 'wc-child-subscription-manager'));
        }

        // Check ownership
        $child = get_post($child_id);
        if (!$child || $child->post_author != $user_id) {
            wp_die(__('You do not have permission to edit this child.', 'wc-child-subscription-manager'));
        }

        // Sanitize and validate data
        $title = sanitize_text_field($_POST['child_name']);
        $dob = sanitize_text_field($_POST['dob']);
        $gender = sanitize_text_field($_POST['gender']);
        $age = absint($_POST['age']);
        $club = sanitize_text_field($_POST['club']);

        // Update post
        $post_data = array(
            'ID'         => $child_id,
            'post_title' => $title,
        );

        $post_id = wp_update_post($post_data, true);

        if ($post_id && !is_wp_error($post_id)) {
            // Update meta fields
            update_post_meta($child_id, '_dob', $dob);
            update_post_meta($child_id, '_gender', $gender);
            update_post_meta($child_id, '_age', $age);
            update_post_meta($child_id, '_club', $club);

            // Redirect to same page with success message
            wp_redirect(add_query_arg('child_updated', '1', remove_query_arg(array('action', 'child_id'))));
            exit;
        } else {
            // Handle error
            wp_redirect(add_query_arg('error', '1', remove_query_arg(array('action', 'child_id'))));
            exit;
        }
    }

    /**
     * Process delete child request.
     *
     * @param int $user_id User ID.
     * @param int $child_id Child ID.
     */
    private function process_delete_child($user_id, $child_id) {
        if (!$child_id) {
            return;
        }

        // Check ownership
        $child = get_post($child_id);
        if (!$child || $child->post_author != $user_id) {
            return;
        }

        // Delete the child
        wp_trash_post($child_id);
        
        // Redirect to same page with success message
        wp_redirect(add_query_arg('child_deleted', '1', remove_query_arg(array('action', 'child_id'))));
        exit;
    }

    /**
     * Handle AJAX delete request.
     */
    public function handle_ajax_delete() {
        check_ajax_referer('wc_child_subscription_manager_nonce', 'nonce');

        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => __('You do not have permission to delete children.', 'wc-child-subscription-manager')));
        }

        $child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
        if (!$child_id) {
            wp_send_json_error(array('message' => __('Invalid child ID.', 'wc-child-subscription-manager')));
        }

        $child = get_post($child_id);
        if (!$child || $child->post_author != get_current_user_id()) {
            wp_send_json_error(array('message' => __('You do not have permission to delete this child.', 'wc-child-subscription-manager')));
        }

        if (wp_trash_post($child_id)) {
            wp_send_json_success(array('message' => __('Child deleted successfully.', 'wc-child-subscription-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete child.', 'wc-child-subscription-manager')));
        }
    }
}
