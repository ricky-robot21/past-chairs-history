<?php
/**
 * The content of one entry, placed inside a <template> in the card.
 * The script copies it into the dialog when a visitor opens the entry.
 * The video is not in here. The script adds the player when the dialog opens.
 *
 * Variable: $e (prepared entry, see FLHist_Render::prepare).
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="flhist-detail">
	<div class="flhist-detail__top<?php echo $e['thumb_id'] ? ' flhist-detail__top--has-photo' : ''; ?>">
		<?php if ( $e['thumb_id'] ) : ?>
			<figure class="flhist-detail__figure">
				<?php
				// Alt text comes from the media library.
				echo wp_get_attachment_image( $e['thumb_id'], 'large', false, array( 'class' => 'flhist-detail__img' ) );
				?>
				<?php if ( '' !== $e['caption'] ) : ?>
					<figcaption class="flhist-detail__caption"><?php echo esc_html( $e['caption'] ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="flhist-detail__head">
			<p class="flhist-card__meta">
				<span class="flhist-card__year"><?php echo esc_html( $e['date_label'] ); ?></span>
				<span class="flhist-badge flhist-badge--<?php echo esc_attr( $e['type_slug'] ); ?>"><?php echo esc_html( $e['type'] ); ?></span>
				<?php if ( '' !== $e['city'] ) : ?>
					<span class="flhist-card__city"><?php echo esc_html( $e['city'] ); ?></span>
				<?php endif; ?>
			</p>
			<h2 class="flhist-detail__title" data-flhist-title><?php echo esc_html( $e['title'] ); ?></h2>
		</div>
	</div>

	<?php if ( '' !== $e['body_html'] ) : ?>
		<div class="flhist-detail__body"><?php echo $e['body_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through the_content filters, which sanitize post content. ?></div>
	<?php elseif ( '' !== $e['snippet'] ) : ?>
		<p class="flhist-detail__lead"><?php echo esc_html( $e['snippet'] ); ?></p>
	<?php endif; ?>

	<?php if ( $e['video_id'] ) : ?>
		<div class="flhist-detail__video" data-flhist-video="<?php echo esc_attr( $e['video_id'] ); ?>" data-title="<?php echo esc_attr( sprintf( /* translators: %s: entry name */ __( 'Video: %s', 'flhist' ), $e['title'] ) ); ?>"></div>
	<?php endif; ?>

	<?php if ( '' !== $e['transcript_html'] ) : ?>
		<details class="flhist-detail__transcript">
			<summary><?php esc_html_e( 'Video transcript', 'flhist' ); ?></summary>
			<div class="flhist-detail__transcript-body"><?php echo $e['transcript_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped with esc_html before wpautop. ?></div>
		</details>
	<?php endif; ?>

	<?php if ( $e['docs'] ) : ?>
		<div class="flhist-detail__docs">
			<h3 class="flhist-detail__docs-title"><?php esc_html_e( 'Documents', 'flhist' ); ?></h3>
			<ul class="flhist-detail__docs-list">
				<?php foreach ( $e['docs'] as $doc ) : ?>
					<li>
						<a href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $doc['label'] ); ?>
							<span class="flhist-sr"><?php esc_html_e( '(opens in a new tab)', 'flhist' ); ?></span>
						</a>
						<span class="flhist-detail__docs-meta">(<?php echo esc_html( trim( $doc['type'] . ( $doc['size'] ? ', ' . $doc['size'] : '' ) ) ); ?>)</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</div>
