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

use function wpWax\Directorist\Review\get_criteria_names;
use function wpWax\Directorist\Review\is_criteria_enabled;

class Comment {

	public static function init() {
		// Rating posts.
		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		add_filter( 'preprocess_comment', array( __CLASS__, 'check_review_rating' ), 0 );
		add_action( 'comment_post', array( __CLASS__, 'save_review_data' ) , 10, 3 );

		// Support avatars for `review` comment type.
		add_filter( 'get_avatar_comment_types', array( __CLASS__, 'add_avatar_for_review_comment_type' ) );

		// Clear transients.
		add_action( 'wp_update_comment_count', array( __CLASS__, 'clear_transients' ) );

		// Set comment type.
		add_action( 'preprocess_comment', array( __CLASS__, 'update_comment_type' ), 1 );

		// Count comments.
		add_filter( 'wp_count_comments', array( __CLASS__, 'wp_count_comments' ), 10, 2 );

		// Delete comments count cache whenever there is a new comment or a comment status changes.
		add_action( 'wp_insert_comment', array( __CLASS__, 'delete_comments_count_cache' ) );
		add_action( 'wp_set_comment_status', array( __CLASS__, 'delete_comments_count_cache' ) );
	}

	/**
	 * See if comments are open.
	 *
	 * @param  bool $open    Whether the current post is open for comments.
	 * @param  int  $post_id Post ID.
	 * @return bool
	 */
	public static function comments_open( $open, $post_id ) {
		if ( ATBDP_POST_TYPE === get_post_type( $post_id ) && ! post_type_supports( ATBDP_POST_TYPE, 'comments' ) ) {
			$open = false;
		}
		return $open;
	}

	/**
	 * Delete comments count cache whenever there is
	 * new comment or the status of a comment changes. Cache
	 * will be regenerated next time Comment::wp_count_comments()
	 * is called.
	 */
	public static function delete_comments_count_cache() {
		delete_transient( 'directorist_count_comments' );
	}

