<?php
namespace Directorist\Listings;

class Query {

	protected function get_default_args() {
		$args = array(
			// Default alternative
			'include'  => null,        // post__in
			'exclude'  => null,        // post__not_in
			'per_page' => 10,          // posts_per_page
			'search'   => null,        // q
			'status'   => 'publish',   // status

			// Category taxonomy args
			'categories'          => null,        // array
			'categories_field'    => 'term_id',
			'categories_relation' => 'AND',

			// Tag taxonomy
			'tags'          => null,
			'tags_field'    => 'term_id',
			'tags_relation' => 'AND',

			// Location taxonomy
			'locations'          => null,
			'locations_field'    => 'term_id',
			'locations_relation' => 'AND',

			// Directory type taxonomy
			'directories'          => null,          // array
			'directories_field'    => 'term_id',
			'directories_relation' => 'AND',

			'rating'       => null,   // number
			'review_count' => null,   // number

			'view_count'   => null,   // numberß

			// Meta fields
			'address'     => null,   // string
			'website'     => null,   // string
			'email'       => null,   // string
			'phone'       => null,   // string
			'fax'         => null,   // string
			'zip'         => null,   // string
			'distance'    => null,
			'latitude'    => null,
			'longitude'   => null,
			'radius'      => [],
			'price'       => [],
			'price_range' => null,   // string
			'featured'    => null,   // true || false

			'search_relation' => 'AND',
			'meta_relation'   => 'AND',
		);

		return apply_filters( 'directorist_listings_query_default_args', $args );
	}

	public function set_defaults() {
		// Meta fields
		// _featured - numeric - 1
		// _listing_status - string - {expired, ...}
		// _atbdp_post_views_count - numeric -
		// _price - numeric -
		// address - string
		// website - string
		// email - string
		// phone - string
		// fax - string
		// zip - string
		// distance - numeric
		// latitude - numeric
		// longitude - numeric
	}

	public function parse_args( array $args = array() ) {
		$args = array_replace_recursive(
			$this->get_default_args(),
			$args
		);

		$args = $this->parse_native_args( $args );
		$args = $this->parse_meta_args( $args );
		$args = $this->parse_taxonomy_args( $args );
	}

	protected function parse_native_args( $args ) {
		if ( ! isset( $args['posts_per_page'] ) || ! empty( $args['per_page'] ) ) {
			$args['posts_per_page'] = $args['per_page'];
			unset( $args['per_page'] );
		}

		if ( ! isset( $args['post__in'] ) && ! empty( $args['include'] ) ) {
			$args['post__in'] = $args['include'];
			unset( $args['include'] );
		}

		if ( ! isset( $args['post__not_in'] ) && ! empty( $args['exclude'] ) ) {
			$args['post__not_in'] = $args['exclude'];
			unset( $args['exclude'] );
		}

		if ( ! isset( $args['q'] ) && ! empty( $args['search'] ) ) {
			$args['q'] = $args['search'];
			unset( $args['search'] );
		}

		return $args;
	}

	protected function parse_meta_args( $args ) {
	}

	protected function parse_taxonomy_args( $args ) {
	}

	public function __construct( array $args = [], $query_id = 'listings' ) {
	}

	public function prepare_query_args() {
		$this->parsed_args;
	}

	protected function get_query_args() {
		return apply_filters( 'directorist_listings_query_args', $args, $this );
	}

	public function run() {
		return new \WP_Query( $this->get_query_args() );
	}
}
