<?php
namespace wpWax\Directorist;

class Query {

	public function set_defaults() {
		$this->defaults = [
			'include'              => [],
			'exclude'              => [],
			'per_page'             => 10,
			'offset'               => 0,
			'order'                => null,
			'orderby'              => null,
			'author' => null,
			'category'        => [],
			'category_exclude'       => [],
			'category_relation'  => 'AND',
			'tags'                 => [],
			'tags_exclude'         => [],
			'tags_relation'        => 'AND',
			'locations'            => [],
			'locations_exclude'    => [],
			'locations_relation'   => [],
			'directories'          => [],
			'directories_exclude'  => [],
			'directories_relation' => 'AND',
			'rating'               => 0,
			'search'               => null,
			'status'               => 'publish',
			'views'                => 0,
			'address'              => null,
			'website'              => null,
			'email'                => null,
			'phone'                => null,
			'fax'                  => null,
			'zip'                  => null,
			'distance'             => null,
			'latitude'             => null,
			'longitude'            => null,
			'radius'               => [],
			'featured'             => null,
			'price'                => [
				'min' => 0,
				'max' => 0,
			],
			'price_range' => null,
			'search_relation' => 'AND',
			'meta_relation' => 'AND',
		];
	}

	public function parse_args( array $args = [] ) {
		$this->parsed_args = array_replace_recursive( $this->defaults, $args );
	}

	public function __construct( array $args = [] ) {
		$this->set_defaults();
		$this->parse_args( $args );
	}

	public function prepare_query_args() {
		$this->parsed_args;
	}

	public function do_wp_query() {
		$this->query = new \WP_Query();
	}
}