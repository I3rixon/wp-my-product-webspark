<?php
/**
 * Email notification template for new/edited products.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/** @var string $product_title */
/** @var string $author_url */
/** @var string $edit_url */
?>

<p><?php esc_html_e('A new product has been submitted or edited by a user:', 'wpmpw'); ?></p>

<ul>
    <li><strong><?php esc_html_e('Product Title:', 'wpmpw'); ?></strong> <?php echo esc_html($product_title); ?></li>
    <li><strong><?php esc_html_e('Author Profile URL:', 'wpmpw'); ?></strong> <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_url($author_url); ?></a></li>
    <li><strong><?php esc_html_e('Edit Product URL:', 'wpmpw'); ?></strong> <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_url($edit_url); ?></a></li>
</ul>

<p><?php esc_html_e('Please review the product as soon as possible.', 'wpmpw'); ?></p>
