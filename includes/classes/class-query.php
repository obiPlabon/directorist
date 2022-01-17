<?php
namespace Directorist\Listings;

class Query {
	const DEFAULT_TAX_QUERY_FIELD = 'term_id';

	protected function get_default_args() {
		$args = array(
			// Default alternative
			'include'  => null,
			'exclude'  => null,
			'per_page' => 10,
			'search'   => null,
			'status'   => 'publish',

			// Category taxonomy args
			'categories__in'              => null,
			'categories__not_in'          => null,
			'categories_field'            => self::DEFAULT_TAX_QUERY_FIELD,
			'categories_include_children' => true,
			'categories_relation'         => 'AND',

			// Tag taxonomy
			'tags__in'      => null,
			'tags__not_in'  => null,
			'tags_field'    => self::DEFAULT_TAX_QUERY_FIELD,
			'tags_relation' => 'AND',

			// Location taxonomy
			'locations__in'      => null,
			'locations__not_in'  => null,
			'locations_field'    => self::DEFAULT_TAX_QUERY_FIELD,
			'locations_relation' => 'AND',

			// Directory type taxonomy
			'directories__in'      => null,
			'directories__not_in'  => null,
			'directories_field'    => self::DEFAULT_TAX_QUERY_FIELD,
			'directories_relation' => 'AND',

			'rating'               => null,   // number
			'rating_compare'       => '>=',
			'review_count'         => null,   // number
			'review_count_compare' => '>=',
			'view_count'           => null,   // number
			'view_count_compare'   => '>=',

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

	public function parse_args( $args = array() ) {
		$args = wp_parse_args( $args, $this->get_default_args() );

		$args = $this->parse_native_args( $args );

		// Taxonomy args parsing.
		$args = $this->parse_categories_args( $args );
		$args = $this->parse_tags_args( $args );
		$args = $this->parse_locations_args( $args );
		$args = $this->parse_directories_args( $args );

		// Meta args parsing.
		$args = $this->parse_meta_args( $args );

		return $args;
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
		$query = array();

		foreach ( array( 'address', 'email', 'website', 'fax', 'zip' ) as $field ) {
			if ( ! empty( $args[ $field ] ) ) {
				$query[ $field ] = array(
					'key'     => '_' . $field,
					'value'   => $args[ $field ],
					'compare' => 'LIKE'
				);
			}
		}

		if ( ! empty( $args['phone'] ) ) {
			$query['phone'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_phone2',
					'value'   => $args['phone'],
					'compare' => 'LIKE'
				),
				array(
					'key'     => '_phone',
					'value'   => $args['phone'],
					'compare' => 'LIKE'
				)
			);
		}

		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		$args['meta_query'] = array_merge( $args['meta_query'], $query );

		return $args;
	}

	protected function parse_categories_args( $args ) {
		return $this->parse_taxonomy_args( $args, ATBDP_CATEGORY );
	}

	protected function parse_tags_args( $args ) {
		return $this->parse_taxonomy_args( $args, ATBDP_TAGS );
	}

	protected function parse_locations_args( $args ) {
		return $this->parse_taxonomy_args( $args, ATBDP_LOCATION );
	}

	protected function parse_directories_args( $args ) {
		return $this->parse_taxonomy_args( $args, ATBDP_DIRECTORY_TYPE );
	}

	protected function parse_taxonomy_args( $args, $taxonomy ) {
		$prefix   = $this->get_taxonomy_query_prefix( $taxonomy );
		$in       = $prefix . '__in';
		$not_in   = $prefix . '__not_in';
		$field    = $prefix . '_field';
		$relation = $prefix . '_relation';

		if ( empty( $args[ $in ] ) && empty( $args[ $not_in ] ) ) {
			return $args;
		}

		$query = array();
		if ( ! empty( $args[ $in ] ) ) {
			$query[ $in ] = array(
				'terms'    => $args[ $in ],
				'taxonomy' => $taxonomy,
				'operator' => 'IN',
				'field'    => $this->validate_taxonomy_query_field( $args[ $field ] ),
			);

			if ( ! empty( $args[ $prefix . '_include_children'] ) && is_taxonomy_hierarchical( $taxonomy ) ) {
				$query[ $in ]['include_children'] = true;
			}
		}

		if ( ! empty( $args[ $not_in ] ) ) {
			$query[ $not_in ] = array(
				'terms'    => $args[ $not_in ],
				'taxonomy' => $taxonomy,
				'operator' => 'NOT IN',
				'field'    => $this->validate_taxonomy_query_field( $args[ $field ] ),
			);
		}

		if ( empty( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}

		unset(
			$args[ $in ],
			$args[ $not_in ],
			$args[ $relation ],
			$args[ $field ],
			$args[ $prefix . '_include_children']
		);

		$args['tax_query'] = array_merge( $args['tax_query'], $query );

		return $args;
	}

	private function get_taxonomy_query_prefix( $taxonomy ) {
		$map = array(
			ATBDP_CATEGORY       => 'categories',
			ATBDP_TAGS           => 'tags',
			ATBDP_LOCATION       => 'locations',
			ATBDP_DIRECTORY_TYPE => 'directories',
		);

		return $map[ $taxonomy ];
	}

	private function validate_taxonomy_query_field( $field = self::DEFAULT_TAX_QUERY_FIELD ) {
		if ( in_array( $field, array( 'term_id', 'name', 'slug', 'term_taxonomy_id' ), true ) ) {
			return $field;
		}

		return self::DEFAULT_TAX_QUERY_FIELD;
	}

	public function __construct( $args = array() ) {
		// $args = $this->parse_args( $args );
		// return 'hello world';
	}

	// public function prepare_query_args() {
	// 	$this->parsed_args;
	// }

	// protected function get_query_args() {
	// 	return apply_filters( 'directorist_listings_query_args', $args, $this );
	// }

	// public function run() {
	// 	return new \WP_Query( $this->get_query_args() );
	// }
}
