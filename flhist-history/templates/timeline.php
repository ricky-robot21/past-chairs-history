<?php
/**
 * Timeline wrapper: decade sections with entry cards.
 *
 * Variables: $groups (decade label => entries), $order, $show_filters, $count.
 * The jump bar (step 5) and the toolbar (step 6) go above the list.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="flhist" data-flhist data-order="<?php echo esc_attr( $order ); ?>">
	<?php if ( ! $groups ) : ?>
		<p class="flhist-empty"><?php esc_html_e( 'No history entries yet.', 'flhist' ); ?></p>
	<?php else : ?>
		<div class="flhist-timeline">
			<?php foreach ( $groups as $decade => $entries ) : ?>
				<section class="flhist-decade" id="flhist-decade-<?php echo esc_attr( $decade ); ?>" data-decade="<?php echo esc_attr( $decade ); ?>" aria-labelledby="flhist-decade-title-<?php echo esc_attr( $decade ); ?>">
					<h2 class="flhist-decade__title" id="flhist-decade-title-<?php echo esc_attr( $decade ); ?>"><?php echo esc_html( $decade ); ?></h2>
					<ol class="flhist-list">
						<?php
						foreach ( $entries as $e ) {
							echo FLHist_Render::template( 'entry-card', array( 'e' => $e ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the template.
						}
						?>
					</ol>
				</section>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
