<?php
/**
 * Listing functions definition should be here.
 *
 * @package Directorist
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * This function returns the meta key for the listing views count.
 *
 * @since 7.2.0
 *
 * @return string The meta key for the views count.
 */
function directorist_get_listing_views_count_meta_key() {
	return '_atbdp_post_views_count';
}

/**
 * Get the number of views for a listing.
 *
 * @since 7.2.0
 * @param int $listing_id The ID of the listing.
 *
 * @return int The number of views for a given listing.
 */
function directorist_get_listing_views_count( $listing_id = 0 ) {
	if ( get_post_type( $listing_id ) !== ATBDP_POST_TYPE ) {
		return 0;
	}

	$views_count = get_post_meta( $listing_id, directorist_get_listing_views_count_meta_key(), true );
	return absint( $views_count );
}

/**
 * This function increments the views count of a listing by 1.
 *
 * @since 7.2.0
 * @param int $listing_id The ID of the listing.
 *
 * @return The number of views for a listing.
 */
function directorist_set_listing_views_count( $listing_id = 0 ) {
	if ( get_post_type( $listing_id ) !== ATBDP_POST_TYPE ) {
		return false;
	}

	$views_count = directorist_get_listing_views_count( $listing_id );
	$views_count = $views_count + 1; // Listing got a new view :D
	update_post_meta( $listing_id, directorist_get_listing_views_count_meta_key(), $views_count );

	/**
	 * Fire this hook when listing got a view.
	 *
	 * @since 7.2.0
	 * @param int $listing_id
	 */
	do_action( 'directorist_listing_views_count_updated', $listing_id );

	return true;
}


/**
 * Get listings field key by import file header key.
 * Used in listings import.
 *
 * @param  string $header_key CSV file header key.
 *
 * @return string Listing field key
 */
function directorist_translate_to_listing_field_key( $header_key = '' ) {
    $fields_map = array(
        'date'                   => 'publish_date',
        'publish_date'           => 'publish_date',
        'status'                 => 'listing_status',
        'listing_status'         => 'listing_status',
        'name'                   => 'listing_title',
        'title'                  => 'listing_title',
        'details'                => 'listing_content',
        'content'                => 'listing_content',
        'price'                  => 'price',
        'price_range'            => 'price_range',
        'location'               => 'location',
        'tag'                    => 'tag',
        'ategory'                => 'category',
        'zip'                    => 'zip',
        'phone'                  => 'phone',
        'phone2'                 => 'phone2',
        'fax'                    => 'fax',
        'email'                  => 'email',
        'website'                => 'website',
        'social'                 => 'social',
        'atbdp_post_views_count' => 'atbdp_post_views_count',
        'views_count'            => 'atbdp_post_views_count',
        'manual_lat'             => 'manual_lat',
        'manual_lng'             => 'manual_lng',
        'hide_map'               => 'hide_map',
        'hide_contact_info'      => 'hide_contact_owner',
        'listing_prv_img'        => 'listing_img',
        'preview'                => 'listing_img',
        'listing_img'            => 'listing_img',
        'videourl'               => 'videourl',
        'tagline'                => 'tagline',
        'address'                => 'address',
    );

    return isset( $fields_map[ $header_key ] ) ? $fields_map[ $header_key ] : '';
}

/**
 * Check if given listing id belongs to the given user id.
 *
 * @since 7.1.1
 * @param int $listing_id Listing id.
 * @param int $user_id User id.
 *
 * @return bool
 */
function directorist_is_listing_author( $listing_id = null, $user_id = null ) {
	if ( ! $user_id || ! is_int( $user_id ) ) {
		return false;
	}

	if ( ! $listing_id || ! is_int( $listing_id ) ) {
		$listing_id = get_the_ID();
	}

	$listing = get_post( $listing_id );
	if ( ! $listing || $listing->post_type !== ATBDP_POST_TYPE ) {
		return false;
	}

	if ( intval( $listing->post_author ) !== $user_id ) {
		return false;
	}

	return true;
}

/**
 * Check if given listing id belongs to the current user.
 *
 * @since 7.1.1
 * @param int $listing_id
 *
 * @return bool
 */
function directorist_is_current_user_listing_author( $listing_id = null ) {
	return directorist_is_listing_author( $listing_id, get_current_user_id() );
}
