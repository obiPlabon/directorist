<?php
/**
 * Advance review class
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Review_Data {

	const RATING_COUNTS_DB_KEY = '_directorist_listing_rating_counts';

	const AVG_RATING_DB_KEY = '_directorist_listing_rating';

	const REVIEW_COUNT_DB_KEY = '_directorist_listing_review_count';

	public static function get_rating_counts( $listing_id ) {
		$counts = get_post_meta( $listing_id, self::RATING_COUNTS_DB_KEY, true );
		return ( ! empty( $counts ) && is_array( $counts ) ) ? $counts : array();
	}

	public static function update_rating_counts( $listing_id, $counts ) {
		update_post_meta( $listing_id, self::RATING_COUNTS_DB_KEY, (array) $counts );
	}

	public static function get_rating( $listing_id ) {
		$rating = get_post_meta( $listing_id, self::AVG_RATING_DB_KEY, true );
		return (float) ( ! empty( $rating ) ? $rating : 0 );
	}

	public static function update_rating( $listing_id, $rating ) {
		update_post_meta( $listing_id, self::AVG_RATING_DB_KEY, (float) $rating );
	}

	public static function get_review_count( $listing_id ) {
		$counts = get_post_meta( $listing_id, self::REVIEW_COUNT_DB_KEY, true );
		return ( ! empty( $counts ) ) ? absint( $counts ) : 0;
	}

	public static function update_review_count( $listing_id, $counts ) {
		update_post_meta( $listing_id, self::REVIEW_COUNT_DB_KEY, absint( $counts ) );
	}
}
