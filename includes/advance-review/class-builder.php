<?php
/**
 * Comment from builder class.
 *
 * @package wpWax\Directorist
 * @subpackage Review
 * @since 7.x
 */
namespace wpWax\Directorist\Review;

defined( 'ABSPATH' ) || die();

class Builder {

	protected $fields = array();

	private static $instance = array();

	public static function get( $post_id ) {
		if ( ! isset( self::$instance[ $post_id ] ) || is_null( self::$instance[ $post_id ] ) ) {
			self::$instance[ $post_id ] = new self( $post_id );
		}

		return self::$instance[ $post_id ];
	}

	private function __construct( $post_id ) {
		$type = get_post_meta( $post_id, '_directory_type', true );
		$this->fields = get_term_meta( $type, 'review_config', true );
	}

	/**
	 * Get rating type.
	 *
	 * @return string
	 */
	public function get_rating_type() {
		return $this->get_field( 'rating_type', 'single' );
	}

	public function is_rating_type_single() {
		return $this->get_field( 'rating_type', 'single' ) === 'single';
	}

	public function is_rating_type_criteria() {
		return ( $this->get_rating_type() === 'multiple' && count( $this->get_rating_criteria() ) > 0 );
	}

	/**
	 * Get the list of criteria.
	 *
	 * @return array
	 */
	public function get_rating_criteria() {
		$criteria = array();

		if ( ! empty( $this->get_field( 'rating_criteria', '' ) ) ) {
			$_criteria = array_filter( explode( PHP_EOL, $this->get_field( 'rating_criteria', '' ) ) );

			if ( ! empty( $_criteria ) ) {
				foreach ( $_criteria as $_criterion ) {
					$key = 'criteria_' . sanitize_key( $_criterion );
					$criteria[ $key ] = strip_tags( $_criterion );
				}
			}
		}

		return $criteria;
	}

	public function is_attachments_enabled() {
		return $this->get_field( 'enable_attachments', false );
	}

	public function is_attachments_required() {
		return $this->get_field( 'attachments_required', false );
	}

	public function get_max_number_attachments() {
		return absint( $this->get_field( 'max_attachments', 3 ) );
	}

	/**
	 * Get the supported media mime types.
	 *
	 * @return array
	 */
	public function get_accepted_attachments_types() {
		return array(
			'image/jpeg',
			'image/jpg',
			'image/png',
		);
	}

	public function get_attachments_upload_size() {
		$size  = absint( $this->get_field( 'attachments_size', 2 ) );
		$size .= 'MB';

		return min( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ), wp_convert_hr_to_bytes( $size ) );
	}

	public function get_name_label( $default = '' ) {
		return $this->get_field( 'name_label', $default );
	}

	public function get_name_placeholder( $default = '' ) {
		return $this->get_field( 'name_placeholder', $default );
	}

	public function get_email_label( $default = '' ) {
		return $this->get_field( 'email_label', $default );
	}

	public function get_email_placeholder( $default = '' ) {
		return $this->get_field( 'email_placeholder', $default );
	}

	public function get_website_label( $default = '' ) {
		return $this->get_field( 'website_label', $default );
	}

	public function get_website_placeholder( $default = '' ) {
		return $this->get_field( 'website_placeholder', $default );
	}

	public function get_comment_label( $default = '' ) {
		return $this->get_field( 'comment_label', $default );
	}

	public function get_comment_placeholder( $default = '' ) {
		return $this->get_field( 'comment_placeholder', $default );
	}

	protected function get_field( $field_key, $defult = false ) {
		$field_key = "review_{$field_key}";
		return ( ( isset( $this->fields[ $field_key ] ) && $this->fields[ $field_key ] !== '' ) ? $this->fields[ $field_key ] : $default );
	}
}
