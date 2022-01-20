<?php
/**
 * Listings query class.
 */
namespace Directorist\Listings;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use WP_Query;

/**
 * Listings query abstraction class.
 *
 * @since 7.1.1
 */
class Query {

	/**
	 * Taxonomy query feild.
	 * Possible fields are term_id, slug, name.
	 */
	const DEFAULT_TAXONOMY_QUERY_FIELD = 'term_id';

	/**
	 * Geo query key.
	 */
	const GEO_QUERY_KEY = 'atbdp_geo_query';

	/**
	 * Unique query identifier.
	 *
	 * @var string
	 */
	private $query_id;

	/**
	 * WP Query instance
	 *
	 * @var WP_Query
	 */
	private $wp_query;

	protected function get_default_args() {
		$args = array(
			// Native query args alternative.
			'include'  => null,   // alternative to post__in
			'exclude'  => null,   // alternative to post__not_in
			'per_page' => 10,     // alternative to posts_per_page
			'search'   => null,   // alternative to q
			// Category taxonomy.
			'categories__in'              => null,                                 // array
			'categories__not_in'          => null,                                 // array
			'categories_field'            => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			'categories_include_children' => true,
			// Tag taxonomy.
			'tags__in'     => null,                                 // array
			'tags__not_in' => null,                                 // array
			'tags_field'   => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			// Location taxonomy.
			'locations__in'     => null,                                 // array
			'locations__not_in' => null,                                 // array
			'locations_field'   => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			// Directory type taxonomy.
			'directories__in'     => null,                                 // array
			'directories__not_in' => null,                                 // array
			'directories_field'   => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			// Meta fields
			'rating'               => null,   // number
			'rating_compare'       => '>=',   // @see $this->validate_meta_compare()
			'review_count'         => null,   // number
			'review_count_compare' => '>=',   // @see $this->validate_meta_compare()
			'view_count'           => null,   // number
			'view_count_compare'   => '>=',   // @see $this->validate_meta_compare()
			// Address
			'address' => null,   // string - LIKE compare
			'website' => null,   // string - LIKE compare
			'email'   => null,   // string - LIKE compare
			'phone'   => null,   // string - LIKE compare
			'fax'     => null,   // string - LIKE compare
			'zip'     => null,   // string - LIKE compare
			// Radius search
			'distance'  => null,   // string
			'latitude'  => null,   // number
			'longitude' => null,   // number
			// Price related args
			'price'         => null,   // number | array
			'price_compare' => null,   // string @see $this->validate_meta_compare()
			'price_range'   => null,   // string @see directorist_get_price_ranges()
			// Featured
			'featured' => null,   // true || false
			// Relation args
			'taxonomy_relation' => 'AND',   // AND | OR
			'meta_relation'     => 'AND',   // AND | OR
		);

		return apply_filters( 'directorist_listings_query_default_args', $args, $this->query_id );
	}

	public function get_default_wp_args() {
		$args = array(
			'post_type' => ATBDP_POST_TYPE,
			'orderby'   => 'date',
			'order'     => 'DESC',
			'status'    => 'publish',
		);

		return apply_filters( 'directorist_listings_query_default_wp_args', $args, $this->query_id );
	}

	public function parse_args( $args = array() ) {
		$args = wp_parse_args( $args, array_merge(
			$this->get_default_wp_args(),
			$this->get_default_args()
		) );

		// Parse native args.
		$args = $this->parse_native_args( $args );

		// Parse taxonomy args.
		$args = $this->parse_all_taxonomy_args( $args );

		// distance, latitude and longiture
		$args = $this->parse_radius_args( $args );

		// Parse meta args.
		$args = $this->parse_meta_args( $args );

		// Clean default args.
		$args = array_diff_key( $args, $this->get_default_args() );

		return apply_filters( 'directorist_listings_query_parsed_args', $args, $this->query_id );
	}

