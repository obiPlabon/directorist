<?php
/**
 * Advance review comment class
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Comment {

	public static function init() {
		add_filter( 'preprocess_comment', array( __CLASS__, 'check_review_rating' ), 0 );

		// Rating posts.
		// add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		// add_filter( 'preprocess_comment', array( __CLASS__, 'check_comment_rating' ), 0 );
		// add_action( 'comment_post', array( __CLASS__, 'add_comment_rating' ), 1 );
		// add_action( 'comment_moderation_recipients', array( __CLASS__, 'comment_moderation_recipients' ), 10, 2 );

		add_action( 'comment_post', array( __CLASS__, 'save_review_data' ) , 10, 3 );

		// Set comment type.
		add_action( 'preprocess_comment', array( __CLASS__, 'update_comment_type' ), 1 );
	}

	public static function update_comment_type( $comment_data ) {
		if ( ! is_admin() &&
			isset( $_POST['comment_post_ID'], $_POST['comment_parent'], $_POST['rating'], $comment_data['comment_type'] ) &&
			ATBDP_POST_TYPE === get_post_type( absint( $_POST['comment_post_ID'] ) ) &&
			$comment_data['comment_parent'] === 0 &&
			self::is_default_comment_type( $comment_data['comment_type'] ) &&
			( ! empty( $_POST['rating'] ) || ( is_criteria_enabled() && count( array_filter( $_POST['rating'] ) ) > 0 ) ) ) {
			$comment_data['comment_type'] = 'review';
		}

		return $comment_data;
	}

	/**
	 * Determines if a comment is of the default type.
	 *
	 * Prior to WordPress 5.5, '' was the default comment type.
	 * As of 5.5, the default type is 'comment'.
	 *
	 * @param string $comment_type Comment type.
	 * @return bool
	 */
	private static function is_default_comment_type( $comment_type ) {
		return ( '' === $comment_type || 'comment' === $comment_type );
	}

	public static function save_review_data( $comment_ID, $comment_approved, $commentdata ) {
		self::save_rating( $comment_ID, $commentdata );
	}

	protected static function save_rating( $comment_ID, $commentdata ) {
		if ( $commentdata['comment_type'] !== 'review' || empty( $_POST['rating'] ) ) {
			return;
		}

		if ( is_array( $_POST['rating'] ) ) {
			if ( is_criteria_enabled() ) {
				$_criteria = array();

				foreach( get_criteria_names() as $criterion_key => $criterion_name ) {
					if ( empty( $_POST['rating'][ $criterion_key ] ) ) {
						continue;
					}

					$_criteria[ $criterion_key ] = absint( $_POST['rating'][ $criterion_key ] );
				}

				$_criteria = array_map( 'intval', $_criteria );
				$_total    = array_sum( $_criteria );
				$avg       = number_format( $_total / count( $_criteria ), 2, '.', '' );

				update_comment_meta( $comment_ID, 'criteria_rating', $_criteria );
			} else {
				$rating = current( $_POST['rating'] );
				$rating = number_format( intval( $rating ), 2, '.', '' );
			}
		} else {
			$rating = number_format( intval( $_POST['rating'] ), 2, '.', '' );
		}

		update_comment_meta( $comment_ID, 'rating', $rating );
	}

	/**
	 * Validate the review ratings.
	 *
	 * @param  array $comment_data Comment data.
	 * @return array
	 */
	public static function check_review_rating( $comment_data ) {
		// If posting a comment (not trackback etc) and not logged in.
		if ( ! is_admin() &&
			isset( $_POST['comment_post_ID'], $_POST['comment_parent'], $_POST['rating'], $comment_data['comment_type'] ) &&
			ATBDP_POST_TYPE === get_post_type( absint( $_POST['comment_post_ID'] ) ) &&
			$comment_data['comment_parent'] === 0 &&
			self::is_default_comment_type( $comment_data['comment_type'] ) &&
			( empty( $_POST['rating'] ) || ( is_criteria_enabled() && count( array_filter( $_POST['rating'] ) ) < 1 ) ) ) {

			wp_die( __( '<strong>Error</strong>: Please rate the listing.', 'directorist' ) );
			exit;
		}

		return $comment_data;
	}

	public static function get_rating( $comment_id ) {
		return (float) get_comment_meta( $comment_id, 'rating', true );
	}

	public static function get_criteria_rating( $comment_id ) {
		if ( is_criteria_enabled() ) {
			$criteria_rating = get_comment_meta( $comment_id, 'criteria_rating', true );

			return ! empty( $criteria_rating ) ? array_map( 'intval', $criteria_rating ) : [];
		}

		return [];
	}
}

Comment::init();
