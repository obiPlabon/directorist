<?php
namespace Directorist\Listings;

class Query {

	protected $defaults = array(
		'include'  => null,   // post__in
		'exclude'  => null,   // post__not_in
		'per_page' => 10,     // posts_per_page

		// Category taxonomy args
		'categories'          => null,        // array
		'categories_field'    => 'term_id',
		'categories_relation' => 'AND',

		'tags'          => [],          // array
		'tags_field'    => 'term_id',
		'tags_relation' => 'AND',

		'locations'          => [],          // array
		'locations_field'    => 'term_id',
		'locations_relation' => 'AND',

		'directories'          => [],          // array
		'directories_field'    => 'term_id',
		'directories_relation' => 'AND',

		'search' => null,

		'rating'       => null,   // number
		'review_count' => null,   // number
		'view_count'   => null,   // numberß

		'status' => 'publish',

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

		'featured' => null,   // true || false

		'search_relation' => 'AND',
		'meta_relation'   => 'AND',
	);

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

	public function parse_args( array $args = [] ) {
		$this->parsed_args = array_replace_recursive( $this->defaults, $args );
	}

	public function __construct( array $args = [], $query_id = 'listings' ) {
		if ( empty( $query_id ) ) {
			return new WP_Error( 'query_id_missing', __( 'Listings query id cannot be empty', 'directorist' ) );
		}

		$this->id = $query_id;
		$this->set_defaults();
		$this->parse_args( $args );

		return $this->run();
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
