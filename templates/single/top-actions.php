<?php
/**
 * @author  wpWax
 * @since   6.6
 * @version 7.7.0
 */

use \Directorist\Directorist_Single_Listing;

if ( ! defined( 'ABSPATH' ) ) exit;

$listing = Directorist_Single_Listing::instance();
?>

<div class="directorist-signle-listing-top directorist-flex directorist-align-center directorist-justify-content-between">
	<?php if( $listing->display_back_link() ): ?>

	<a href="javascript:history.back()" class="directorist-single-listing-action directorist-return-back directorist-btn directorist-btn-sm directorist-btn-light"><?php directorist_icon( 'las la-arrow-left' ); ?> <span class="directorist-single-listing-action__text"><?php esc_html_e( 'Go Back', 'directorist'); ?></span> </a>

	<?php endif; ?>

	<div class="directorist-single-listing-quick-action directorist-flex directorist-align-center directorist-justify-content-between">

		<?php if ( $listing->submit_link() ): ?>
			<a href="<?php echo esc_url( $listing->submit_link() ); ?>" class="directorist-single-listing-action directorist-btn directorist-btn-sm directorist-btn-light directorist-btn-success directorist-signle-listing-top__btn-continue"><span class="directorist-single-listing-action__text"><?php esc_html_e( 'Continue', 'directorist' ); ?></span> </a>
		<?php endif; ?>

		<a href="<?php echo esc_url( $listing->edit_link() ) ?>" class="directorist-single-listing-action directorist-btn directorist-btn-sm directorist-btn-light directorist-signle-listing-top__btn-edit">
			<?php directorist_icon( 'las la-edit' ); ?>
			<span class="directorist-single-listing-action__text"><?php esc_html_e( 'Edit', 'directorist' ); ?></span>	
		</a>

		<?php $listing->quick_actions_template(); ?>

	</div>

</div>