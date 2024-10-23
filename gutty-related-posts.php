<?php
/*
Plugin Name: Gutty Related Posts
Plugin URI: https://guttypress.com/
Description: Gutty Related Posts is the best way to show related and relevant content on your WordPress site using the power of the Block Editor and Full Site Editing.
Version: {VERSION}
Author: Team GuttyPress
Author URI: https://profiles.wordpress.org/guttypress/
License: GPL2
Text Domain: gutty-related-posts
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GUTTY_RELATED_POSTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GUTTY_RELATED_POSTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GUTTY_RELATED_POSTS_VERSION', '{VERSION}');
define('GUTTY_RELATED_POSTS_STOP_WORDS', GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/stop-words');

// Include required files
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-stop-words.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-keyword-handler.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-rest-api.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'admin/class-grp-settings.php';
require_once GUTTY_RELATED_POSTS_PLUGIN_DIR . 'includes/class-grp-block.php';


// Activation hook to create custom tables
function gutty_related_posts_activate_plugin() {
    $grp_keyword_handler = new GuttyRelatedPosts\KeywordHandler\GuttyRelatedPosts_Keyword_Handler();
    $grp_keyword_handler->create_keyword_table();
}
register_activation_hook(__FILE__, 'gutty_related_posts_activate_plugin');

// Load plugin textdomain for translations
function gutty_related_posts_load_textdomain() {
    load_plugin_textdomain('gutty-related-posts', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'gutty_related_posts_load_textdomain');