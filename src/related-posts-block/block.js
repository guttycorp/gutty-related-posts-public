import apiFetch from '@wordpress/api-fetch';
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Register related posts query block variation
registerBlockVariation('core/query', {
    name: 'gutty/related-posts-query',
    title: __('Related Posts', 'gutty-related-posts'),
    category: 'theme',
    isActive: ['namespace'],
    attributes: {
        namespace: 'gutty/related-posts-query',
        query: {
            perPage: 3,
            postType: 'post',
        },
        
    },
    allowedControls: [
          'order',
          'author',
          'postType',
          'sticky',
          'taxQuery'  
    ],
    innerBlocks: [
        [
            'core/heading',
            {
                level: 2,
                content: __('Related Posts', 'gutty-related-posts'),
            },
        ],
        [
            'core/query-no-results',
            {},
            [
                ['core/paragraph', { content: __('No related posts were found.', 'gutty-related-posts')}],
            ]
        ],
        [
            'core/post-template',
            {},
            [
                ['core/post-featured-image', { isLink: true }],
                ['core/post-title', { level: 3, isLink: true }],
                ['core/post-excerpt', { moreText: __('Read More', 'gutty-related-posts') }],
            ],
        ],
    ],
});

export const queryCustomProps = (BlockEdit) => (props) => {
    if (props.name === 'core/query' && props.attributes.namespace === 'gutty/related-posts-query') {
        const { query = {} } = props.attributes;

        // Only fetch related posts if 'postIn' is not set
        if (!query.postIn) {
            const postId = wp.data.select('core/editor').getCurrentPostId();
            const numPosts = query.perPage || 3;

            // Fetch related posts from the API
            apiFetch({ path: `/grp/v1/gutty-related-posts?numPosts=${numPosts}&post_id=${postId}` })
                .then((posts) => {
                    // Map related post IDs and update query attributes
                    props.setAttributes({ query: { ...query, postIn: posts.map((p) => p.id) } });
                })
                .catch(() => {
                    // Set empty postIn on error
                    props.setAttributes({ query: { ...query, postIn: [] } });
                });
        }
    }

    return <BlockEdit key="edit" {...props} />;
};

// Add filter to apply custom props to BlockEdit
wp.hooks.addFilter('editor.BlockEdit', 'gutty/related-posts', queryCustomProps);
