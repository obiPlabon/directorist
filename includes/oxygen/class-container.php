<?php
/**
 * Oxygen container class.
 * 
 * @author wpWax
 */
namespace wpWax\Directorist\Oxygen;

defined( 'ABSPATH' ) || die();

use CT_Toolbar;

class Container {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->load_files();

		// Register +Add Directorist section
		add_action( 'oxygen_add_plus_sections', array( $this, 'register_add_plus_section' ) );

		// Register +Add Directorist subsections
		// oxygen_add_plus_{$id}_section_content
		add_action( 'oxygen_add_plus_directorist_category_section_content', array( $this, 'register_add_plus_subsections' ) );

		// add_action( 'init',       array( $this, 'init_callback' ) );
	}

	public function load_files() {
		// elements without controls
		include_once 'elements/add-listing.php';
		include_once 'elements/checkout.php';
		include_once 'elements/custom-registration.php';
		include_once 'elements/payment-receipt.php';
		include_once 'elements/transaction-failure.php';
		include_once 'elements/user-dashboard.php';
		include_once 'elements/user-login.php';
		include_once 'elements/single-details.php';

		// elements with controls
		include_once 'elements/all-listing.php';
		include_once 'elements/category.php';
		include_once 'elements/location.php';
		include_once 'elements/tag.php';
		include_once 'elements/all-categories.php';
		include_once 'elements/all-locations.php';
		include_once 'elements/author-profile.php';
		include_once 'elements/search-listing.php';
		include_once 'elements/search-result.php';
	}

	/**
	 * Register directorist section tab
	 *
	 * @return void
	 */
	public function register_add_plus_section() {
		CT_Toolbar::oxygen_add_plus_accordion_section( 'directorist_category', __( 'Directorist', 'directorist' ) );
	}

	/**
	 * Register directorist elemnents section hook
	 *
	 * @return void
	 */
	public function register_add_plus_subsections() {
		/**
		 * Oxygen is creating this hook dynamically
		 * 
		 * button place value is being used to create this hook.
		 * eg. directorist::elements -> oxygen_add_plus_directorist_elements
		 * 
		 * @see \OxygenElementHelper /component-framework/api/oxygen.element-helper.class.php
		 */
		do_action( 'oxygen_add_plus_directorist_elements' );
	}

	function init_callback() {

		// we don't want wooco redirects to work when builder is loading
		if ( defined("SHOW_CT_BUILDER") || defined("OXYGEN_IFRAME") ) {
			remove_action( 'template_redirect', 'wc_template_redirect' );
		}

	}

	public static function get_listing_items( $controlId, Element $element ) {
		$args = array(
			'name'        => __( 'Listing Items', 'directorist' ),
			'slug'        => $controlId,
			'placeholder' => __( 'Select items ...', 'directorist' ),
		);

		$posts = get_posts( array(
			'post_type'      => ATBDP_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1
		) );

		if ( ! empty( $posts ) ) {
			$args['value'] = wp_list_pluck( $posts, 'post_title', 'ID' );
		}

		return self::get_custom_select2_html( $args, $element );
	}

	public static function get_locations_control( $controlId, Element $element ) {
		$args = array(
			'name'        => __( 'Locations', 'directorist' ),
			'slug'        => $controlId,
			'placeholder' => __( 'Select locations ...', 'directorist' ),
		);

		$terms = get_terms( array(
			'taxonomy'   => ATBDP_LOCATION,
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			$args['value'] = wp_list_pluck( $terms, 'name', 'slug' );
		}

		return self::get_custom_select2_html( $args, $element );
	}

	public static function get_tags_control( $controlId, Element $element ) {
		$args = array(
			'name'        => __( 'Tags', 'directorist' ),
			'slug'        => $controlId,
			'placeholder' => __( 'Select tags ...', 'directorist' ),
		);

		$terms = get_terms( array(
			'taxonomy'   => ATBDP_TAGS,
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			$args['value'] = wp_list_pluck( $terms, 'name', 'slug' );
		}

		return self::get_custom_select2_html( $args, $element );
	}

	public static function get_categories_control( $controlId, Element $element ) {
		$args = array(
			'name'        => __( 'Categories', 'directorist' ),
			'slug'        => $controlId,
			'placeholder' => __( 'Select categories ...', 'directorist' ),
		);

		$terms = get_terms( array(
			'taxonomy'   => ATBDP_CATEGORY,
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			$args['value'] = wp_list_pluck( $terms, 'name', 'slug' );
		}

		return self::get_custom_select2_html( $args, $element );
	}

	public static function get_custom_select2_html( $args = array(), Element $element ) {
		$args = wp_parse_args( $args, array(
			'name'        => 'Control Label',
			'slug'        => \uniqid( 'control_slug' ),
			'value'       => array(),
			'placeholder' => 'Set custom placeholder text ...'
		) );

		$controlId  = \uniqid( 'directorist_control_id_' . $args['slug'] );
		$objectName = $element->getSlug();

		ob_start();
		?>
		<div class="oxygen-control-row">
			<div class="oxygen-control-wrapper">
				<label class="oxygen-control-label"><?php echo esc_html( $args['name'] ); ?></label>
				<div class="oxygen-control">
					<select id="<?php echo esc_attr( $controlId ); ?>" name="<?php echo esc_attr( $controlId ); ?>[]" multiple="multiple"
						ng-init="initSelect2( '<?php echo esc_attr( $controlId ); ?>', '<?php echo esc_attr( $args['placeholder'] ); ?>' )"
						ng-model="iframeScope.component.options[ iframeScope.component.active.id ]['model']['<?php echo esc_attr( $args['slug'] ); ?>']"
						ng-change="iframeScope.setOption( iframeScope.component.active.id, '<?php echo esc_attr( $objectName ); ?>', '<?php echo esc_attr( $args['slug'] ); ?>' )">'
						<?php foreach ( $args['value'] as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
