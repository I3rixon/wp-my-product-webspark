<?php
/**
 * Plugin Name: WP Product Webspark
 * Description: Extends WooCommerce functionality to allow CRUD operations for products via My Account.
 * Version: 1.0.3
 * Author: Alex
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
function wpmpw_activate_check() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('WP Product Webspark requires WooCommerce to be installed and activated.', 'wpmpw'),
            esc_html__('Plugin Activation Error', 'wpmpw'),
            [
                'back_link' => true
            ]
        );
    }
}
register_activation_hook(__FILE__, 'wpmpw_activate_check');

function wpmpw_activate() {
    wpmpw_activate_check();
    wpmpw_add_endpoints();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wpmpw_activate');

// Add custom menu items in My Account.
add_filter('woocommerce_account_menu_items', 'wpmpw_add_menu_items');
function wpmpw_add_menu_items($items)
{
    $new_items = array(
        'add-product' => __('Add Product', 'wpmpw'),
        'my-products' => __('My Products', 'wpmpw'),
    );
    if (isset($items['customer-logout'])) {
        $logout = array('customer-logout' => $items['customer-logout']);
        unset($items['customer-logout']);
        $items = array_merge($items, $new_items, $logout);
    } else {
        $items = array_merge($items, $new_items);
    }

    
    return $items;
}

add_action('init', 'wpmpw_add_endpoints');
function wpmpw_add_endpoints()
{
    add_rewrite_endpoint('add-product', EP_PAGES);
    add_rewrite_endpoint('my-products', EP_PAGES);
}


add_action('woocommerce_account_add-product_endpoint', 'wpmpw_add_product_page');
add_action('woocommerce_account_my-products_endpoint', 'wpmpw_my_products_page');

function wpmpw_add_product_page()
{
    include plugin_dir_path(__FILE__) . 'templates/add-product.php';
}

function wpmpw_my_products_page()
{
    include plugin_dir_path(__FILE__) . 'templates/my-products.php';
}


function wpmpw_handle_add_or_edit_product() {
    if (!isset($_POST['wpmpw_add_product_nonce']) || !wp_verify_nonce($_POST['wpmpw_add_product_nonce'], 'wpmpw_add_product_action')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }
    
    // Retrieve form data
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_title = sanitize_text_field($_POST['product_title']);
    $product_price = sanitize_text_field($_POST['product_price']);
    $product_quantity = intval($_POST['product_quantity']);
    $product_description = isset($_POST['product_description']) ? wp_kses_post($_POST['product_description']) : '';
    $product_image = isset($_POST['product_image']) ? intval($_POST['product_image']) : 0;

    
    if ($product_id > 0) {
        $existing_product = get_post($product_id);
        if (!$existing_product || $existing_product->post_author != get_current_user_id()) {
            return; // Prevent unauthorized edit
        }
    }
    //print_r($existing_product); die();

    $product_data = array(
        'post_title'   => $product_title,
        'post_type'    => 'product',
        'post_status'  => 'pending',
        'post_author'  => get_current_user_id(),
    );

    if ($product_id > 0) {
        $product_data['ID'] = $product_id; // For updating an existing product
        wp_update_post($product_data);
    } else {
        $product_id = wp_insert_post($product_data); // Create a new product
    }

   
    update_post_meta($product_id, '_regular_price', $product_price);
    update_post_meta($product_id, '_quantity', $product_quantity);
    update_post_meta($product_id, '_description', $product_description);
    set_post_thumbnail($product_id, $product_image);
    update_post_meta($product_id, '_image', $product_image);


   
    wp_redirect(home_url('/my-account/my-products/')); 
    exit;
}

add_action('admin_post_wpmpw_add_product', 'wpmpw_handle_add_or_edit_product');
add_action('admin_post_nopriv_wpmpw_add_product', 'wpmpw_handle_add_or_edit_product');


// Email notification to admin.
add_action('save_post_product', 'wpmpw_notify_admin', 10, 3);
function wpmpw_notify_admin($post_id, $post, $update)
{
    if ($post->post_status !== 'pending') {
        return;
    }

    $author_id = $post->post_author;
    $product_title = $post->post_title;
    $author_url = admin_url("user-edit.php?user_id=$author_id");
    $edit_url = admin_url("post.php?post=$post_id&action=edit");
    $email = get_option('admin_email');

    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/email-notification.php';
    $message = ob_get_clean();

    wp_mail($email, __('New Product Pending Review', 'wpmpw'), $message);
}


add_filter('woocommerce_email_classes', 'wpmpw_register_email_class');
function wpmpw_register_email_class($emails)
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wpmpw-admin-email.php';
    $emails['WPMPW_Admin_Email'] = new WPMPW_Admin_Email();
    return $emails;
}

add_filter('wp_mail_content_type', function() {
    return 'text/html';
});

add_action('template_redirect', function() {
    if (isset($_POST['wpmpw_delete_product_nonce'], $_POST['delete_product_id']) && wp_verify_nonce($_POST['wpmpw_delete_product_nonce'], 'wpmpw_delete_product_action')) {
        
        $product_id = intval($_POST['delete_product_id']);
        $current_user = wp_get_current_user();

        if (get_post_field('post_author', $product_id) == $current_user->ID) {
            wp_trash_post($product_id);
            wc_add_notice(__('Product deleted successfully.', 'wpmpw'), 'success');
            wp_redirect(wc_get_account_endpoint_url('my-products'));
            exit;
        } else {
            wc_add_notice(__('You do not have permission to delete this product.', 'wpmpw'), 'error');
        }
    }
});

add_action('wp_enqueue_scripts', 'wpmpw_enqueue_scripts');
function wpmpw_enqueue_scripts()
{
    if (is_account_page()) {
        wp_enqueue_media();
        wp_enqueue_script('wpmpw-scripts', plugin_dir_url(__FILE__) . 'assets/js/scripts.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style( 'wpwebspark-style', plugin_dir_url( __FILE__ ) . 'assets/css/wpwebspark.css' );
    }
}

add_filter( 'ajax_query_attachments_args', 'filter_attachments_by_current_user', 10, 1 );

function filter_attachments_by_current_user( $query ) {
    $user_id = get_current_user_id();
    if ( $user_id ) {
        $query['author'] = $user_id;
    }
    return $query;
}
function wpmpw_add_customer_upload_capability() {
    $role = get_role('customer'); // Get the customer role
    if ($role) {
       
        $role->add_cap('upload_files');
        $role->add_cap( 'edit_posts' );
             
    }
}

add_action('init', 'wpmpw_add_customer_upload_capability');