	/**
	 * Remove order notes and webhook delivery logs from wp_count_comments().
	 *
	 * @since  2.2
	 * @param  object $stats   Comment stats.
	 * @param  int    $post_id Post ID.
	 * @return object
	 */
	public static function wp_count_comments( $stats, $post_id ) {
		global $wpdb;

		if ( 0 === $post_id ) {
			$stats = get_transient( 'directorist_count_comments' );

			if ( ! $stats ) {
				$stats = array(
					'total_comments' => 0,
					'all'            => 0,
				);

				$count = $wpdb->get_results(
					"
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('action_log')
					GROUP BY comment_approved
					",
					ARRAY_A
				);

				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash', 'spam' ), true ) ) {
						$stats['all']            += $row['num_comments'];
						$stats['total_comments'] += $row['num_comments'];
					} elseif ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash' ), true ) ) {
						$stats['total_comments'] += $row['num_comments'];
					}
					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}
				}

				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0;
					}
				}

				$stats = (object) $stats;
				set_transient( 'directorist_count_comments', $stats );
			}
		}

		return $stats;
	}

	/**
	 * Make sure WP displays avatars for comments with the `review` type.
	 *
	 * @param  array $comment_types Comment types.
	 * @return array
	 */
	public static function add_avatar_for_review_comment_type( $comment_types ) {
		return array_merge( $comment_types, array( 'review' ) );
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
		$post_id = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0; // WPCS: input var ok, CSRF ok.

		if ( isset( $_POST['comment_post_ID'] ) && ATBDP_POST_TYPE === get_post_type( $post_id ) ) {
			self::save_rating( $comment_ID, $commentdata );
			self::save_media( $comment_ID, $commentdata );

			if ( $post_id ) {
				self::clear_transients( $post_id );
			}
		}
	}

	/**
	 * Ensure listing average rating and review count is kept up to date.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_transients( $post_id ) {
		if ( ATBDP_POST_TYPE === get_post_type( $post_id ) ) {
			// Make sure to maintain the sequence. Update review count before updating the rating
			Review_Data::update_rating_counts( $post_id, self::get_rating_counts_for_listing( $post_id ) );
			Review_Data::update_review_count( $post_id, self::get_review_count_for_listing( $post_id ) );
			Review_Data::update_rating( $post_id, self::get_average_rating_for_listing( $post_id ) );
			Review_Data::update_criteria_rating( $post_id, self::get_criteria_rating_for_listing( $post_id ) );
		}
	}

	/**
	 * Get listing review count for a listing (not replies). Please note this is not cached.
	 *
	 * @param int $post_id.
	 * @return int
	 */
	public static function get_review_count_for_listing( $post_id ) {
		$counts = self::get_review_counts_for_listing_ids( array( $post_id ) );

		return $counts[ $post_id ];
	}

	/**
	 * Get listing rating count for a directory listing. Please note this is not cached.
	 *
	 * @param $post_id.
	 * @return int[]
	 */
	public static function get_rating_counts_for_listing( $post_id ) {
		global $wpdb;

		$counts     = array();
		$raw_counts = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT meta_value, COUNT( * ) as meta_value_count FROM $wpdb->commentmeta
			LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
			WHERE meta_key = 'rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value > 0
			GROUP BY meta_value
				",
				$post_id
			)
		);

		foreach ( $raw_counts as $count ) {
			$counts[ $count->meta_value ] = absint( $count->meta_value_count ); // WPCS: slow query ok.
		}

		return $counts;
	}

	/**
	 * Get listing rating for a listing. Please note this is not cached.
	 *
	 * @param $post_id.
	 * @return float
	 */
	public static function get_average_rating_for_listing( $post_id ) {
		global $wpdb;

		$count = Review_Data::get_review_count( $post_id );

		if ( $count ) {
			$ratings = $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
				AND meta_value > 0
					",
					$post_id
				)
			);
			$average = number_format( $ratings / $count, 2, '.', '' );
		} else {
			$average = 0;
		}

		return $average;
	}

	/**
	 * Utility function for getting review counts for multiple listings in one query. This is not cached.
	 *
	 * @param array $listing_ids Array of listing IDs.
	 *
	 * @return array
	 */
	public static function get_review_counts_for_listing_ids( $listing_ids ) {
		global $wpdb;

		if ( empty( $listing_ids ) ) {
			return array();
		}

		$listing_id_string_placeholder = substr( str_repeat( ',%s', count( $listing_ids ) ), 1 );

		$review_counts = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Ignored for allowing interpolation in IN query.
			$wpdb->prepare(
				"
					SELECT comment_post_ID as listing_id, COUNT( comment_post_ID ) as review_count
					FROM $wpdb->comments
					WHERE
						comment_parent = 0
						AND comment_post_ID IN ( $listing_id_string_placeholder )
						AND comment_approved = '1'
						AND comment_type in ( 'review', '', 'comment' )
					GROUP BY listing_id
				",
				$listing_ids
			),
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared.
			ARRAY_A
		);

		// Convert to key value pairs.
		$counts = array_replace( array_fill_keys( $listing_ids, 0 ), array_column( $review_counts, 'review_count', 'listing_id' ) );

		return $counts;
	}

	private static function get_criteria_rating_for_listing( $post_id ) {
		if ( ! is_criteria_enabled() ) {
			return array();
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT meta_value FROM $wpdb->commentmeta
			LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
			WHERE meta_key = 'criteria_rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value != ''
				",
				$post_id
			)
		);

		if ( empty( $results ) ) {
			return array();
		}

		$results = array_map( function( $row ) {
			return maybe_unserialize( $row->meta_value );
		}, $results );

		$rating_map = array();

		foreach ( get_criteria_names() as $criteria_key => $criteria_name ) {
			$criteria = array_column( $results, $criteria_key );

			if ( $criteria ) {
				$rating_map[ $criteria_key ] = number_format( array_sum( $criteria ) / count( $criteria ), 2, '.', '' );
			}
		}

		return $rating_map;
	}

	private static function save_rating( $comment_ID, $commentdata ) {
		if ( $commentdata['comment_type'] !== 'review' || empty( $_POST['rating'] ) ) {
			return;
		}

		if ( is_array( $_POST['rating'] ) && is_criteria_enabled() ) {
			$_criteria = array();

			foreach( get_criteria_names() as $criterion_key => $criterion_name ) {
				if ( empty( $_POST['rating'][ $criterion_key ] ) ) {
					continue;
				}

				$_criteria[ $criterion_key ] = absint( $_POST['rating'][ $criterion_key ] );
			}

			$_criteria      = array_map( 'intval', $_criteria );
			$criteria_total = array_sum( $_criteria );
			$criteria_count = count( $_criteria );
			$rating         = number_format( $criteria_total / $criteria_count, 2, '.', '' );

			add_comment_meta( $comment_ID, 'criteria_rating', $_criteria, true );
		} else if ( is_array( $_POST['rating'] ) && ! is_criteria_enabled() ) {
			$rating = current( $_POST['rating'] );
			$rating = number_format( intval( $rating ), 2, '.', '' );
		} else {
			$rating = number_format( intval( $_POST['rating'] ), 2, '.', '' );
		}

		add_comment_meta( $comment_ID, 'rating', $rating, true );
	}

	private static function save_media( $comment_ID, $commentdata ) {
		if ( ! empty( $_FILES['review_images'] ) ) {
			$length = count( $_FILES['review_images']['name'] );

			for ( $i = 0; $i < $length; $i++ ) {
				$data = wp_upload_bits(
					$_FILES['review_images']['name'][ $i ],
					null,
					file_get_contents( $_FILES['review_images']['tmp_name'][ $i ] )
				);

				file_put_contents( __DIR__ . '/data.txt', print_r( $data, 1 ), FILE_APPEND );
			}
		}
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
