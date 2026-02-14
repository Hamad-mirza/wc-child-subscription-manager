<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Child_Subscription_Manager_Frontend {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {

        add_shortcode('wc_my_children', array($this, 'shortcode_my_children'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'handle_actions'));

        // AJAX delete hooks
        add_action('wp_ajax_wc_child_subscription_manager_delete_child', array($this, 'handle_ajax_delete'));
    }

    /* -------------------------------------------------------------
     ENQUEUE SCRIPTS
    ------------------------------------------------------------- */

    public function enqueue_scripts() {

        wp_enqueue_style(
            'wc-child-subscription-manager',
            WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WC_CHILD_SUBSCRIPTION_MANAGER_VERSION
        );

        wp_enqueue_script(
            'wc-child-subscription-manager',
            WC_CHILD_SUBSCRIPTION_MANAGER_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WC_CHILD_SUBSCRIPTION_MANAGER_VERSION,
            true
        );

        wp_localize_script('wc-child-subscription-manager', 'wcChildSubscriptionManager', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('wc_child_subscription_manager_nonce'),
            'confirm_delete' => __('Are you sure you want to delete this child?', 'wc-child-subscription-manager'),
        ));
    }

    /* -------------------------------------------------------------
     SHORTCODE
    ------------------------------------------------------------- */

    public function shortcode_my_children() {

        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to manage your children.', 'wc-child-subscription-manager') . '</p>';
        }

        $user_id = get_current_user_id();
        $children = $this->get_children_for_user($user_id);

        // Detect edit mode
        $edit_child_id = 0;
        if (isset($_GET['action']) && $_GET['action'] === 'edit_child' && isset($_GET['child_id'])) {
            $edit_child_id = intval($_GET['child_id']);
        }

        ob_start();
        ?>

        <div class="wc-child-subscription-manager">
            <h2><?php esc_html_e('My Children', 'wc-child-subscription-manager'); ?></h2>

            <?php $this->display_notices(); ?>

            <?php if (!empty($children)) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Club</th>
                            <th>Actions</th>
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
                                    <a href="<?php echo esc_url(add_query_arg(array(
                                        'action' => 'edit_child',
                                        'child_id' => $child->ID
                                    ))); ?>">Edit</a> |

                                    <a href="<?php echo esc_url(add_query_arg(array(
                                        'action' => 'delete_child',
                                        'child_id' => $child->ID
                                    ))); ?>"
                                       class="delete-child"
                                       data-child-id="<?php echo esc_attr($child->ID); ?>">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php $this->render_add_child_form($edit_child_id); ?>

        </div>

        <?php
        return ob_get_clean();
    }

    /* -------------------------------------------------------------
     GET CHILDREN
    ------------------------------------------------------------- */

    public function get_children_for_user($user_id) {

        return get_posts(array(
            'post_type'      => 'wc_child',
            'posts_per_page' => -1,
            'meta_key'       => '_parent_user_id',
            'meta_value'     => $user_id,
        ));
    }

    /* -------------------------------------------------------------
     FORM
    ------------------------------------------------------------- */

    public function render_add_child_form($child_id = 0) {

        $data = array(
            'title' => '',
            'dob'   => '',
            'gender'=> '',
            'age'   => '',
            'club'  => '',
        );

        if ($child_id) {
            $child = get_post($child_id);
            if ($child && $child->post_author == get_current_user_id()) {
                $data['title']  = $child->post_title;
                $data['dob']    = get_post_meta($child_id, '_dob', true);
                $data['gender'] = get_post_meta($child_id, '_gender', true);
                $data['age']    = get_post_meta($child_id, '_age', true);
                $data['club']   = get_post_meta($child_id, '_club', true);
            }
        }

        ?>

        <h3><?php echo $child_id ? 'Edit Child' : 'Add Child'; ?></h3>

        <form method="post">
            <?php wp_nonce_field('wc_child_manager', 'wc_child_manager_nonce'); ?>
            <input type="hidden" name="child_id" value="<?php echo esc_attr($child_id); ?>">

            <p>
                <input type="text" name="child_name" placeholder="Name"
                    value="<?php echo esc_attr($data['title']); ?>" required>
            </p>

            <p>
                <input type="date" name="dob" value="<?php echo esc_attr($data['dob']); ?>">
            </p>

            <p>
                <select name="gender">
                    <option value="">Select Gender</option>
                    <option value="male" <?php selected($data['gender'], 'male'); ?>>Male</option>
                    <option value="female" <?php selected($data['gender'], 'female'); ?>>Female</option>
                </select>
            </p>

            <p>
                <input type="number" name="age" placeholder="Age"
                    value="<?php echo esc_attr($data['age']); ?>">
            </p>

            <p>
                <input type="text" name="club" placeholder="Club"
                    value="<?php echo esc_attr($data['club']); ?>">
            </p>

            <p>
                <input type="submit"
                       name="<?php echo $child_id ? 'update_child' : 'add_child'; ?>"
                       value="<?php echo $child_id ? 'Update Child' : 'Add Child'; ?>">
            </p>
        </form>

        <?php
    }

    /* -------------------------------------------------------------
     HANDLE ACTIONS
    ------------------------------------------------------------- */

    public function handle_actions() {

        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        if (isset($_POST['add_child'])) {
            $this->process_add_child($user_id);
        }

        if (isset($_POST['update_child'])) {
            $this->process_update_child($user_id);
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete_child') {
            $this->process_delete_child($user_id, intval($_GET['child_id']));
        }
    }

    /* -------------------------------------------------------------
     ADD
    ------------------------------------------------------------- */

    private function process_add_child($user_id) {

        if (!wp_verify_nonce($_POST['wc_child_manager_nonce'], 'wc_child_manager')) {
            wp_die('Security check failed');
        }

        $post_id = wp_insert_post(array(
            'post_title'  => sanitize_text_field($_POST['child_name']),
            'post_type'   => 'wc_child',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ));

        if ($post_id) {

            update_post_meta($post_id, '_parent_user_id', $user_id);
            update_post_meta($post_id, '_dob', sanitize_text_field($_POST['dob']));
            update_post_meta($post_id, '_gender', sanitize_text_field($_POST['gender']));
            update_post_meta($post_id, '_age', absint($_POST['age']));
            update_post_meta($post_id, '_club', sanitize_text_field($_POST['club']));

            wp_redirect(add_query_arg('child_added', 1));
            exit;
        }
    }

    /* -------------------------------------------------------------
     UPDATE
    ------------------------------------------------------------- */

    private function process_update_child($user_id) {

        if (!wp_verify_nonce($_POST['wc_child_manager_nonce'], 'wc_child_manager')) {
            wp_die('Security check failed');
        }

        $child_id = intval($_POST['child_id']);
        $child = get_post($child_id);

        if (!$child || $child->post_author != $user_id) {
            wp_die('Permission denied');
        }

        wp_update_post(array(
            'ID' => $child_id,
            'post_title' => sanitize_text_field($_POST['child_name']),
        ));

        update_post_meta($child_id, '_dob', sanitize_text_field($_POST['dob']));
        update_post_meta($child_id, '_gender', sanitize_text_field($_POST['gender']));
        update_post_meta($child_id, '_age', absint($_POST['age']));
        update_post_meta($child_id, '_club', sanitize_text_field($_POST['club']));

        wp_redirect(remove_query_arg(array('action','child_id')));
        exit;
    }

    /* -------------------------------------------------------------
     DELETE
    ------------------------------------------------------------- */

    private function process_delete_child($user_id, $child_id) {

        $child = get_post($child_id);

        if ($child && $child->post_author == $user_id) {
            wp_trash_post($child_id);
            wp_redirect(remove_query_arg(array('action','child_id')));
            exit;
        }
    }

    public function handle_ajax_delete() {

        check_ajax_referer('wc_child_subscription_manager_nonce', 'nonce');

        $child_id = intval($_POST['child_id']);
        $child = get_post($child_id);

        if ($child && $child->post_author == get_current_user_id()) {
            wp_trash_post($child_id);
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /* -------------------------------------------------------------
     NOTICES
    ------------------------------------------------------------- */

    private function display_notices() {

        if (isset($_GET['child_added'])) {
            echo '<div class="notice success">Child added successfully.</div>';
        }

        if (isset($_GET['child_deleted'])) {
            echo '<div class="notice success">Child deleted successfully.</div>';
        }
    }
}
