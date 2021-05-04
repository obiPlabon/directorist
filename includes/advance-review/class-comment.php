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
		// Rating posts.
		add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
		add_filter( 'preprocess_comment', array( __CLASS__, 'validate_comment_data' ), 0 );
		add_action( 'comment_post', array( __CLASS__, 'on_comment_post' ) , 10, 3 );

		// Support avatars for `review` comment type.
		add_filter( 'get_avatar_comment_types', array( __CLASS__, 'set_avater_comment_types' ) );

		// Clear transients.
		add_action( 'wp_update_comment_count', array( __CLASS__, 'clear_transients' ) );

		// Set comment type.
		add_filter( 'preprocess_comment', array( __CLASS__, 'update_comment_data' ), 1 );

		// Set comment approval
		add_filter( 'pre_comment_approved', array( __CLASS__, 'set_comment_status' ), 10, 2 );

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
	 * Validate the review ratings.
	 *
	 * @param  array $comment_data Comment data.
	 * @return array
	 */
	public static function validate_comment_data( $comment_data ) {
		if ( is_admin() || ! isset( $_POST['comment_post_ID'] ) || ATBDP_POST_TYPE !== get_post_type( absint( $_POST['comment_post_ID'] ) ) ) {
			return $comment_data;
		}

		// Exit when review is disabled.
		if ( ! \Directorist\Helper::is_review_enabled() ) {
			wp_die( __( '<strong>Error</strong>: Review is disabled.', 'directorist' ) );
			exit;
		}

		// Exit when guest review is disabled.
		if ( ! is_user_logged_in() && get_directorist_option( 'guest_review', 0 ) ) {
			wp_die( __( '<strong>Error</strong>: You must login to share review.', 'directorist' ) );
			exit;
		}

		$builder = Builder::get( absint( $_POST['comment_post_ID'] ) );
		$errors  = array();

		if ( $builder->is_field_active( 'rating' ) && isset( $_POST['comment_parent'], $_POST['rating'], $comment_data['comment_type'] ) &&
			$comment_data['comment_parent'] === 0 && self::is_default_comment_type( $comment_data['comment_type'] ) ) {

			$rating_missing = ( empty( $_POST['rating'] ) || ( $builder->rating_criteria_exists() && count( array_filter( $_POST['rating'] ) ) < 1 ) );

			// Validate review is shared or not
			if ( $builder->get_field_prop( 'rating', 'required', true ) && $rating_missing ) {
				$errors[] = __( '<strong>Error</strong>: Please share review rating.', 'directorist' );
			}

			// Validate owner is sharing review or not
			$post_author_id = (int) get_post_field( 'post_author', absint( $_POST['comment_post_ID'] ) );

			if ( ! $rating_missing && ! get_directorist_option( 'enable_owner_review' ) && $post_author_id === $comment_data['user_ID'] ) {
				$errors[] = __( '<strong>Error</strong>: You are not allowed to share review on your own listing.', 'directorist' );
			}

			// Validate if sharing multiple reviews
			if ( ! $rating_missing && self::review_exists_by( $comment_data['user_ID'], absint( $_POST['comment_post_ID'] ) ) ) {
				$errors[] = __( '<strong>Error</strong>: Sharing multiple reviews is not allowed.', 'directorist' );
			}

			if ( count( $errors ) ) {
				wp_die( implode( "<br>", $errors ) );
				exit;
			}
		}

		if ( $builder->is_field_active( 'media' ) && self::has_attachments() ) {
			$size            = array_sum( $_FILES['review_attachments']['size'] );
			$types           = $_FILES['review_attachments']['type'];
			$allowed_size    = $builder->get_media_max_upload_size();
			$ignorable_types = array_diff( $types, $builder->get_accepted_media() );

			if ( count( $ignorable_types ) > 0 ) {
				wp_die( __( '<strong>Error</strong>: Uploaded file(s) has an unsupported file type.', 'directorist' ) );
				exit;
			}

			if ( $size > $allowed_size ) {
				wp_die( sprintf(
					__( '<strong>Error</strong>: Uploaded image(s) size (%1$s) exceeds the limit (%2$s).', 'directorist' ),
					size_format( $size, 2 ),
					size_format( $allowed_size )
				) );
				exit;
			}
		}

		return $comment_data;
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
	public static function set_avater_comment_types( $comment_types ) {
		return array_merge( $comment_types, array( 'review' ) );
	}

	public static function update_comment_data( $comment_data ) {
		if ( is_admin() || ! isset( $_POST['comment_post_ID'] ) || ATBDP_POST_TYPE !== get_post_type( absint( $_POST['comment_post_ID'] ) ) ) {
			return $comment_data;
		}

		$builder = Builder::get( absint( $_POST['comment_post_ID'] ) );

		if ( isset( $_POST['comment_parent'], $_POST['rating'], $comment_data['comment_type'] ) &&
			$comment_data['comment_parent'] === 0 && self::is_default_comment_type( $comment_data['comment_type'] ) &&
			( ( ! $builder->rating_criteria_exists() && ! empty( $_POST['rating'] ) ) || ( $builder->rating_criteria_exists() && count( array_filter( $_POST['rating'] ) ) > 0 ) ) ) {
			$comment_data['comment_type'] = 'review';
		}

		return $comment_data;
	}

	/**
	 * Set comment status.
	 *
	 * Apporoved pending comment immediately when "Approve Immediately?" is enabled,
	 * ignore trash and spam status.
	 *
	 * @param mixed $approved
	 * @param array $comment_data
	 *
	 * @return mixed
	 */
	public static function set_comment_status( $approved, $comment_data ) {
		if ( is_admin() || $comment_data['comment_type'] !== 'review' || ATBDP_POST_TYPE !== get_post_type( $comment_data['comment_post_ID'] ) ) {
			return $approved;
		}

		if ( get_directorist_option( 'approve_immediately', 1 ) && $approved === 0 ) {
			$approved = 1; // set to approved
		}

		if ( ! get_directorist_option( 'approve_immediately', 1 ) && $approved === 1 ) {
			$approved = 0; // set to pending
		}

		return $approved;
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

	public static function on_comment_post( $comment_ID, $comment_approved, $comment_data ) {
		$post_id = isset( $_POST['comment_post_ID'] ) ? absint( $_POST['comment_post_ID'] ) : 0; // WPCS: input var ok, CSRF ok.

		if ( $post_id && ATBDP_POST_TYPE === get_post_type( $post_id ) ) {
			self::save_rating( $comment_ID, $comment_data );
			self::save_media( $comment_ID, $comment_data );

			self::clear_transients( $post_id );
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
	 * Check if user already shared a review.
	 *
	 * @param int $user_id
	 * @param int $post_id.
	 * @return bool
	 */
	public static function review_exists_by( $user_id, $post_id ) {
		global $wpdb;

		$has_review = $wpdb->get_var(
			$wpdb->prepare(
				"
			SELECT count(comment_ID) FROM $wpdb->comments
			WHERE comment_post_ID = %d
			AND ( comment_approved = '1' OR comment_approved = '0' )
			AND comment_type = 'review'
			AND user_id = %d
				",
				$post_id,
				$user_id
			)
		);

		return (bool) $has_review;
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
						AND comment_type = 'review'
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
		$builder = Builder::get( $post_id );

		if ( ! $builder->rating_criteria_exists() ) {
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

		foreach ( $builder->get_rating_criteria() as $key => $v ) {
			$criteria = array_column( $results, $key );

			if ( $criteria ) {
				$rating_map[ $key ] = number_format( array_sum( $criteria ) / count( $criteria ), 2, '.', '' );
			}
		}

		return $rating_map;
	}

	private static function save_rating( $comment_ID, $comment_data ) {
		if ( $comment_data['comment_type'] !== 'review' || empty( $_POST['rating'] ) ) {
			return;
		}

		$builder = Builder::get( $comment_data['comment_post_ID'] );

		// Return early incase rating is disabled
		if ( ! $builder->is_field_active( 'rating' ) ) {
			return;
		}

		$rating = 0;

		if ( is_array( $_POST['rating'] ) && $builder->rating_criteria_exists() ) {
			$criteria_meta = array();

			foreach( $builder->get_rating_criteria() as $key => $_v ) {
				if ( empty( $_POST['rating'][ $key ] ) ) {
					continue;
				}

				$criteria_meta[ $key ] = absint( $_POST['rating'][ $key ] );
			}

			$total = array_sum( $criteria_meta );
			$count = count( $criteria_meta );

			if ( $count ) {
				$rating = number_format( $total / $count, 2, '.', '' );
				add_comment_meta( $comment_ID, 'criteria_rating', $criteria_meta, true );
			}
		} else if ( is_array( $_POST['rating'] ) && ! $builder->rating_criteria_exists() ) {
			$rating = current( $_POST['rating'] );
			$rating = number_format( intval( $rating ), 2, '.', '' );
		} else {
			$rating = number_format( intval( $_POST['rating'] ), 2, '.', '' );
		}

		if ( $rating ) {
			add_comment_meta( $comment_ID, 'rating', $rating, true );
		}
	}

	private static function has_attachments() {
		if ( ! isset( $_FILES['review_attachments'], $_FILES['review_attachments']['name'] ) ||
			empty( $_FILES['review_attachments']['name'] ) ||
			count( array_filter( $_FILES['review_attachments']['name'] ) ) < 1 ) {
			return false;
		}

		return true;
	}

	private static function get_attachment_storable_path_only( $image_url ) {
		$dir = wp_get_upload_dir();
		return str_replace( $dir['basedir'] . '/', '', $image_url );
	}

	private static function save_media( $comment_ID, $comment_data ) {
		$post_id = $comment_data['comment_post_ID'];
		$builder = Builder::get( $post_id );

		if ( ! $builder->is_field_active( 'media' ) || ! self::has_attachments() ) {
			return;
		}

		$length = count( $_FILES['review_attachments']['name'] );
		$images = array();

		for ( $i = 0; $i < $length; $i++ ) {
			$data = wp_upload_bits(
				$_FILES['review_attachments']['name'][ $i ],
				null,
				file_get_contents( $_FILES['review_attachments']['tmp_name'][ $i ] )
			);

			if ( ! $data['error'] ) {
				$images[] = self::get_attachment_storable_path_only( $data['file'] );
			}
		}

		if ( ! empty( $images ) ) {
			update_comment_meta( $comment_ID, 'attachments', $images );
		}
	}

	public static function get_rating( $comment_id ) {
		return (float) get_comment_meta( $comment_id, 'rating', true );
	}

	public static function get_criteria_rating( $comment_id ) {
		$criteria_rating = get_comment_meta( $comment_id, 'criteria_rating', true );

		return ! empty( $criteria_rating ) ? array_map( 'intval', $criteria_rating ) : array();
	}
}

Comment::init();
