<?php

namespace GuttyRelatedPosts\Settings;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use GuttyRelatedPosts\KeywordHandler\GuttyRelatedPosts_Keyword_Handler;

class GuttyRelatedPosts_Settings {

    private GuttyRelatedPosts_Keyword_Handler $keyword_handler;

    public function __construct() {
        $this->keyword_handler = new GuttyRelatedPosts_Keyword_Handler();

        // Register admin menu and settings
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);

        // Handle TF-IDF generation
        add_action('admin_init', [$this, 'handle_generate_keywords']);
    }

    // Add admin menu
    public function add_admin_menu() {
        add_options_page(
            __('Gutty Related Posts Settings', 'gutty-related-posts'),
            __('Gutty Related Posts', 'gutty-related-posts'),
            'manage_options',
            'guttyrelatedposts-settings',
            [$this, 'settings_page']
        );
    }

    // Register settings
    public function settings_init() {
        register_setting('guttyrelatedposts_settings_group', 'guttyrelatedposts_settings', [$this, 'sanitize_settings']);

        add_settings_section(
            'guttyrelatedposts_settings_section',
            '',
            [$this, 'settings_section_callback'],
            'guttyrelatedposts-settings'
        );

        // Number of Related Posts Field
        add_settings_field(
            'guttyrelatedposts_number_of_posts',
            __('Number of Related Posts', 'gutty-related-posts'),
            [$this, 'number_of_posts_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        // Image Size Selection Field
        add_settings_field(
            'guttyrelatedposts_image_size',
            __('Image Size', 'gutty-related-posts'),
            [$this, 'image_size_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        // Automatically Display Block Field
        add_settings_field(
            'guttyrelatedposts_after_post_content',
            __('Automatically display the block', 'gutty-related-posts'),
            [$this, 'after_post_content'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        // Default Title Field
        add_settings_field(
            'guttyrelatedposts_default_title',
            __('Default Title for Related Posts', 'gutty-related-posts'),
            [$this, 'default_title_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        // "Read More" Text Field
        add_settings_field(
            'guttyrelatedposts_read_more_text',
            __('"Read More" Text', 'gutty-related-posts'),
            [$this, 'read_more_text_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        // "No related posts found." Text Field
        add_settings_field(
            'guttyrelatedposts_none_posts_text',
            __('"No related posts found." Text', 'gutty-related-posts'),
            [$this, 'none_posts_text_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );
    }

    // Sanitize settings input
    public function sanitize_settings($input) {
        $sanitized = [];

        // Number of related posts
        if (isset($input['guttyrelatedposts_number_of_posts'])) {
            $sanitized['guttyrelatedposts_number_of_posts'] = absint($input['guttyrelatedposts_number_of_posts']);
            if ($sanitized['guttyrelatedposts_number_of_posts'] < 1) {
                $sanitized['guttyrelatedposts_number_of_posts'] = 5;
            }
        }

        // Image size
        if (isset($input['guttyrelatedposts_image_size'])) {
            $sanitized['guttyrelatedposts_image_size'] = sanitize_text_field($input['guttyrelatedposts_image_size']);
        }

        if (isset($input['guttyrelatedposts_after_post_content'])) {
            $sanitized['guttyrelatedposts_after_post_content'] = (bool) $input['guttyrelatedposts_after_post_content'];
        }

        // Default title
        if (isset($input['guttyrelatedposts_default_title'])) {
            $sanitized['guttyrelatedposts_default_title'] = sanitize_text_field($input['guttyrelatedposts_default_title']);
        }

        // "Read More" text
        if (isset($input['guttyrelatedposts_read_more_text'])) {
            $sanitized['guttyrelatedposts_read_more_text'] = sanitize_text_field($input['guttyrelatedposts_read_more_text']);
        }

        // "No related posts found." text
        if (isset($input['guttyrelatedposts_none_posts_text'])) {
            $sanitized['guttyrelatedposts_none_posts_text'] = sanitize_text_field($input['guttyrelatedposts_none_posts_text']);
        }

        return $sanitized;
    }

    // Render Number of Related Posts Field
    public function number_of_posts_render() {
        $options = get_option('guttyrelatedposts_settings');
        $number_of_posts = $options['guttyrelatedposts_number_of_posts'] ?? 5;
        ?>
        <input type='number' name='guttyrelatedposts_settings[guttyrelatedposts_number_of_posts]' value='<?php echo esc_attr($number_of_posts); ?>' min='1' max='20'>
        <?php
    }

    // Render Image Size Field
    public function image_size_render() {
        $options = get_option('guttyrelatedposts_settings');
        $image_size = $options['guttyrelatedposts_image_size'] ?? 'thumbnail';
        $sizes = get_intermediate_image_sizes();
        ?>
        <select name="guttyrelatedposts_settings[guttyrelatedposts_image_size]">
            <?php foreach ($sizes as $size) : ?>
                <option value="<?php echo esc_attr($size); ?>" <?php selected($image_size, $size); ?>>
                    <?php echo esc_html($size); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function after_post_content() {
        $options = get_option('guttyrelatedposts_settings');
        $after_post_content = isset($options['guttyrelatedposts_after_post_content']) ? (bool) $options['guttyrelatedposts_after_post_content'] : false;
        ?>
        <input id="guttyrelatedposts_after_post_content" type="checkbox" name="guttyrelatedposts_settings[guttyrelatedposts_after_post_content]" value="1" <?php checked($after_post_content, true); ?>>
        <label for="guttyrelatedposts_after_post_content"><?php esc_html_e('Enable to automatically display the block after post content (Posts only)', 'gutty-related-posts'); ?></label>
        <?php
    }

    // Render Default Title Field
    public function default_title_render() {
        $options = get_option('guttyrelatedposts_settings');
        $default_title = $options['guttyrelatedposts_default_title'] ?? __('Related Posts', 'gutty-related-posts');
        ?>
        <input type="text" name="guttyrelatedposts_settings[guttyrelatedposts_default_title]" value="<?php echo esc_attr($default_title); ?>" placeholder="<?php esc_attr_e('Related Posts', 'gutty-related-posts'); ?>">
        <?php
    }

    // Render "Read More" Text Field
    public function read_more_text_render() {
        $options = get_option('guttyrelatedposts_settings');
        $read_more_text = $options['guttyrelatedposts_read_more_text'] ?? __('Read more', 'gutty-related-posts');
        ?>
        <input type="text" name="guttyrelatedposts_settings[guttyrelatedposts_read_more_text]" value="<?php echo esc_attr($read_more_text); ?>" placeholder="<?php esc_attr_e('Read more', 'gutty-related-posts'); ?>">
        <?php
    }

    // Render "No Related Posts found." Text Field
    public function none_posts_text_render() {
        $options = get_option('guttyrelatedposts_settings');
        $none_posts_text = $options['guttyrelatedposts_none_posts_text'] ?? __('No related posts found.', 'gutty-related-posts');
        ?>
        <input type="text" name="guttyrelatedposts_settings[guttyrelatedposts_none_posts_text]" value="<?php echo esc_attr($none_posts_text); ?>" placeholder="<?php esc_attr_e('No related posts found.', 'gutty-related-posts'); ?>">
        <?php
    }

    // Section Callback
    public function settings_section_callback() {
        ?>
        <div class="gutty-related-posts-notice">
            <h3><?php esc_html_e('How to add the Gutty Related Posts block to your site?', 'gutty-related-posts'); ?></h3>

            <div class="gutty-related-posts-notice-items">
                <div>
                    <span class="dashicons dashicons-block-default"></span>
                    <h4><?php esc_html_e('Block Editor / FSE', 'gutty-related-posts'); ?></h4>
                    <p><?php echo wp_kses_post(__('Add the Related Posts block using the <strong>Block Editor</strong> or the <strong>Full Site Editing</strong> feature.', 'gutty-related-posts')); ?></p>
                    <p><?php echo wp_kses_post(esc_html__('Customize all the settings and design directly from the FSE/Block Editor.', 'gutty-related-posts')); ?></p>
                </div>

                <div>
                    <span class="dashicons dashicons-shortcode"></span>
                    <h4><?php esc_html_e('Shortcode', 'gutty-related-posts'); ?></h4>
                    <p><?php esc_html_e('You can also use this shortcode in your content (post, page, post type...):', 'gutty-related-posts'); ?></p>

                    <pre>[gutty_related_posts]</pre>
                    <pre>[gutty_related_posts number="5"]</pre>
                </div>

                <div>
                    <span class="dashicons dashicons-editor-code"></span>
                    <h4><?php esc_html_e('PHP template', 'gutty-related-posts'); ?></h4>
                    <p><?php esc_html_e('Copy and paste this function into your theme (e.g. singular.php) to add the Gutty Related Posts block:', 'gutty-related-posts'); ?></p>

                    <pre>&lt;?php echo do_shortcode('[gutty_related_posts]'); ?&gt;</pre>
                </div>
            </div>
        </div>

        <h3><?php esc_html_e('Settings for the shortcode', 'gutty-related-posts'); ?></h3>
    <?php
    }

    // Settings Page
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('guttyrelatedposts_settings_group');
                do_settings_sections('guttyrelatedposts-settings');
                submit_button();
                ?>
            </form>

            <h2><?php esc_html_e('TF-IDF Keyword Generation', 'gutty-related-posts'); ?></h2>
            <p><?php esc_html_e('We automatically update the content relationships database every time a post is saved. To initialize the table, click the button below.', 'gutty-related-posts'); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field('guttyrelatedposts_generate_keywords_nonce_action', 'guttyrelatedposts_generate_keywords_nonce'); ?>
                <input type="submit" name="guttyrelatedposts_generate_keywords" class="button button-primary" value="<?php esc_attr_e('Generate related posts', 'gutty-related-posts'); ?>" />
            </form>
        </div>
        <?php
    }

    // Handle TF-IDF keyword generation process
    public function handle_generate_keywords() {
        if (isset($_POST['guttyrelatedposts_generate_keywords']) && check_admin_referer('guttyrelatedposts_generate_keywords_nonce_action', 'guttyrelatedposts_generate_keywords_nonce')) {
            // Run the keyword generation process
            $this->keyword_handler->process_all_posts();

            // Display admin notice for success
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('TF-IDF keywords generated successfully for all posts.', 'gutty-related-posts') . '</p></div>';
            });
        }
    }
}

// Initialize the GuttyRelatedPosts Settings Class
new GuttyRelatedPosts_Settings();
