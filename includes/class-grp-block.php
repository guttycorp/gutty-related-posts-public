<?php

namespace GuttyRelatedPosts\Block;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use GuttyRelatedPosts\KeywordHandler\GuttyRelatedPosts_Keyword_Handler;

class GuttyRelatedPosts_Block {
    private GuttyRelatedPosts_Keyword_Handler $keyword_handler;

    public function __construct() {
        // Instantiate the GuttyRelatedPosts_Keyword_Handler class
        $this->keyword_handler = new GuttyRelatedPosts_Keyword_Handler();

        // Register hooks
        add_action('init', [$this, 'register_related_posts_block']);
        add_action('enqueue_block_assets', [$this, 'enqueue_block_assets']);
        add_filter('pre_render_block', [$this, 'custom_related_posts_render'], 10, 3);
        add_filter('rest_post_query', [$this, 'handle_rest_post_query'], 10, 2);
    }

    public function register_related_posts_block() {
        // Register the main related posts block
        register_block_type(GUTTY_RELATED_POSTS_PLUGIN_DIR . 'build/blocks/related-posts-block/block.json');
    }

    public function enqueue_block_assets() {
        // Enqueue assets for the blocks
        $relatedPostItemAsset = include(GUTTY_RELATED_POSTS_PLUGIN_DIR . 'build/blocks/related-posts-block/block.asset.php');
    }

    public function custom_related_posts_render($pre_render, $block, $parent_block) {
        $namespace = $block['attrs']['namespace'] ?? '';
        if ($namespace === 'gutty/related-posts-query') {
            add_filter('query_loop_block_query_vars', function($query) use ($block) {
				$post__in = $block['attrs']['query']['postIn'];
				// FSE
				if (empty($post__in)) {
					$post__in = [];
					$related_posts = $this->keyword_handler->get_related_posts(get_the_ID(), 10);
					foreach($related_posts as $post) {
						$post__in[] = $post->ID;
					}
				}

                if (!empty($post__in)) {
                    $query['post__in'] = $post__in;
                }

                return $query;
            });
        }
        return $pre_render;
    }

    public function handle_rest_post_query($args, $request) {
        $post__in = $request->get_param('postIn') ?? [];
        if (!empty($post__in)) {
            $args['post__in'] = $post__in;
        }
        return $args;
    }

    public function get_related_posts($post_in = null, $number = 5) {
        // If no post IDs are provided, fetch related posts for the current post
        if ($post_in === null) {
            return $this->keyword_handler->get_related_posts(get_the_ID(), $number);
        }
    
        // Convert post_in string to an array of integers
        $post_ids = array_map('intval', explode(',', $post_in));
        
        // Get related posts based on the provided IDs
        if (empty($post_ids)) {
            return [];
        }
    
        // Use your existing logic to fetch related posts
        $related_posts = $this->keyword_handler->get_related_posts(get_the_ID(), $number);
        
        // If specific IDs are provided, filter the results
        return array_filter($related_posts, function($post) use ($post_ids) {
            return in_array($post->ID, $post_ids);
        });
    }    
}

// Initialize the GuttyRelatedPosts_Block class
new GuttyRelatedPosts_Block();
