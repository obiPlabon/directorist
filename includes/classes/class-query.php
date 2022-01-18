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
			'include'  => null,   // Alternative to post__in
			'exclude'  => null,   // Alternative to post__not_in
			'per_page' => 10,     // Alternative to posts_per_page
			'search'   => null,   // Alternative to q

			// Category taxonomy.
			'categories__in'              => null,
			'categories__not_in'          => null,
			'categories_field'            => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			'categories_include_children' => true,
			'categories_relation'         => 'AND',

			// Tag taxonomy.
			'tags__in'      => null,
			'tags__not_in'  => null,
			'tags_field'    => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			'tags_relation' => 'AND',

			// Location taxonomy.
			'locations__in'      => null,
			'locations__not_in'  => null,
			'locations_field'    => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			'locations_relation' => 'AND',

			// Directory type taxonomy.
			'directories__in'      => null,
			'directories__not_in'  => null,
			'directories_field'    => self::DEFAULT_TAXONOMY_QUERY_FIELD,
			'directories_relation' => 'AND',

			// Meta fields
			'rating'               => null,   // number
			'rating_compare'       => '>=',
			'review_count'         => null,   // number
			'review_count_compare' => '>=',
			'view_count'           => null,   // number
			'view_count_compare'   => '>=',

			'address'     => null,   // string
			'website'     => null,   // string
			'email'       => null,   // string
			'phone'       => null,   // string
			'fax'         => null,   // string
			'zip'         => null,   // string
			'distance'    => null,   // sring
			'latitude'    => null,   // number
			'longitude'   => null,   // number
			'price'       => null,   // mixed | number | array
			'price_range' => null,   // string
			'featured'    => null,   // true || false

			'search_relation' => 'AND',
			'meta_relation'   => 'AND',
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
		$args = $this->parse_categories_args( $args );
		$args = $this->parse_tags_args( $args );
		$args = $this->parse_locations_args( $args );
		$args = $this->parse_directories_args( $args );

		// Parse meta args.
		$args = $this->parse_meta_args( $args );

		// Clean default args.
		$args = array_diff_key( $args, $this->get_default_args() );

		return apply_filters( 'directorist_listings_query_parsed_args', $args, $this->query_id );
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

		// Rating
		if ( ! empty( $args['rating'] ) ) {
			$query['rating'] = array(
				'key'     => directorist_get_rating_field_meta_key(),
				'value'   => $args['rating'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['rating_compare'] ),
			);
		}

		// Reviews count
		if ( ! empty( $args['review_count'] ) ) {
			$query['review_count'] = array(
				'key'     => directorist_get_review_count_field_meta_key(),
				'value'   => $args['review_count'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['review_count_compare'] ),
			);
		}

		// Views count
		if ( ! empty( $args['view_count'] ) ) {
			$query['view_count'] = array(
				'key'     => '_atbdp_post_views_count',
				'value'   => $args['view_count'],
				'type'    => 'NUMERIC',
				'compare' => $this->validate_meta_compare( $args['view_count_compare'] ),
			);
		}

		// Featured listings query
		if ( isset( $args['featured'] ) && ! is_null( $args['featured'] ) ) {
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
		$this->do_query( $args = array(), $query_id = null );
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

	public function do_query( $args = array(), $query_id = null ) {
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
