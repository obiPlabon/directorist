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
				'comment_meta'         => array(
					'rating' => $review->rating
				)
			) );
		}
	}
}

function directorist_7100_migrate_posts_table_to_comments_table() {
	// select meta_table.post_id, meta_table.meta_key, meta_table.meta_value from wp_posts left join wp_postmeta as meta_table on wp_posts.ID=meta_table.post_id where wp_posts.post_type='atbdp_listing_review';

// 	SELECT posts_join_table.post_id,
//     MAX(CASE WHEN posts_join_table.meta_key = '_listing_reviewer' THEN posts_join_table.meta_value END) AS `user_name`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_email' THEN posts_join_table.meta_value END) AS `email`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_by_user_id' THEN posts_join_table.meta_value END) AS `user_id`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_by_guest' THEN posts_join_table.meta_value END) AS `guest`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_reviewer_details' THEN posts_join_table.meta_value END) AS `comment`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_reviewer_rating' THEN posts_join_table.meta_value END) AS `rating`,
//     MAX(CASE WHEN posts_join_table.meta_key = '_review_status' THEN posts_join_table.meta_value END) AS `status`
// FROM (select meta_table.post_id, meta_table.meta_key, meta_table.meta_value from wp_posts left join wp_postmeta as meta_table on wp_posts.ID=meta_table.post_id where wp_posts.post_type='atbdp_listing_review') as posts_join_table group by post_id;
}
