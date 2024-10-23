<?php

namespace GuttyRelatedPosts\StopWords;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class StopWords {
    private $stop_words = [];
    private $handlers = [];
    private static $stop_words_directory = GUTTY_RELATED_POSTS_STOP_WORDS; // Directory containing JSON files

    public function __construct() {
        // Get the current WordPress locale
        $locale = get_locale();
        $this->load_stop_words_by_locale($locale);
    }

    private function load_stop_words_by_locale($locale) {
        // Normalize locale to match handlers (e.g., 'en_US' -> 'en')
        $locale_parts = explode('_', $locale);
        $normalized_locale = strtolower($locale_parts[0]);
    
        // Get all JSON files in the stop words directory
        $files = glob(self::$stop_words_directory . '/*.json');
    
        // Load WordPress filesystem API
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }
    
        foreach ($files as $file) {
            // If the file path is a URL (remote), use wp_remote_get()
            if ($this->is_remote_url($file)) {
                $response = wp_remote_get($file);
                if (is_wp_error($response)) {
                    continue; // Skip to the next file if there's an error
                }
                $body = wp_remote_retrieve_body($response);
            } else {
                // For local files, use the WP_Filesystem API
                $body = $wp_filesystem->get_contents($file);
            }
    
            // Process the file content as JSON
            $data = json_decode($body, true);
    
            if (isset($data['handlers']) && isset($data['words'])) {
                // Check if the normalized locale matches any handler
                foreach ($data['handlers'] as $handler) {
                    if (strtolower($handler) === $normalized_locale) {
                        // Match found, load stop words and handlers
                        $this->stop_words = $data['words'];
                        $this->handlers = $data['handlers'];
                        return; // Exit the loop as we've found the right file
                    }
                }
            }
        }
    
        // If no match was found, load default stop words
        $this->load_default_stop_words();
    }
    
    private function is_remote_url($url) {
        // Check if the URL starts with "http" or "https"
        return (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0);
    }    

    private function load_default_stop_words() {
        // Load default English stop words
        $json_file = self::$stop_words_directory . '/english.json'; // Assuming the default is English
        $response = wp_remote_get($json_file);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['words']) && isset($data['handlers'])) {
                $this->stop_words = $data['words'];
                $this->handlers = $data['handlers'];
            }
        }
    }

    public function get_stop_words() {
        return $this->stop_words;
    }

    public function get_handlers() {
        return $this->handlers;
    }

    public function is_stop_word($word) {
        $normalized_word = strtolower($word);
        return in_array($normalized_word, $this->stop_words);
    }

    public function is_handler($handler) {
        return in_array(strtolower($handler), $this->handlers);
    }
}
