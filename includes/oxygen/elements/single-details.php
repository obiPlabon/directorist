<?php
/**
 * Single details element class.
 * 
 * @author wpWax
 */
namespace wpWax\Directorist\Oxygen;

use Directorist\Helper;

class SingleDetails extends Element {

	public function name() {
		return esc_html__( 'Single Details', 'directorist' );
	}

	public function slug() {
		return 'directorist-single-details';
	}

	public function render( $options, $defaults, $content ) {
		if ( is_singular( ATBDP_POST_TYPE ) && is_main_query() ) {
			$content = Helper::get_template_contents( 'single-contents' );

			if ( Helper::is_legacy_mode() ) {
				$content = Helper::get_template_contents( 'single-listing/content-wrapper' );
			}
		}

		echo $content;
	}
}

new SingleDetails();
