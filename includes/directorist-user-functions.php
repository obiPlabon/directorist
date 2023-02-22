<?php
/**
 * User related functions definition should be here.
 *
 * @package Directorist
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Get the user's favorite listings
 *
 * @since 7.2.0
 * @param int $user_id The user ID of the user whose favorites you want to retrieve.
 *
 * @return array An array of listing IDs.
 */
function directorist_get_user_favorites( $user_id = 0 ) {
	$favorites = get_user_meta( $user_id, 'atbdp_favourites', true );

	if ( ! empty( $favorites ) && is_array( $favorites ) ) {
		$favorites = directorist_prepare_user_favorites( $favorites );
	} else {
		$favorites = array();
	}

	/**
	 * User favorite listings filter hook.
	 *
	 * @since 7.2.0
	 * @param array $favorites
	 * @param int $user_id
	 */
	$favorites = apply_filters( 'directorist_user_favorites', $favorites, $user_id );

	return $favorites;
}

/**
 * This function update the user's favorites
 *
 * @since 7.2.0
 * @param int $user_id The ID of the user whose favorites are being updated.
 * @param int $listing_id The new favorite listing id.
 *
 * @return array
 */
function directorist_add_user_favorites( $user_id = 0, $listing_id = 0 ) {
	if ( get_post_type( $listing_id ) !== ATBDP_POST_TYPE ) {
		return array();
	}

	$old_favorites = directorist_get_user_favorites( $user_id );
	$new_favorites = array_merge( $old_favorites, array( $listing_id ) );
	$new_favorites = directorist_prepare_user_favorites( $new_favorites );

	update_user_meta( $user_id, 'atbdp_favourites', $new_favorites );

	$new_favorites = directorist_get_user_favorites( $user_id );

	/**
	 * Fire after user favorite listings updated.
	 *
	 * @since 7.2.0
	 * @param int $user_id
	 * @param array $new_favorites
	 * @param array $old_favorites
	 */
	do_action( 'directorist_user_favorites_added', $user_id, $new_favorites, $old_favorites );

	return $new_favorites;
}

/**
 * This function deletes a listing from a user's favorites
 *
 * @since 7.2.0
 * @param int $user_id The ID of the user who's favorites are being updated.
 * @param int $listing_id The listing ID that is being deleted from the user's favorites.
 *
 * @return array An array of listing IDs that are favorites for the user.
 */
function directorist_delete_user_favorites( $user_id = 0, $listing_id = 0 ) {
	if ( get_post_type( $listing_id ) !== ATBDP_POST_TYPE ) {
		return array();
	}

	$old_favorites = directorist_get_user_favorites( $user_id );
	$new_favorites = array_filter( $old_favorites, static function( $favorite ) use ( $listing_id ) {
		return ( $favorite !== $listing_id );
	} );

	if ( count( $old_favorites ) > count( $new_favorites ) ) {
		update_user_meta( $user_id, 'atbdp_favourites', $new_favorites );
	}

	/**
	 * Fire after user favorite listings updated.
	 *
	 * @since 7.2.0
	 * @param int $user_id
	 * @param array $new_favorites
	 * @param array $old_favorites
	 */
	do_action( 'directorist_user_favorites_deleted', $user_id, $new_favorites, $old_favorites );

	return $new_favorites;
}

/**
 * Process user favorites listings ids before saving and after retriving.
 *
 * @since 7.2.0
 * @param array $favorites
 * @access private
 *
 * @return array
 */
function directorist_prepare_user_favorites( $favorites = array() ) {
	$favorites = array_values( $favorites );
	$favorites = array_map( 'absint', $favorites );
	$favorites = array_filter( $favorites );
	$favorites = array_unique( $favorites );

	return $favorites;
}
