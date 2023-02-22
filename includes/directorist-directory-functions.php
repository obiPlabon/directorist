<?php
/**
 * Directory functions definition should be here.
 *
 * @package Directorist
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Check if multi directory is enabled.
 *
 * @return bool
 */
function directorist_is_multi_directory() {
	return (bool) get_directorist_option( 'enable_multi_directory', false );
}

/**
 * Get all the directories.
 * It's a wrapper around get_terms.
 *
 * @since //TODO: add version number before merge.
 * @see get_terms()
 * @param array $args Arguments of get_terms
 *
 * @return WP_Term[]|int[]|string[]|string|WP_Error Whatever is returned by get_terms.
 */
function directorist_get_directories( array $args = array() ) {
	$args = wp_parse_args( $args, array(
		'hide_empty' => false,
	) );

	$args['taxonomy'] = ATBDP_TYPE;

	return get_terms( $args );
}

/**
 * Get all the directories of a term (category or location).
 *
 * @since //TODO: add version number before merge.
 * @param int $term_id
 *
 * @return array id=>name or empty array
 */
function directorist_get_directories_of_term( $term_id ) {
	$directory_ids = (array) get_term_meta( $term_id, '_directory_type', true );
	$directory_ids = array_map( 'absint', $directory_ids );
	$directory_ids = array_filter( $directory_ids );

	if ( empty( $directory_ids ) ) {
		return array();
	}

	$args = array(
		'include'                => $directory_ids,
		'update_term_meta_cache' => false,
		'fields'                 => 'id=>name',
	);

	$directories = directorist_get_directories( $args );

	if ( is_wp_error( $directories ) ) {
		return array();
	}

	return $directories;
}

/**
 * @since //TODO: add version number before merge.
 * @see directorist_get_directories_of_term()
 */
function directorist_get_directories_of_category( $category_id ) {
	return directorist_get_directories_of_term( $category_id );
}

/**
 * @since //TODO: add version number before merge.
 * @see directorist_get_directories_of_term()
 */
function directorist_get_directories_of_location( $location_id ) {
	return directorist_get_directories_of_term( $location_id );
}

function directorist_update_directories_of_term( $term_id, array $directory_ids ) {
	$directory_ids = array_filter( $directory_ids );

	if ( empty( $directory_ids ) ) {
		return;
	}

	// Save as serialized to display.
	update_term_meta( $term_id, '_directory_type', $directory_ids );

	// Save separately to improve query performance.
	foreach ( $directory_ids as $directory_id ) {
		update_term_meta( $term_id, "_directory_type_{$directory_id}", 1 );
	}
}

function directorist_delete_directories_of_term( $term_id ) {
	$directories = directorist_get_directories_of_term( $term_id );

	foreach ( array_keys( $directories ) as $directory_id ) {
		delete_term_meta( $term_id, "_directory_type_{$directory_id}" );
	}

	delete_term_meta( $term_id, '_directory_type' );
}
