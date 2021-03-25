<?php
/**
 * Advance review system init file.
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

use Directorist\Helper;

function add_comment_support( $args, $post_type ) {
	if ( $post_type !== ATBDP_POST_TYPE ) {
		return $args;
	}

	if ( isset( $args['supports'] ) ) {
		$args['supports'] = array_merge( $args['supports'], [ 'comments' ] );
	}

	return $args;
}
add_filter( 'register_post_type_args', __NAMESPACE__ . '\add_comment_support', 10, 2 );

/**
 * Rename core meta boxes.
 */
function rename_comment_metabox() {
	global $post;

	// Comments/Reviews.
	if ( isset( $post ) && ( 'publish' === $post->post_status || 'private' === $post->post_status ) && post_type_supports( ATBDP_POST_TYPE, 'comments' ) ) {
		remove_meta_box( 'commentsdiv', ATBDP_POST_TYPE, 'normal' );
		add_meta_box( 'commentsdiv', __( 'Reviews', 'directorist' ), 'post_comment_meta_box', ATBDP_POST_TYPE, 'normal' );
	}
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\rename_comment_metabox', 20 );

function enqueue_comment_scripts() {
	if ( is_singular( ATBDP_POST_TYPE) && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_comment_scripts' );

function load_comments_template( $template ) {
	if ( get_post_type() !== ATBDP_POST_TYPE ) {
		return $template;
	}

	if ( file_exists( Helper::template_path( 'single-reviews' ) ) ) {
		return Helper::template_path( 'single-reviews' );
	}
}
add_filter( 'comments_template', __NAMESPACE__ . '\load_comments_template' );
