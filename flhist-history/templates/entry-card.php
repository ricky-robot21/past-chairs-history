<?php
/**
 * One entry card.
 *
 * Variable: $e (prepared entry, see FLHist_Render::prepare).
 * Compact entries show the year, name, badge, and summary only.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes   = 'flhist-entry flhist-entry--' . $e['type_slug'] . ( $e['is_rich'] ? ' flhist-entry--rich' : ' flhist-entry--compact' );
$has_photo = (bool) $e['thumb_id'];
?>
<li class="<?php echo esc_attr( $classes ); ?>"
	id="entry-<?php echo esc_attr( $e['slug'] ); ?>"
	data-type="<?php echo esc_attr( $e['type_slug'] ); ?>"
	data-decade="<?php echo esc_attr( $e['decade'] ); ?>"
	data-year-start="<?php echo esc_attr( $e['year_start'] ); ?>"
	data-year-end="<?php echo esc_attr( $e['year_end'] ); ?>"
		data-other-years="<?php echo esc_attr( implode( ' ', $e['other_years'] ) ); ?>"
	data-search="<?php echo esc_attr( $e['search'] ); ?>">
	<article class="flhist-card<?php echo $has_photo ? ' flhist-card--has-photo' : ''; ?>">
		<?php if ( $has_photo ) : ?>
			<div class="flhist-card__media">
				<?php
				// Alt text comes from the media library. WordPress outputs alt="" when it is blank.
				echo wp_get_attachment_image( $e['thumb_id'], 'medium', false, array( 'class' => 'flhist-card__img', 'loading' => 'lazy' ) );
				?>
			</div>
		<?php endif; ?>
		<div class="flhist-card__body">
			<p class="flhist-card__meta">
				<span class="flhist-card__year"><?php echo esc_html( $e['date_label'] ); ?></span>
				<span class="flhist-badge flhist-badge--<?php echo esc_attr( $e['type_slug'] ); ?>"><?php echo esc_html( $e['type'] ); ?></span>
				<?php if ( '' !== $e['city'] ) : ?>
					<span class="flhist-card__city"><?php echo esc_html( $e['city'] ); ?></span>
				<?php endif; ?>
			</p>
			<h3 class="flhist-card__title"><?php echo esc_html( $e['title'] ); ?></h3>
			<?php if ( '' !== $e['snippet'] ) : ?>
				<p class="flhist-card__snippet"><?php echo esc_html( $e['snippet'] ); ?></p>
			<?php endif; ?>
			<?php // Every entry opens a dialog, so a visitor can step through all of them. ?>
				<button type="button" class="flhist-button" data-flhist-open aria-haspopup="dialog">
					<?php echo esc_html( $e['is_rich'] ? __( 'Read more', 'flhist' ) : __( 'Details', 'flhist' ) ); ?>
					<span class="flhist-sr"><?php echo esc_html( ' ' . sprintf( /* translators: %s: entry name */ __( 'about %s', 'flhist' ), $e['title'] ) ); ?></span>
				</button>
		</div>
	</article>
	<?php // Inert until the dialog opens, so photos and text inside do not load with the page. ?>
	<template data-flhist-detail><?php echo FLHist_Render::template( 'entry-detail', array( 'e' => $e ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the template. ?></template>
</li>
