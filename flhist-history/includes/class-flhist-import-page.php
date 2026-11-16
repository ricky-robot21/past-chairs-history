<?php
/**
 * Admin page: upload a CSV to add or update history entries.
 * History Entries > Import. Uses the same importer as WP-CLI.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Import_Page {

	const SLUG = 'flhist-import';

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		}
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=' . FLHist_Post_Types::POST_TYPE,
			__( 'Import entries', 'flhist' ),
			__( 'Import', 'flhist' ),
			'edit_others_posts',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function render() {
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to import entries.', 'flhist' ) );
		}

		$result = null;
		$lines  = array();
		$error  = '';
		$dry    = true;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
		if ( isset( $_POST['flhist_import_nonce'] ) ) {
			check_admin_referer( 'flhist_import', 'flhist_import_nonce' );
			$dry    = ! isset( $_POST['flhist_do_import'] );
			$update = ! empty( $_POST['flhist_update'] );
			$file   = isset( $_FILES['flhist_csv'] ) ? $_FILES['flhist_csv'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below.

			if ( ! $file || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
				$error = __( 'Choose a CSV file first.', 'flhist' );
			} elseif ( 'csv' !== strtolower( pathinfo( sanitize_file_name( $file['name'] ), PATHINFO_EXTENSION ) ) ) {
				$error = __( 'The file must be a .csv file. In Excel or Google Sheets, use Save As or Download, then CSV.', 'flhist' );
			} elseif ( $file['size'] > 2 * MB_IN_BYTES ) {
				$error = __( 'The file is too large. Keep it under 2 MB.', 'flhist' );
			} else {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- photo downloads can be slow.
				$log    = function ( $line ) use ( &$lines ) {
					$lines[] = $line;
				};
				$result = FLHist_Importer::run( $file['tmp_name'], $dry, $update, $log );
				if ( is_wp_error( $result ) ) {
					$error  = $result->get_error_message();
					$result = null;
				}
			}
		}

		$template = FLHIST_URL . 'data/import-template.csv';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import entries', 'flhist' ); ?></h1>
			<p><?php esc_html_e( 'Add many entries at once from a spreadsheet saved as CSV. Start with the template, fill in one row per entry, and upload it. Always preview first. A preview changes nothing.', 'flhist' ); ?></p>
			<p><a href="<?php echo esc_url( $template ); ?>" download><?php esc_html_e( 'Download the CSV template', 'flhist' ); ?></a></p>

			<?php if ( $error ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
			<?php endif; ?>

			<?php if ( $result ) : ?>
				<div class="notice <?php echo $result['errors'] ? 'notice-warning' : 'notice-success'; ?>">
					<p><strong>
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: heading, 2: created, 3: updated, 4: skipped, 5: problems */
								__( '%1$s %2$d new, %3$d updated, %4$d skipped, %5$d rows with problems.', 'flhist' ),
								$dry ? __( 'Preview only. Nothing was changed.', 'flhist' ) : __( 'Import finished.', 'flhist' ),
								$result['created'],
								$result['updated'],
								$result['skipped'],
								count( $result['errors'] )
							)
						);
						?>
					</strong></p>
					<?php if ( $dry && ! $result['errors'] ) : ?>
						<p><?php esc_html_e( 'This looks right. Choose the same file again and click Import now.', 'flhist' ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $result['errors'] ) : ?>
					<h2><?php esc_html_e( 'Rows with problems', 'flhist' ); ?></h2>
					<ul class="ul-disc">
						<?php foreach ( $result['errors'] as $problem ) : ?>
							<li><?php echo esc_html( $problem ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $lines ) : ?>
					<h2><?php esc_html_e( 'What happens, row by row', 'flhist' ); ?></h2>
					<pre style="max-height:20rem;overflow:auto;background:#fff;border:1px solid #c3c4c7;padding:1rem;"><?php echo esc_html( implode( "\n", $lines ) ); ?></pre>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'flhist_import', 'flhist_import_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="flhist_csv"><?php esc_html_e( 'CSV file', 'flhist' ); ?></label></th>
						<td><input type="file" name="flhist_csv" id="flhist_csv" accept=".csv,text/csv" required></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Existing entries', 'flhist' ); ?></th>
						<td>
							<label><input type="checkbox" name="flhist_update" value="1" <?php checked( ! empty( $_POST['flhist_update'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>> <?php esc_html_e( 'Update entries that already exist (same name and start year). Otherwise they are skipped.', 'flhist' ); ?></label>
						</td>
					</tr>
				</table>
				<p>
					<button type="submit" class="button button-secondary" name="flhist_preview" value="1"><?php esc_html_e( 'Preview', 'flhist' ); ?></button>
					<button type="submit" class="button button-primary" name="flhist_do_import" value="1"><?php esc_html_e( 'Import now', 'flhist' ); ?></button>
				</p>
			</form>

			<h2><?php esc_html_e( 'Tips', 'flhist' ); ?></h2>
			<ul class="ul-disc">
				<li><?php esc_html_e( 'Required columns: title, type (Chair or Milestone), year_start, snippet.', 'flhist' ); ?></li>
				<li><?php esc_html_e( 'Photos and PDFs must be web addresses that start with https://. Imported photos need alt text. Add it in the Media Library afterward.', 'flhist' ); ?></li>
				<li><?php esc_html_e( 'A row with a problem is reported and skipped. The other rows still import.', 'flhist' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
