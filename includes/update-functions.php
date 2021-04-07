<?php
/**
 * Database update functions.
 *
 */
defined( 'ABSPATH' ) || die();

// Migrate old reviews data from review table to comments table
function directorist_7100_migrate_reviews_table_to_comments_table() {
	global $wpdb;

	$review_table = $wpdb->prefix . 'atbdp_review';
	$reviews = $wpdb->get_results( "SELECT * FROM {$review_table}" );

	if ( ! empty( $reviews ) ) {
		foreach ( $reviews as $review ) {
			wp_insert_comment( array(
				'comment_type'         => ( ( isset( $review->rating ) && $review->rating > 0 ) ? 'review' : 'comment' ),
				'comment_post_ID'      => $review->post_id,
				'comment_author'       => $review->name,
				'comment_author_email' => $review->email,
				'comment_content'      => $review->content,
				'comment_date'         => $review->date_created,
				'comment_date_gmt'     => $review->date_created,
				'user_id'              => ! empty( $review->by_user_id ) ? absint( $review->by_user_id ) : 0,
				'comment_approved'     => 1,
				'comment_meta'         => array(
					'rating' => $review->rating
				)
			) );
		}
	}

	//TODO: delete review table
}

// pending -> pending:0
// declined -> trash
// approved -> approved:1
function directorist_7100_migrate_posts_table_to_comments_table() {
	global $wpdb;

	$reviews = $wpdb->get_results(
		"SELECT posts_meta_join.post_id,
			posts_meta_join.post_date AS comment_date,
			MAX(CASE WHEN posts_meta_join.meta_key = '_listing_reviewer' THEN posts_meta_join.meta_value END) AS `author`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_email' THEN posts_meta_join.meta_value END) AS `author_email`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_by_user_id' THEN posts_meta_join.meta_value END) AS `user_id`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_by_guest' THEN posts_meta_join.meta_value END) AS `guest`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_reviewer_details' THEN posts_meta_join.meta_value END) AS `comment`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_reviewer_rating' THEN posts_meta_join.meta_value END) AS `rating`,
			MAX(CASE WHEN posts_meta_join.meta_key = '_review_status' THEN posts_meta_join.meta_value END) AS `status`
		FROM (select posts_meta.post_id, posts_meta.meta_key, posts_meta.meta_value, posts.post_date from {$wpdb->posts} as posts left join {$wpdb->postmeta} as posts_meta on posts.ID=posts_meta.post_id where posts.post_type='atbdp_listing_review') as posts_meta_join group by post_id"
	);

	if ( ! empty( $reviews ) ) {
		foreach ( $reviews as $review ) {
			wp_insert_comment( array(
				'comment_type'         => ( ( isset( $review->rating ) && $review->rating > 0 ) ? 'review' : 'comment' ),
				'comment_post_ID'      => $review->post_id,
				'comment_author'       => $review->author,
				'comment_author_email' => $review->author_email,
				'comment_content'      => $review->comment,
				'comment_date'         => $review->comment_date,
				'comment_date_gmt'     => $review->comment_date,
				'user_id'              => ! empty( $review->user_id ) ? absint( $review->user_id ) : 0,
				'comment_approved'     => _directorist_get_comment_status_by_review_status( $review->status ),
				'comment_meta'         => array(
					'rating' => $review->rating
				)
			) );
		}
	}

	//TODO: delete review post type posts
}

/**
 * Get wp comment status by review post type review status.
 *
 * @access private
 * @return void
 */
function _directorist_get_comment_status_by_review_status( $status = 'approved' ) {
	$statuses = array(
		'approved' => 1,
		'declined' => 'trash',
		'pending'  => 0,
	);

	return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'];
}

function directorist_7100_update_db_version() {
	ATBDP_Installation::update_db_version( '7.1.0.0' );
}
