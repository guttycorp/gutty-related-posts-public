<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register shortcode for related posts
function gutty_related_posts_shortcode($atts) {
    wp_enqueue_style('gutty-related-posts-shortcode');

    // Get the saved settings or default values
    $options = get_option('guttyrelatedposts_settings', []);
    $number_of_posts = !empty($options['guttyrelatedposts_number_of_posts']) ? absint($options['guttyrelatedposts_number_of_posts']) : 5;
    $image_size = !empty($options['guttyrelatedposts_image_size']) ? $options['guttyrelatedposts_image_size'] : 'thumbnail';
    $default_title = !empty($options['guttyrelatedposts_default_title']) ? $options['guttyrelatedposts_default_title'] : __('Related Posts', 'gutty-related-posts');
    $read_more_text = !empty($options['guttyrelatedposts_read_more_text']) ? $options['guttyrelatedposts_read_more_text'] : __('Read more', 'gutty-related-posts');
    $none_posts_text = !empty($options['guttyrelatedposts_none_posts_text']) ? $options['guttyrelatedposts_none_posts_text'] : __('No related posts found.', 'gutty-related-posts');

    // Override number of posts with shortcode attribute if provided
    $atts = shortcode_atts(
        array(
            'number' => $number_of_posts,
        ),
        $atts,
        'gutty_related_posts'
    );

    // Get related posts using the related posts block class
    $related_posts_block = new GuttyRelatedPosts\Block\GuttyRelatedPosts_Block();
    $related_posts = $related_posts_block->get_related_posts(null, $atts['number']);

    ob_start();

    if (!empty($related_posts)) {
        ?>
        <section class="gutty-related-posts">
            <h2 class="gutty-related-posts-section-title"><?php echo esc_html($default_title); ?></h2>
            <div class="gutty-related-posts-items">
                <?php foreach ($related_posts as $post) {
                    setup_postdata($post);

                    $thumbnail = get_the_post_thumbnail($post->ID, $image_size);
                    $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt($post->ID)), 15);
                    ?>
                    <article>
                        <?php if (has_post_thumbnail($post->ID)) { ?>
                            <figure><a href="<?php echo esc_url(get_permalink($post->ID)); ?>" target="_self"><?php echo wp_kses_post($thumbnail); ?></a></figure>
                        <?php } ?>
                        <div class="gutty-related-posts-item">
                            <h3 class="gutty-related-posts-item-title"><a href="<?php echo esc_url(get_permalink($post->ID)); ?>" target="_self"><?php echo esc_html(get_the_title($post->ID)); ?></a></h3>
                            <p class="gutty-related-posts-item-excerpt"><?php echo esc_html($excerpt); ?></p>
                            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="gutty-related-posts-item-read-more"><?php echo esc_html($read_more_text); ?></a>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </section>
        <?php
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html($none_posts_text) . '</p>';
    }

    return ob_get_clean();
}
add_shortcode('gutty_related_posts', 'gutty_related_posts_shortcode');
