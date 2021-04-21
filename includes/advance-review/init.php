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

require_once 'class-comment.php';
require_once 'class-interaction.php';
require_once 'class-review-data.php';

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

// Load comment walker
function load_review_walker() {
	if ( is_singular( ATBDP_POST_TYPE ) && comments_open() ) {
		require_once ATBDP_INC_DIR . 'advance-review/class-review-walker.php';
	}
}
add_action( 'template_redirect', __NAMESPACE__ . '\load_review_walker' );

function get_rating_markup( $label, $subname = '' ) {
	$name     = 'rating';
	$selected = isset( $_REQUEST['rating'] ) ? $_REQUEST['rating'] : '';

	if ( is_criteria_enabled() && $subname ) {
		$name .= "[{$subname}]";
		$selected = isset( $_REQUEST['rating'], $_REQUEST['rating'][ $subname ] ) ? $_REQUEST['rating'][ $subname ] : '';
	}

	ob_start();
	?>
	<div class="directorist-review-criteria__single">
		<span class="directorist-review-criteria__single__label"><?php echo esc_html( $label ); ?></span>
		<select required="required" name="<?php echo esc_attr( $name ); ?>" class="directorist-review-criteria-select">
			<option value=""><?php esc_html_e( 'Rate...', 'directorist' ); ?></option>
			<option <?php selected( $selected, '1' ); ?> value="1"><?php esc_html_e( 'Very poor', 'directorist' ); ?></option>
			<option <?php selected( $selected, '2' ); ?> value="2"><?php esc_html_e( 'Not that bad', 'directorist' ); ?></option>
			<option <?php selected( $selected, '3' ); ?> value="3"><?php esc_html_e( 'Average', 'directorist' ); ?></option>
			<option <?php selected( $selected, '4' ); ?> value="4"><?php esc_html_e( 'Good', 'directorist' ); ?></option>
			<option <?php selected( $selected, '5' ); ?> value="5"><?php esc_html_e( 'Perfect', 'directorist' ); ?></option>
		</select>
	</div><!-- ends: .directorist-review-criteria__one -->
	<?php
	return ob_get_clean();
}

function get_media_uploader_markup() {
	$accepted_types = array(
		'image/jpeg',
		'image/jpg',
		'image/png',
	);

	$uid = uniqid( 'directorist-' );

	ob_start();
	?>
	<div class="directorist-form-group directorist-review-media-upload">
		<input class="directorist-review-images" type="file" accept="<?php echo implode( ',', $accepted_types ); ?>" name="review_images[]" id="<?php echo $uid; ?>" multiple="multiple">
		<label for="<?php echo $uid; ?>">
			<i class="far fa-image"></i>
			<span><?php esc_html_e( 'Add a photo', 'diretorist' ); ?></span>
		</label>
		<div class="directorist-review-img-gallery"></div>
	</div>
	<?php
	return ob_get_clean();
}

function is_criteria_enabled() {
	return count( get_criteria_names() ) > 0;
}

function get_criteria_names() {
	// return [];
	return array(
		'food'     => 'Food',
		'location' => 'Location',
		'service'  => 'Service',
		'quality'  => 'Quality',
		'price'    => 'Price',
	);
}
