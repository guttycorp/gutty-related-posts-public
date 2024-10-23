<?php
defined('ABSPATH') or exit('Please don&rsquo;t call the plugin directly. Thanks :)');

/**
 * Uninstall Gutty Related Posts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { // If uninstall not called from WordPress exit
	exit;
}

class GuttyRelatedPosts_Uninstall {

	/**
	 * Constructor: manages uninstall for multisite
	 */
	public function __construct() {
		global $wpdb;

		// Don't do anything except if the constant GUTTY_RELATED_POSTS_UNINSTALL is explicitely defined and true.
		if ( ! defined( 'GUTTY_RELATED_POSTS_UNINSTALL' ) || ! GUTTY_RELATED_POSTS_UNINSTALL ) {
			return;
		}

		// Check if it is a multisite uninstall - if so, run the uninstall function for each blog id
		if ( is_multisite() ) {
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->uninstall();
			}
			restore_current_blog();
		}
		else {
			$this->uninstall();
		}
	}

	/**
	 * Delete all entries in the DB related to Gutty Related Posts:
	 * Transients, post meta, options, custom tables
	 */
	public function uninstall() {
		global $wpdb;

		do_action( 'gutty_related_posts_uninstall' );

        // Delete global settings
        $options = $wpdb->get_col( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'gutty_related_posts_settings_%'" );
        array_map( 'delete_option', $options );

		// Delete transients
		delete_transient( '_gutty_related_posts' );

        // Delete custom tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}gutty_related_posts_keywords");
	}
}

new GuttyRelatedPosts_Uninstall();
