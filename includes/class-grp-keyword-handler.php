<?php

namespace GuttyRelatedPosts\KeywordHandler;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use GuttyRelatedPosts\StopWords\StopWords;

class GuttyRelatedPosts_Keyword_Handler {
    private StopWords $StopWords;

    public function __construct() {
        $this->StopWords = new StopWords();

        // Hook into post save to extract keywords
        add_action('save_post', [$this, 'process_post'], 10, 3);
    }

    // Create custom table for storing keywords on plugin activation
    public function create_keyword_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'gutty_related_posts_keywords';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            keyword varchar(255) NOT NULL,
            tf_idf float NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY keyword (keyword)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Process post to extract and store keywords
    public function process_post($post_id, $post, $update) {
        // Avoid recursion
        remove_action('save_post', [$this, 'process_post'], 10);

        // Only process published posts
        if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
            add_action('save_post', [$this, 'process_post'], 10, 3);
            return;
        }

        // Get post content
        $content = get_post_field('post_content', $post_id);

        // Extract keywords
        $keywords = $this->extract_keywords($post_id, $content);

        // Store keywords in the database
        $this->store_keywords($post_id, $keywords);

        // Re-hook the action
        add_action('save_post', [$this, 'process_post'], 10, 3);
    }

    // Extract keywords using TF-IDF
    private function extract_keywords($post_id, $content) { 
        
        // Load stop words from the JSON file
        $stop_words = $this->StopWords->get_stop_words();
    
        // Split content into words and convert to lowercase
        $words = preg_split('/\s+/', strtolower($content));
    
        // Filter out stop words using the is_stop_word method
        $filtered_words = array_filter($words, function($word) {
            return !$this->StopWords->is_stop_word($word);
        });
    
        // Apply stemming
        $stemmed_words = array_map([$this, 'stem_word'], $filtered_words);
    
        // Remove empty strings and non-alphabetic characters
        $stemmed_words = array_filter($stemmed_words, function($word) {
            return !empty($word) && preg_match('/^[a-z]+$/', $word);
        });
    
        // Count word frequency
        $word_count = array_count_values($stemmed_words);
        arsort($word_count);
    
        // Select top 20 keywords based on frequency
        $top_keywords = array_slice($word_count, 0, 20, true);
    
        // Calculate TF-IDF for each keyword
        $keywords_with_tfidf = [];
        foreach ($top_keywords as $keyword => $count) {
            $tf_idf = $this->calculate_tfidf($keyword, $word_count);
            $keywords_with_tfidf[$keyword] = $tf_idf;
        }
    
        return $keywords_with_tfidf;
    }    

    // Basic stemming implementation
    private function stem_word($word) {
        $suffixes = ['ing', 'ed', 'ly', 'es', 's', 'ment'];
        foreach ($suffixes as $suffix) {
            if (substr($word, -strlen($suffix)) === $suffix && strlen($word) > strlen($suffix) + 2) {
                return substr($word, 0, -strlen($suffix));
            }
        }
        return $word;
    }

    // Calculate TF-IDF
    private function calculate_tfidf($keyword, $word_count) {
        global $wpdb;

        // Term Frequency (TF)
        $tf = isset($word_count[$keyword]) ? $word_count[$keyword] / array_sum($word_count) : 0;

        // Document Frequency (DF)
        $df = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}gutty_related_posts_keywords WHERE keyword = %s",
            $keyword
        ));

        // Total Documents
        $total_documents = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");

        // Prevent division by zero
        if ($df == 0) {
            $df = 1;
        }

        // Inverse Document Frequency (IDF)
        $idf = log($total_documents / $df);

        // TF-IDF
        $tf_idf = $tf * $idf;

        return $tf_idf;
    }

    // Store keywords in the database
    private function store_keywords($post_id, $keywords) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gutty_related_posts_keywords';

        // Delete existing keywords for the post to avoid duplicates
        $wpdb->delete($table_name, ['post_id' => $post_id], ['%d']);

        // Prepare data for bulk insert
        $insert_data = [];
        foreach ($keywords as $keyword => $tf_idf) {
            $insert_data[] = [
                'post_id' => $post_id,
                'keyword' => $keyword,
                'tf_idf'  => $tf_idf,
            ];
        }

        if (!empty($insert_data)) {
            foreach ($insert_data as $data) {
                $wpdb->insert(
                    $table_name,
                    $data,
                    ['%d', '%s', '%f']
                );
            }
        }
    }

    // Method to process all published posts (for manual generation)
    public function process_all_posts() {
        // Query to fetch all published posts
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'numberposts'    => -1,
            'fields'         => 'ids',
        ]);

        if (!empty($posts)) {
            foreach ($posts as $post_id) {
                $content = get_post_field('post_content', $post_id);

                // Extract keywords
                $keywords = $this->extract_keywords($post_id, $content);

                // Store keywords in the database
                $this->store_keywords($post_id, $keywords);
            }
        }
    }

    // Function to fetch related posts based on keywords using TF-IDF
    public function get_related_posts($post_id, $num_posts = 5) {
        global $wpdb;

        // Get keywords for the current post
        $keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT keyword FROM {$wpdb->prefix}gutty_related_posts_keywords WHERE post_id = %d ORDER BY tf_idf DESC LIMIT 20",
            $post_id
        ));

        if (empty($keywords)) {
            return [];
        }

        // Prepare keywords array for SQL query
        $keyword_list = array_map(function($keyword_obj) {
            return $keyword_obj->keyword;
        }, $keywords);

        // Ensure there are keywords before proceeding
        if (empty($keyword_list)) {
            return [];
        }

        $related_posts = $wpdb->get_results($wpdb->prepare(
            "
            SELECT DISTINCT p.ID, p.post_title 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}gutty_related_posts_keywords k ON p.ID = k.post_id
            WHERE k.keyword IN (" . implode(',', array_fill(0, count($keyword_list), '%s')) . ")
            AND p.ID != %d
            AND p.post_status = 'publish'
            ORDER BY RAND()
            LIMIT %d
            ",
            array_merge($keyword_list, [$post_id, $num_posts])
        ));

        return $related_posts;
    }
}

// Initialize the GRP Keyword Handler Class
new GuttyRelatedPosts_Keyword_Handler();
