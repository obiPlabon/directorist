<?php
/*This file will contain most common filters that will help other developer extends / modify our plugin settings or design */


/**
 * It lets you modify button classes used by the directorist plugin. You can add your custom class or modify existing ones.
 * @param string $type the type of the button being printed. eg. default or primary etc.
 * @return string it returns the names of the classed that should be added to a button.
 */

function atbdp_directorist_button_classes( $type = 'primary' ) {
     /**
      * It lets you modify button classes used by the directorist plugin. You can add your custom class or modify existing ones.
      * @param $type string the type of the button eg. default, primary etc. Default value is default.
      *
      */
     return apply_filters( 'atbdp_button_class', "directorist-btn directorist-btn-{$type} directorist-btn-lg", $type );
}

/**
 * @since 6.3.4
 * @return string image scource
 */
function atbdp_get_image_source( $id = null, $size = 'medium' ) {
    return wp_get_attachment_image_url( $id, $size );
}

/**
 * Add location and category directories ids to term meta keys for better search performance.
 *
 * @param mixed $term_id
 * @param mixed $object_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @return void
 */
function directorist_on_location_category_added_term_meta( $meta_id, $term_id, $meta_key, $meta_value ) {
	if ( '_directory_type' !== $meta_key ) {
		return;
	}

	$term = get_term( $term_id );
	if ( $term->taxonomy !== ATBDP_LOCATION && $term->taxonomy !== ATBDP_CATEGORY ) {
		return;
	}

	directorist_delete_term_directories_performance_key( $term_id );

	// Add performance key.
	$directory_ids = wp_parse_id_list( $meta_value );
	directorist_add_term_directories_performance_key( $term_id, $directory_ids );
}
add_action( 'added_term_meta', 'directorist_on_location_category_added_term_meta', 10, 4 );
add_action( 'updated_term_meta', 'directorist_on_location_category_added_term_meta', 10, 4 );

function directorist_on_location_category_deleted_term_meta( $meta_id, $term_id, $meta_key ) {
	if ( '_directory_type' !== $meta_key ) {
		return;
	}

	$term = get_term( $term_id );
	if ( $term->taxonomy !== ATBDP_LOCATION && $term->taxonomy !== ATBDP_CATEGORY ) {
		return;
	}

	directorist_delete_term_directories_performance_key( $term_id );
}
add_action( 'deleted_term_meta', 'directorist_on_location_category_deleted_term_meta', 10, 3 );
