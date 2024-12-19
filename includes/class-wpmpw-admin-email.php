<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WPMPW_Admin_Email extends WC_Email
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id          = 'wpmpw_admin_email';
        $this->title       = __('New Product Pending Review', 'wpmpw');
        $this->description = __('This email is sent to the admin when a new product is pending review.', 'wpmpw');

        $this->template_html  = 'emails/wpmpw-admin-email.php';
        $this->template_plain = 'emails/plain/wpmpw-admin-email.php';
        $this->template_base  = plugin_dir_path(__FILE__) . '../templates/';

        // Trigger email.
        add_action('wpmpw_trigger_admin_email', [$this, 'trigger'], 10, 2);

        // Call parent constructor.
        parent::__construct();

        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int    $product_id Product ID.
     * @param WP_User $author     Product author.
     */
    public function trigger($product_id, $author)
    {
        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        $this->object = get_post($product_id);

        $this->placeholders = [
            '{product_title}' => $this->object->post_title,
            '{author_name}'   => $author->display_name,
            '{author_url}'    => admin_url("user-edit.php?user_id={$author->ID}"),
            '{edit_url}'      => admin_url("post.php?post={$product_id}&action=edit"),
        ];

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    /**
     * Get default email subject.
     *
     * @return string
     */
    public function get_default_subject()
    {
        return __('New Product Pending Review: {product_title}', 'wpmpw');
    }

    /**
     * Get default email heading.
     *
     * @return string
     */
    public function get_default_heading()
    {
        return __('New Product Submission', 'wpmpw');
    }

    /**
     * Get email content HTML.
     *
     * @return string
     */
    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'email_heading' => $this->get_heading(),
                'product'       => $this->object,
                'author_url'    => $this->placeholders['{author_url}'],
                'edit_url'      => $this->placeholders['{edit_url}'],
                'email'         => $this,
            ],
            '',
            $this->template_base
        );
    }

    /**
     * Get email content plain text.
     *
     * @return string
     */
    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email_heading' => $this->get_heading(),
                'product'       => $this->object,
                'author_url'    => $this->placeholders['{author_url}'],
                'edit_url'      => $this->placeholders['{edit_url}'],
                'email'         => $this,
            ],
            '',
            $this->template_base
        );
    }

    /**
     * Initialize form fields for the email settings.
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'wpmpw'),
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'wpmpw'),
                'default' => 'yes',
            ],
            'recipient' => [
                'title'       => __('Recipient(s)', 'wpmpw'),
                'type'        => 'text',
                'description' => __('Enter recipient(s) email address. Defaults to admin email.', 'wpmpw'),
                'placeholder' => __('admin@example.com', 'wpmpw'),
                'default'     => get_option('admin_email'),
            ],
        ];
    }
}

// Ensure this class is loaded and added to WooCommerce email classes.
add_filter('woocommerce_email_classes', function ($emails) {
    $emails['WPMPW_Admin_Email'] = new WPMPW_Admin_Email();
    return $emails;
});
