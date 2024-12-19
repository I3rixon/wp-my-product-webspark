<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$current_user = wp_get_current_user();
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = [
    'post_type'      => 'product',
    'post_status'    => ['pending', 'draft', 'publish'],
    'posts_per_page' => 10,
    'paged'          => $paged,
    'author'         => $current_user->ID,
];

$products = new WP_Query($args);


/*// Handle product deletion.
if (isset($_POST['wpmpw_delete_product_nonce'], $_POST['delete_product_id']) && wp_verify_nonce($_POST['wpmpw_delete_product_nonce'], 'wpmpw_delete_product_action')) {
    
    $product_id = intval($_POST['delete_product_id']);

    if (get_post_field('post_author', $product_id) == $current_user->ID) {
        wp_trash_post($product_id);
        wc_add_notice(__('Product deleted successfully.', 'wpmpw'), 'success');
        wp_redirect(wc_get_account_endpoint_url('my-products'));
        exit;
    } else {
        wc_add_notice(__('You do not have permission to delete this product.', 'wpmpw'), 'error');
    }
}*/

?>

<div class="wpmpw-my-products">
    <h2><?php esc_html_e('My Products', 'wpmpw'); ?></h2>

    <?php if ($products->have_posts()) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Title', 'wpmpw'); ?></th>
                    <th><?php esc_html_e('Quantity', 'wpmpw'); ?></th>
                    <th><?php esc_html_e('Price', 'wpmpw'); ?></th>
                    <th><?php esc_html_e('Status', 'wpmpw'); ?></th>
                    <th><?php esc_html_e('Actions', 'wpmpw'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($products->have_posts()) : $products->the_post(); ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_quantity', true)); ?></td>
                        <td><?php echo wc_price(get_post_meta(get_the_ID(), '_regular_price', true)); ?></td>
                        <td><?php echo esc_html(get_post_status()); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('edit_product', get_the_ID(), wc_get_account_endpoint_url('add-product'))); ?>" class="button">Edit</a>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('wpmpw_delete_product_action', 'wpmpw_delete_product_nonce'); ?>
                                <input type="hidden" name="delete_product_id" value="<?php the_ID(); ?>">
                                <button type="submit" class="button button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            echo paginate_links([
                'total'   => $products->max_num_pages,
                'current' => $paged,
                'format'  => '?paged=%#%',
            ]);
            ?>
        </div>

    <?php else : ?>
        <p><?php esc_html_e('No products found.', 'wpmpw'); ?></p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>


