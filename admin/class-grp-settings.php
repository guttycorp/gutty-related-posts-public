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

        // Handle cache clearing
        add_action('admin_init', [$this, 'handle_clear_cache']);

        // Handle TF-IDF generation
        add_action('admin_init', [$this, 'handle_generate_keywords']);

        // Initialize Keyword Handler
        $this->keyword_handler = new GuttyRelatedPosts_Keyword_Handler();
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
            __('Related Posts Configuration', 'gutty-related-posts'),
            [$this, 'settings_section_callback'],
            'guttyrelatedposts-settings'
        );

        add_settings_field(
            'guttyrelatedposts_number_of_posts',
            __('Number of Related Posts', 'gutty-related-posts'),
            [$this, 'number_of_posts_render'],
            'guttyrelatedposts-settings',
            'guttyrelatedposts_settings_section'
        );

        add_settings_field(
            'guttyrelatedposts_cache_duration',
            __('Cache Duration (seconds)', 'gutty-related-posts'),
            [$this, 'cache_duration_render'],
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

        // Cache duration
        if (isset($input['guttyrelatedposts_cache_duration'])) {
            $sanitized['guttyrelatedposts_cache_duration'] = absint($input['guttyrelatedposts_cache_duration']);
            if ($sanitized['guttyrelatedposts_cache_duration'] < 3600) { // Minimum 1 hour
                $sanitized['guttyrelatedposts_cache_duration'] = 43200; // Default 12 hours
            }
        }

        return $sanitized;
    }

    // Render Number of Related Posts Field
    public function number_of_posts_render() {
        $options = get_option('guttyrelatedposts_settings');
        $number_of_posts = isset($options['guttyrelatedposts_number_of_posts']) ? intval($options['guttyrelatedposts_number_of_posts']) : 5;
        ?>
        <input type='number' name='guttyrelatedposts_settings[guttyrelatedposts_number_of_posts]' value='<?php echo esc_attr($number_of_posts); ?>' min='1' max='20'>
        <?php
    }

    // Render Cache Duration Field
    public function cache_duration_render() {
        $options = get_option('guttyrelatedposts_settings');
        $cache_duration = isset($options['guttyrelatedposts_cache_duration']) ? intval($options['guttyrelatedposts_cache_duration']) : 43200;
        ?>
        <input type='number' name='guttyrelatedposts_settings[guttyrelatedposts_cache_duration]' value='<?php echo esc_attr($cache_duration); ?>' min='3600' step='3600'>
        <p class="description"><?php esc_html_e('Enter cache duration in seconds (e.g., 43200 for 12 hours). Minimum 1 hour.', 'gutty-related-posts'); ?></p>
        <?php
    }

    // Section Callback
    public function settings_section_callback() {
        esc_html_e('Configure the settings for the Gutty Related Posts plugin.', 'gutty-related-posts');
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

            <h2><?php esc_html_e('Cache Management', 'gutty-related-posts'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('guttyrelatedposts_clear_cache_nonce_action', 'guttyrelatedposts_clear_cache_nonce'); ?>
                <input type="submit" name="guttyrelatedposts_clear_cache" class="button button-secondary" value="<?php esc_attr_e('Clear Cache', 'gutty-related-posts'); ?>" />
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

    // Clear cache handler
    public function handle_clear_cache() {
        if (isset($_POST['guttyrelatedposts_clear_cache']) && check_admin_referer('guttyrelatedposts_clear_cache_nonce_action', 'guttyrelatedposts_clear_cache_nonce')) {
            global $wpdb;

            // Query to delete all transients related to the GRP plugin
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_guttyrelatedposts_related_posts_%' OR option_name LIKE '_transient_timeout_guttyrelatedposts_related_posts_%'");

            // Display admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Related posts cache cleared successfully.', 'gutty-related-posts') . '</p></div>';
            });
        }
    }

    // Handle TF-IDF keyword generation process
    public function handle_generate_keywords() {
        if (isset($_POST['guttyrelatedposts_generate_keywords']) && check_admin_referer('guttyrelatedposts_generate_keywords_nonce_action', 'guttyrelatedposts_generate_keywords_nonce')) {
            // Run the keyword generation process
            $this->keyword_handler->process_all_posts();

            // Clear related posts cache since keywords have been updated
            $this->clear_related_posts_cache();

            // Display admin notice for success
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('TF-IDF keywords generated successfully for all posts.', 'gutty-related-posts') . '</p></div>';
            });
        }
    }

    // Clear related posts cache
    private function clear_related_posts_cache() {
        global $wpdb;

        // Query to delete all transients related to the Gutty Related Posts plugin
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_guttyrelatedposts_related_posts_%' OR option_name LIKE '_transient_timeout_guttyrelatedposts_related_posts_%'");
    }
}

// Initialize the GuttyRelatedPosts Settings Class
new GuttyRelatedPosts_Settings();
