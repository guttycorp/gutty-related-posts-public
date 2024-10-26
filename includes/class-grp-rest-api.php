<?php

namespace GuttyRelatedPosts\RestAPI;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use GuttyRelatedPosts\KeywordHandler\GuttyRelatedPosts_Keyword_Handler;
use WP_REST_Request;

class GuttyRelatedPosts_REST_API {
	private GuttyRelatedPosts_Keyword_Handler $keyword_handler;

    public function __construct() {
		$this->keyword_handler = new GuttyRelatedPosts_Keyword_Handler();

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('gutty-related-posts/v1', '/gutty-related-posts', [
            'methods' => 'GET',
            'callback' => [$this, 'get_related_posts'],
            'permission_callback' => '__return_true',
        ]);
    }

	public function get_related_posts(WP_REST_Request $request) {
		$num_posts = $request->get_param('numPosts');
		$post_id = $request->get_param('post_id');

		if (!$post_id) {
			return new WP_Error('no_post_id', __('Invalid post ID', 'gutty-related-posts'), ['status' => 400]);
		}
	
		$related_posts = $this->keyword_handler->get_related_posts($post_id, $num_posts);
	
		if (empty($related_posts)) {
			return [];
		}

		$posts_data = [];
		foreach ($related_posts as $post) {	
			$posts_data[] = [
				'id'             => $post->ID,
			];
		}
	
		return rest_ensure_response($posts_data);
	}
	
}

// Initialize the REST API class
new GuttyRelatedPosts_REST_API();
