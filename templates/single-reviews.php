<?php
/**
 * Single view advanced review template.
 *
 * @author  wpWax
 * @since   x
 * @version x
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Directorist\Helper;
use Directorist\Directorist_Single_Listing as Directorist_Entry;
use wpWax\Directorist\Review\Walker as Review_Walker;

$listing = Directorist_Entry::instance();
?>

<div class="directorist-review-container">
	<div class="directorist-review-content">
		<div class="directorist-review-content__header">
			<h3>Foodies Ratings <span>452</span></h3>
			<a href="#directorist-add-review" class="directorist-btn directorist-btn-primary"><span class="fa fa-star"></span> Write a Review</a>
		</div><!-- ends: .directorist-review-content__header -->

		<?php if ( have_comments() ) : ?>

			<div class="directorist-review-content__overview">
				<div class="directorist-review-content__overview__rating">
					<span class="directorist-rating-point">4.6</span>
					<span class="directorist-rating-stars">
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
						<i class="fa fa-star"></i>
					</span>
					<span class="directorist-rating-overall">653 reviews</span>
				</div>
				<div class="directorist-review-content__overview__benchmarks">
					<div class="directorist-benchmark-single">
						<label>Food</label>
						<progress value="5" max="5"> 5.0 </progress>
						<strong>5.0</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Location</label>
						<progress value="4.5" max="5"> 4.5 </progress>
						<strong>4.5</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Service</label>
						<progress value="4.7" max="5"> 4.7 </progress>
						<strong>4.7</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Quality</label>
						<progress value="5" max="5"> 5.0 </progress>
						<strong>5.0</strong>
					</div>
					<div class="directorist-benchmark-single">
						<label>Price</label>
						<progress value="4.2" max="5"> 4.2 </progress>
						<strong>4.2</strong>
					</div>
				</div>
			</div><!-- ends: .directorist-review-content__overview -->

			<ul class="commentlist directorist-review-content__reviews">
				<?php wp_list_comments( array(
					'avatar_size'  => 50,
					'walker'       => new Review_Walker(),
					'format' => 'html5',
					// 'callback'     => '\wpWax\Directorist\Review\comments_callback',
					// 'end-callback' => '\wpWax\Directorist\Review\comments_end_callback',
				) ); ?>
			</ul>

			<?php
			if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
				echo '<nav class="directorist-pagination">';
				paginate_comments_links( array(
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'type'      => 'list',
				) );
				echo '</nav>';
			endif;
			?>
		<?php else : ?>
			<p class="directorist-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'directorist' ); ?></p>
		<?php endif; ?>
	</div><!-- ends: .directorist-review-content -->
</div>

<?php
$args = array(
	'class_container'    => 'directorist-card directorist-card-rating-block',
	'title_reply'        => __( 'Leave a Review', 'directorist' ),
	'title_reply_before' => '<div class="directorist-card__header"><h4 id="reply-title" class="directorist-card__header--title "><span class="' . atbdp_icon_type() . '-star" aria-hidden="true"></span>',
	'title_reply_after'  => '</h4></div>',
	'class_form'         => 'directorist-card__body comment-form',
);

comment_form( $args );
