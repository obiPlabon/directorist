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
		$this->fields = get_term_meta( $type, 'review_form_fields', true );
	}

	public function get_data() {
		return isset( $this->fields['groups'], $this->fields['groups'][0] ) ? $this->fields['groups'][0] : array();
	}

	public function get_form_label() {
		$data = $this->get_data();
		return ( ! empty( $data ) ? $data['label'] : '' );
	}

	public function get_active_fields_key() {
		$data = $this->get_data();
		return ( ! empty( $data ) ? $data['fields'] : array() );
	}

	public function get_fields() {
		return ( ! empty( $this->fields['fields'] ) ? $this->fields['fields'] : array() );
	}

	public function get_field( $field_id ) {
		$fields = $this->get_fields();
		return ( isset( $fields[ $field_id ] ) ? $fields[ $field_id ] : array() );
	}

	public function is_field_active( $field_id ) {
		return in_array( $field_id, $this->get_active_fields_key(), true );
	}

	public function rating_criteria_exists() {
		if ( ! $this->is_field_active( 'rating' ) ) {
			return false;
		}

		$field = $this->get_field( 'rating' );

		return ( isset( $field['rating_type'], $field['rating_criteria'] ) && $field['rating_type'] === 'criteria' && ! empty( $field['rating_criteria'] ) );
	}

	public function get_rating_criteria() {
		$criteria = array();

		if ( $this->rating_criteria_exists() ) {
			$_criteria = array_filter( explode( PHP_EOL, $this->get_field_prop( 'rating', 'rating_criteria', array() ) ) );

			if ( ! empty( $_criteria ) ) {
				foreach ( $_criteria as $_criterion ) {
					$key = 'criteria_' . sanitize_key( $_criterion );
					$criteria[ $key ] = strip_tags( $_criterion );
				}
			}
		}

		return $criteria;
	}

	public function get_accepted_media() {
		return array(
			'image/jpeg',
			'image/jpg',
			'image/png',
		);
	}

	public function get_field_prop( $field_key, $prop_key, $default = false ) {
		$field = $this->get_field( $field_key );
		return ( isset( $field[ $prop_key ] ) && $field[ $prop_key ] !== '' ) ? $field[ $prop_key ] : $default;
	}

	public function get_media_max_upload_size() {
		return min( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ), wp_convert_hr_to_bytes( $this->get_field_prop( 'media', 'file_size', '2MB' ) ) );
	}
}
