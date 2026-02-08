<?php
/**
 * Plugin Name: VKFS Search Result Display Controller
 * Description: Provides a block that conditionally displays inner content based on VK Filter Search URL query parameters.
 * Version: 0.1.0
 * Author: NExT-Season
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function vkfs_src_register_block() {
    $dir = __DIR__;

    // Editor script
    $script_path = $dir . '/block.js';
    wp_register_script(
        'vkfs-src-block-editor',
        plugins_url( 'block.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ),
        file_exists( $script_path ) ? filemtime( $script_path ) : false
    );

    register_block_type( 'vkfs/search-result-controller', array(
        'editor_script'   => 'vkfs-src-block-editor',
        'render_callback' => 'vkfs_src_render_block',
        'attributes'      => array(
            'vkfs_post_type'     => array('type' => 'string'),
            'category_name'      => array('type' => 'string'),
            'keyword'            => array('type' => 'string'),
            'category_operator'  => array('type' => 'string'),
        ),
    ) );
}
add_action( 'init', 'vkfs_src_register_block' );

function vkfs_src_render_block( $attributes, $content ) {
    // Sanitize GET parameters
    $params = array_map( 'sanitize_text_field', wp_unslash( $_GET ) );

    // Debug output only when WP_DEBUG is enabled
    $debug = '';
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $debug = "\n<!-- VKFS Debug:\n";
        $debug .= "Attributes: " . esc_html( wp_json_encode( $attributes ) ) . "\n";
        $debug .= "GET params: " . esc_html( wp_json_encode( $params ) ) . "\n";
    }
    
    // If no conditions set, show content
    $hasCondition = false;
    foreach ( $attributes as $k => $v ) {
        if ( is_string( $v ) && $v !== '' ) {
            $hasCondition = true;
            break;
        }
    }
    
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $debug .= "Has condition: " . ( $hasCondition ? 'yes' : 'no' ) . "\n";
    }

    if ( ! $hasCondition ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $debug .= "Result: Show content (no conditions set)\n-->\n";
        }
        return $content . $debug;
    }

    // mapping attribute key => query param key
    $map = array(
        'vkfs_post_type'    => 'vkfs_post_type',
        'category_name'     => 'category_name',
        'keyword'           => 'keyword',
        'category_operator' => 'category_operator',
    );

    // Check attribute mappings
    foreach ( $map as $attr => $qp ) {
        if ( isset( $attributes[ $attr ] ) && $attributes[ $attr ] !== '' ) {
            $expected = (string) $attributes[ $attr ];
            $actual = isset( $params[ $qp ] ) ? (string) $params[ $qp ] : '';

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $debug .= sprintf(
                    "Checking %s (%s): expected='%s', actual='%s'\n",
                    esc_html( $attr ),
                    esc_html( $qp ),
                    esc_html( $expected ),
                    esc_html( $actual )
                );
            }

            // basic comparison (exact match) - case insensitive for category names
            if ( 'category_name' === $qp ) {
                // For category name, compare case-insensitively
                if ( strtolower( $expected ) !== strtolower( $actual ) ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        $debug .= sprintf( "Result: Hide (mismatch on %s)\n-->\n", esc_html( $qp ) );
                    }
                    return $debug;
                }
            } else {
                if ( $expected !== $actual ) {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        $debug .= sprintf( "Result: Hide (mismatch on %s)\n-->\n", esc_html( $qp ) );
                    }
                    return $debug;
                }
            }
        }
    }

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $debug .= "Result: Show content (all conditions match)\n-->\n";
    }
    return $content . $debug;
}

/**
 * Parse blocks from post_content and extract search-item candidates.
 * Each candidate will include both display label and the query parameter name it maps to.
 */
function vkfs_src_parse_form_candidates( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'filter-search' !== $post->post_type ) {
        return array();
    }

    $candidates = array();
    if ( function_exists( 'parse_blocks' ) ) {
        $blocks = parse_blocks( $post->post_content );
        foreach ( $blocks as $block ) {
            if ( ! isset( $block['blockName'] ) ) {
                continue;
            }

            $block_name = $block['blockName'];
            $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

            if ( 'vk-filter-search/keyword-search' === $block_name ) {
                $candidates[] = array(
                    'label'     => 'Keyword',
                    'queryName' => 'keyword',
                    'blockName' => $block_name,
                );
            } elseif ( 'vk-filter-search/post-type-search' === $block_name ) {
                $candidates[] = array(
                    'label'     => 'Post Type',
                    'queryName' => 'vkfs_post_type',
                    'blockName' => $block_name,
                );
            } elseif ( 'vk-filter-search/taxonomy-search' === $block_name ) {
                $tax = isset( $attrs['isSelectedTaxonomy'] ) ? $attrs['isSelectedTaxonomy'] : 'category';
                $candidates[] = array(
                    'label'     => 'Taxonomy: ' . $tax,
                    'queryName' => $tax,
                    'blockName' => $block_name,
                );
            }
        }
    }

    return $candidates;
}

/**
 * Provide filter-search posts data to the block editor so the user can
 * select a form and inspect its search items in the editor UI.
 */
function vkfs_src_set_call_filter_search_data() {
    // only for block editor
    if ( ! wp_doing_ajax() && ! is_admin() ) {
        return;
    }

    // ensure our editor script is registered
    if ( ! wp_script_is( 'vkfs-src-block-editor', 'registered' ) ) {
        return;
    }

    $posts = get_posts( array(
        'post_type'   => 'filter-search',
        'numberposts' => -1,
    ) );

    $target_posts = array();
    $posts_map = array();

    if ( $posts ) {
        foreach ( $posts as $p ) {
            $target_posts[] = array(
                'label' => get_the_title( $p ),
                'value' => (int) $p->ID,
            );
            // provide raw post_content so the editor JS can parse inner blocks
            $posts_map[ (int) $p->ID ] = $p->post_content;
        }
    }

    $data = array(
        'hasFilterSearchPosts' => ! empty( $target_posts ),
        'targetPosts' => $target_posts,
        'filterSearchPosts' => $posts_map,
    );

    wp_localize_script( 'vkfs-src-block-editor', 'vkfsCallFilterSearch', $data );
}
add_action( 'enqueue_block_editor_assets', 'vkfs_src_set_call_filter_search_data' );
