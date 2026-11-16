<?php
/**
 * Timeline wrapper: toolbar, decade jump bar, decade sections with entry cards.
 *
 * Variables: $groups (decade label => entries), $order, $show_filters, $count.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid     = wp_unique_id( 'flhist-' );
$decades = array_keys( $groups );
sort( $decades ); // The decade menu always runs oldest to newest.
?>
<div class="flhist" data-flhist data-order="<?php echo esc_attr( $order ); ?>">
	<noscript><style>.flhist [data-flhist-toolbar], .flhist [data-flhist-open], .flhist [data-flhist-jump] { display: none; }</style></noscript>
	<?php if ( ! $groups ) : ?>
		<p class="flhist-empty"><?php esc_html_e( 'No history entries yet.', 'flhist' ); ?></p>
	<?php else : ?>
		<?php if ( $show_filters ) : ?>
			<form class="flhist-toolbar" role="search" aria-label="<?php esc_attr_e( 'Search and filter the history', 'flhist' ); ?>" data-flhist-toolbar
				data-text-one="<?php echo esc_attr__( '1 entry shown', 'flhist' ); ?>"
				data-text-many="<?php echo esc_attr__( '%d entries shown', 'flhist' ); ?>"
				data-text-oldest="<?php echo esc_attr__( 'Oldest first', 'flhist' ); ?>"
				data-text-newest="<?php echo esc_attr__( 'Newest first', 'flhist' ); ?>">
				<div class="flhist-field flhist-field--search">
					<label class="flhist-field__label" for="<?php echo esc_attr( $uid ); ?>-q"><?php esc_html_e( 'Search by name, year, or keyword', 'flhist' ); ?></label>
					<input class="flhist-field__input" type="search" id="<?php echo esc_attr( $uid ); ?>-q" name="q" autocomplete="off" data-flhist-q>
				</div>
				<div class="flhist-field">
					<label class="flhist-field__label" for="<?php echo esc_attr( $uid ); ?>-type"><?php esc_html_e( 'Type', 'flhist' ); ?></label>
					<select class="flhist-field__input" id="<?php echo esc_attr( $uid ); ?>-type" name="type" data-flhist-type>
						<option value=""><?php esc_html_e( 'All', 'flhist' ); ?></option>
						<option value="chair"><?php esc_html_e( 'Chairs', 'flhist' ); ?></option>
						<option value="milestone"><?php esc_html_e( 'Milestones', 'flhist' ); ?></option>
					</select>
				</div>
				<div class="flhist-field">
					<label class="flhist-field__label" for="<?php echo esc_attr( $uid ); ?>-decade"><?php esc_html_e( 'Decade', 'flhist' ); ?></label>
					<select class="flhist-field__input" id="<?php echo esc_attr( $uid ); ?>-decade" name="decade" data-flhist-decade>
						<option value=""><?php esc_html_e( 'All', 'flhist' ); ?></option>
						<?php foreach ( $decades as $decade ) : ?>
							<option value="<?php echo esc_attr( $decade ); ?>"><?php echo esc_html( $decade ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="flhist-toolbar__actions">
					<button type="button" class="flhist-link-button" data-flhist-sort>
						<?php echo esc_html( 'desc' === $order ? __( 'Newest first', 'flhist' ) : __( 'Oldest first', 'flhist' ) ); ?>
					</button>
					<button type="button" class="flhist-link-button" data-flhist-clear hidden><?php esc_html_e( 'Clear filters', 'flhist' ); ?></button>
				</div>
				<p class="flhist-status" role="status" aria-live="polite" aria-atomic="true" data-flhist-status>
					<?php echo esc_html( sprintf( /* translators: %d: number of entries */ _n( '%d entry shown', '%d entries shown', $count, 'flhist' ), $count ) ); ?>
				</p>
			</form>
		<?php endif; ?>

		<nav class="flhist-jump" aria-label="<?php esc_attr_e( 'Jump to a decade', 'flhist' ); ?>" data-flhist-jump>
			<ul class="flhist-jump__list">
				<?php foreach ( array_keys( $groups ) as $decade ) : ?>
					<li class="flhist-jump__item" data-decade="<?php echo esc_attr( $decade ); ?>">
						<a class="flhist-jump__link" href="#flhist-decade-<?php echo esc_attr( $decade ); ?>"><?php echo esc_html( $decade ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="flhist-none" data-flhist-none hidden>
			<p><?php esc_html_e( 'No entries match. Try a different name or year.', 'flhist' ); ?></p>
			<button type="button" class="flhist-button" data-flhist-clear><?php esc_html_e( 'Clear filters', 'flhist' ); ?></button>
		</div>

		<div class="flhist-timeline" data-flhist-timeline>
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

		<dialog class="flhist-dialog" data-flhist-dialog>
			<div class="flhist-dialog__inner">
				<div class="flhist-dialog__bar">
					<button type="button" class="flhist-link-button flhist-dialog__close" data-flhist-close>
						<span aria-hidden="true">&times;</span> <?php esc_html_e( 'Close', 'flhist' ); ?>
					</button>
				</div>
				<div class="flhist-dialog__content" data-flhist-content></div>
				<div class="flhist-dialog__nav">
					<button type="button" class="flhist-button flhist-button--quiet" data-flhist-prev>
						<span aria-hidden="true">&larr;</span> <?php esc_html_e( 'Previous', 'flhist' ); ?><span class="flhist-sr" data-flhist-prev-name></span>
					</button>
					<button type="button" class="flhist-button flhist-button--quiet" data-flhist-next>
						<?php esc_html_e( 'Next', 'flhist' ); ?><span class="flhist-sr" data-flhist-next-name></span> <span aria-hidden="true">&rarr;</span>
					</button>
				</div>
			</div>
		</dialog>
	<?php endif; ?>
</div>
