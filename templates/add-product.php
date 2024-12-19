<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


$current_user = wp_get_current_user();
$edit_product_id = isset($_GET['edit_product']) ? intval($_GET['edit_product']) : 0;

// If we're editing an existing product
$product = null;
if ($edit_product_id > 0) {
    $product = get_post($edit_product_id);

    // Ensure that the current user is the owner of the product or is an admin
    if (!$product || $product->post_author != $current_user->ID) {
        wp_redirect(home_url('/my-account')); // Redirect if no permission
        exit;
    }
}
?>

<div class="wpmpw-add-product">
    <h2><?php echo $product ? esc_html__('Edit Product', 'wpmpw') : esc_html__('Add New Product', 'wpmpw'); ?></h2>
    
    <form action="<?php echo admin_url('admin-post.php'); ?>" method="post" enctype="multipart/form-data">

        <?php wp_nonce_field('wpmpw_add_product_action', 'wpmpw_add_product_nonce'); ?>

        <input type="hidden" name="action" value="wpmpw_add_product">
        <?php if ($product) : ?>
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product->ID); ?>">
        <?php endif; ?>

        <p>
            <label for="product_title"><?php esc_html_e('Product Title', 'wpmpw'); ?></label>
            <input type="text" id="product_title" name="product_title" value="<?php echo $product ? esc_attr($product->post_title) : ''; ?>" required>
        </p>

        <p>
            <label for="product_price"><?php esc_html_e('Price', 'wpmpw'); ?></label>
            <input type="number" id="product_price" name="product_price" step="0.01" value="<?php echo $product ? esc_attr(get_post_meta($product->ID, '_price', true)) : ''; ?>" required>
        </p>

        <p>
            <label for="product_quantity"><?php esc_html_e('Quantity', 'wpmpw'); ?></label>
            <input type="number" id="product_quantity" name="product_quantity" value="<?php echo $product ? esc_attr(get_post_meta($product->ID, '_quantity', true)) : ''; ?>" required>
        </p>

        <p>
            <label for="product_description"><?php esc_html_e('Description', 'wpmpw'); ?></label>
            <?php
            wp_editor(
                $product ? get_post_meta($product->ID, '_description', true) : '',
                'product_description',
                [
                    'textarea_name' => 'product_description',
                    'media_buttons' => false,
                    'textarea_rows' => 10,
                ]
            );
            ?>
        </p>

        <p>
            <label for="product_image"><?php esc_html_e('Product Image', 'wpmpw'); ?></label>
            <input type="button" id="wpmpw-upload-image-button" class="button" value="<?php esc_attr_e('Upload Image', 'wpmpw'); ?>">
            <input type="hidden" id="product_image" name="product_image" value="<?php echo $product ? esc_attr(get_post_meta($product->ID, '_image', true)) : ''; ?>">
            <div id="wpmpw-image-preview">
                <?php if ($product) : ?>
                    <img src="<?php echo esc_url(wp_get_attachment_url(get_post_meta($product->ID, '_image', true))); ?>" style="max-width: 100%; height: auto;">
                <?php endif; ?>
            </div>
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php echo $product ? esc_html__('Update Product', 'wpmpw') : esc_html__('Save Product', 'wpmpw'); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function ($) {
    let mediaUploader;

    $('#wpmpw-upload-image-button').click(function (e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: '<?php esc_html_e('Select Product Image', 'wpmpw'); ?>',
            button: {
                text: '<?php esc_html_e('Use This Image', 'wpmpw'); ?>'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#product_image').val(attachment.id);
            $('#wpmpw-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">');
        });

        mediaUploader.open();
    });
});
</script>