	protected function parse_native_args( $args ) {
		if ( ! isset( $args['posts_per_page'] ) || ! empty( $args['per_page'] ) ) {
			$args['posts_per_page'] = $args['per_page'];
		}

		if ( ! isset( $args['post__in'] ) && ! empty( $args['include'] ) ) {
			$args['post__in'] = $args['include'];
		}

		if ( ! isset( $args['post__not_in'] ) && ! empty( $args['exclude'] ) ) {
			$args['post__not_in'] = $args['exclude'];
		}

		if ( ! isset( $args['q'] ) && ! empty( $args['search'] ) ) {
			$args['q'] = $args['search'];
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

		// Rating
		if ( isset( $args['rating'] ) ) {
			$query['rating'] = array(
				'key'     => directorist_get_rating_field_meta_key(),
				'value'   => $args['rating'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['rating_compare'] ),
			);
		}

		// Reviews count
		if ( isset( $args['review_count'] ) ) {
			$query['review_count'] = array(
				'key'     => directorist_get_review_count_field_meta_key(),
				'value'   => $args['review_count'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['review_count_compare'] ),
			);
		}

		// Views count
		if ( isset( $args['view_count'] ) ) {
			$query['view_count'] = array(
				'key'     => '_atbdp_post_views_count',
				'value'   => $args['view_count'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['view_count_compare'] ),
			);
		}

		// Featured listings
		if ( isset( $args['featured'] ) ) {
			if ( $args['featured'] ) {
				$query['featured'] = array(
					'key'     => '_featured',
					'compare' => '=',
					'value'   => 1,
				);
			} else {
				$query['featured'] = array(
					'relation' => 'OR',
					array(
						'key'     => '_featured',
						'compare' => '!=',
						'value'   => 1,
					),
					array(
						'key'     => '_featured',
						'compare' => 'NOT EXISTS',
					)
				);
			}
		}

		// Price query.
		if ( isset( $args['price'] ) ) {
			$query['price'] = array(
				'key'     => '_price',
				'value'   => $args['price'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['price_compare'] )
			);
		}

		// Price range compare
		if ( isset( $args['price_range'] ) && $this->is_valid_price_range( $args['price_range'] ) ) {
			$query['price_range'] = array(
				'key'     => '_price_range',
				'value'   => $args['price_range'],
				'compare' => '='
			);
		}

		// Return early.
		if ( empty( $query ) ) {
			return $args;
		}

		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array_merge( $args['meta_query'], $query );
		} else {
			$args['meta_query'] = $query;
		}

		if ( ! isset( $args['meta_query']['relation'] ) && isset( $args['meta_relation'] ) ) {
			$args['meta_query']['relation'] = $args['meta_relation'];
		}

		$args['meta_query'] = apply_filters( 'directorist_listings_query_parsed_meta_query_args', $args['meta_query'], $this->query_id );

		return $args;
	}

	protected function parse_radius_args( $args ) {
		if ( isset( $args['distance'], $args['latitude'], $args['longitude'] ) ) {
			$args[ self::GEO_QUERY_KEY ] = array(
				'lat_field' => '_manual_lat',
				'lng_field' => '_manual_lng',
				'latitude'  => $args['latitude'],
				'longitude' => $args['longitude'],
				'distance'  => $args['distance'],
				'units'     => get_directorist_option( 'radius_search_unit', 'miles' )
			);
		}

		return $args;
	}

	protected function parse_all_taxonomy_args( $args ) {
		$args = $this->parse_categories_args( $args );
		$args = $this->parse_tags_args( $args );
		$args = $this->parse_locations_args( $args );
		$args = $this->parse_directories_args( $args );

		// Return when no tax query.
		if ( empty( $args['tax_query'] ) ) {
			return $args;
		}

		if ( ! isset( $args['tax_query']['relation'] ) && isset( $args['taxonomy_relation'] ) ) {
			$args['tax_query']['relation'] = $args['taxonomy_relation'];
		}

		$args['tax_query'] = apply_filters( 'directorist_listings_query_parsed_tax_query_args', $args['tax_query'], $this->query_id );

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

		// Return early
		if ( empty( $query ) ) {
			return $args;
		}

		if ( ! empty( $args['tax_query'] ) && is_array( $args['tax_query'] ) ) {
			$args['tax_query'] = array_merge( $args['tax_query'], $query );
		} else {
			$args['tax_query'] = $query;
		}

		return $args;
	}

	private function is_valid_price_range( $price_range ) {
		return ( in_array( $price_range, directorist_get_price_ranges(), true ) );
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

	private function validate_taxonomy_query_field( $field = self::DEFAULT_TAXONOMY_QUERY_FIELD ) {
		if ( in_array( $field, array( 'term_id', 'name', 'slug', 'term_taxonomy_id' ), true ) ) {
			return $field;
		}

		return self::DEFAULT_TAXONOMY_QUERY_FIELD;
	}

	private function validate_meta_compare( $operator = '=' ) {
		$valid_operators = array(
			'=',
			'!=',
			'>',
			'>=',
			'<',
			'<=',
			'LIKE',
			'NOT LIKE',
			'IN',
			'NOT IN',
			'BETWEEN',
			'NOT BETWEEN',
			'EXISTS',
			'NOT EXISTS',
		);

		return ( in_array( $operator, $valid_operators, true ) ? $operator : '=' );
	}

	public function __construct( $args = array(), $query_id = null ) {
		$this->do( $args, $query_id );
	}

	protected function set_query_id( $query_id = null ) {
		static $called = 1;

		if ( is_null( $query_id ) ) {
			$this->query_id = 'directorist_query__' . $called;
			$called += 1;
		} else {
			$this->query_id = $query_id;
		}
	}

	public function do( $args, $query_id ) {
		$this->set_query_id( $query_id );

		$this->wp_query = new WP_Query( $this->parse_args( $args ) );
		$this->wp_query->set( 'directorist_query_id', $this->query_id );

		return $this->wp_query;
	}

	public function get_query() {
		return $this->wp_query;
	}

	public function __call( $method, $args ) {
		if ( method_exists( $this->wp_query, $method ) ) {
			return call_user_func_array( array( $this->wp_query, $method ), $args );
		}
	}

	public function __get( $prop ) {
		if ( property_exists( $this->wp_query, $prop ) ) {
			return $this->wp_query->{$prop};
		}
	}
}
