<?php
/**
 * CSV importer. Runs from WP-CLI: wp flhist import <file.csv> [--dry-run] [--update]
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Importer {

	const COLUMNS = array(
		'title', 'type', 'year_start', 'year_end', 'display_date', 'city', 'snippet', 'body',
		'photo_url', 'video_url',
		'doc_1_url', 'doc_1_label', 'doc_2_url', 'doc_2_label', 'doc_3_url', 'doc_3_label',
	);

	public static function init() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'flhist import', array( __CLASS__, 'cli_import' ) );
		}
	}

	/**
	 * Import history entries from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the CSV file. See data/import-template.csv for the columns.
	 *
	 * [--dry-run]
	 * : Show what would happen and change nothing.
	 *
	 * [--update]
	 * : Update entries that already exist (matched by title and start year). Without this flag, existing entries are skipped.
	 *
	 * ## EXAMPLES
	 *
	 *     wp flhist import wp-content/plugins/flhist-history/data/sample-entries.csv --dry-run
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Flags.
	 */
	public static function cli_import( $args, $assoc_args ) {
		$dry    = ! empty( $assoc_args['dry-run'] );
		$update = ! empty( $assoc_args['update'] );
		$result = self::run( $args[0], $dry, $update, array( 'WP_CLI', 'log' ) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '%s: %d created, %d updated, %d skipped, %d rows with problems.', $dry ? 'Dry run (nothing was changed)' : 'Done', $result['created'], $result['updated'], $result['skipped'], count( $result['errors'] ) ) );
		foreach ( $result['errors'] as $error ) {
			WP_CLI::warning( $error );
		}
		if ( $result['errors'] ) {
			WP_CLI::halt( 1 );
		}
		WP_CLI::success( $dry ? 'Dry run finished.' : 'Import finished.' );
	}

	/**
	 * Run an import.
	 *
	 * @param string   $file   CSV path.
	 * @param bool     $dry    Change nothing.
	 * @param bool     $update Update existing entries.
	 * @param callable $log    Receives one line of text at a time.
	 * @return array|WP_Error
	 */
	public static function run( $file, $dry, $update, $log ) {
		if ( ! is_readable( $file ) ) {
			return new WP_Error( 'flhist_file', 'Cannot read the file: ' . $file );
		}
		$handle = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'flhist_header', 'The file is empty.' );
		}
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $header[0] ); // Strip a byte order mark from Excel exports.
		$header    = array_map( 'trim', $header );
		$missing   = array_diff( array( 'title', 'type', 'year_start', 'snippet' ), $header );
		if ( $missing ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'flhist_columns', 'The CSV is missing these columns: ' . implode( ', ', $missing ) );
		}

		FLHist_Post_Types::create_type_terms();

		$out  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array() );
		$line = 1;
		$base = dirname( $file );

		while ( false !== ( $cells = fgetcsv( $handle ) ) ) {
			$line++;
			if ( 1 === count( $cells ) && ( null === $cells[0] || '' === trim( $cells[0] ) ) ) {
				continue; // Blank line.
			}
			$row = array();
			foreach ( $header as $i => $name ) {
				$row[ $name ] = isset( $cells[ $i ] ) ? trim( $cells[ $i ] ) : '';
			}

			$problems = self::validate_row( $row );
			if ( $problems ) {
				$out['errors'][] = sprintf( 'Row %d (%s): %s', $line, $row['title'] ? $row['title'] : 'no title', implode( ' ', $problems ) );
				continue;
			}

			$existing = self::find_existing( $row['title'], (int) $row['year_start'] );
			if ( $existing && ! $update ) {
				$out['skipped']++;
				call_user_func( $log, sprintf( 'Skip: "%s" (%s) already exists. Use --update to change it.', $row['title'], $row['year_start'] ) );
				continue;
			}

			$verb = $existing ? 'Update' : 'Create';
			call_user_func( $log, sprintf( '%s: "%s" (%s, %s)', $verb, $row['title'], $row['type'], $row['year_start'] ) );
			if ( $dry ) {
				$out[ $existing ? 'updated' : 'created' ]++;
				continue;
			}

			$id = self::save_row( $row, $existing, $base, $log, $out['errors'], $line );
			if ( is_wp_error( $id ) ) {
				$out['errors'][] = sprintf( 'Row %d (%s): %s', $line, $row['title'], $id->get_error_message() );
				continue;
			}
			$out[ $existing ? 'updated' : 'created' ]++;
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( ! $dry ) {
			FLHist_Post_Types::assign_all_decades();
		}
		return $out;
	}

	/**
	 * Check one row. Returns a list of plain language problems.
	 */
	private static function validate_row( $row ) {
		$problems = array();
		if ( '' === $row['title'] ) {
			$problems[] = 'The title is empty.';
		}
		if ( ! in_array( $row['type'], array( 'Chair', 'Milestone' ), true ) ) {
			$problems[] = 'Type must be Chair or Milestone.';
		}
		if ( ! preg_match( '/^\d{4}$/', $row['year_start'] ) ) {
			$problems[] = 'Start year must be four digits.';
		}
		if ( '' !== $row['year_end'] ) {
			if ( ! preg_match( '/^\d{4}$/', $row['year_end'] ) ) {
				$problems[] = 'End year must be four digits or blank.';
			} elseif ( preg_match( '/^\d{4}$/', $row['year_start'] ) && (int) $row['year_end'] < (int) $row['year_start'] ) {
				$problems[] = 'End year is earlier than the start year.';
			}
		}
		if ( '' === $row['snippet'] ) {
			$problems[] = 'The short summary is empty.';
		}
		if ( '' !== $row['video_url'] && '' === FLHist_Fields::youtube_id( $row['video_url'] ) ) {
			$problems[] = 'The video link is not a YouTube link.';
		}
		return $problems;
	}

	private static function find_existing( $title, $year ) {
		$found = get_posts(
			array(
				'post_type'      => FLHist_Post_Types::POST_TYPE,
				'post_status'    => 'any',
				'title'          => $title,
				'meta_key'       => 'flhist_year_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $year, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	private static function save_row( $row, $existing, $base, $log, &$errors, $line ) {
		$post = array(
			'post_type'    => FLHist_Post_Types::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => sanitize_text_field( $row['title'] ),
			'post_content' => wp_kses_post( $row['body'] ),
		);
		if ( $existing ) {
			$post['ID'] = $existing;
			$id         = wp_update_post( $post, true );
		} else {
			$id = wp_insert_post( $post, true );
		}
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		self::set_field( $id, 'flhist_year_start', (int) $row['year_start'] );
		self::set_field( $id, 'flhist_year_end', '' === $row['year_end'] ? '' : (int) $row['year_end'] );
		self::set_field( $id, 'flhist_display_date', sanitize_text_field( $row['display_date'] ) );
		self::set_field( $id, 'flhist_city', sanitize_text_field( $row['city'] ) );
		self::set_field( $id, 'flhist_snippet', sanitize_textarea_field( $row['snippet'] ) );
		self::set_field( $id, 'flhist_video_url', esc_url_raw( $row['video_url'] ) );
		wp_set_object_terms( $id, $row['type'], FLHist_Post_Types::TAX_TYPE );

		if ( '' !== $row['photo_url'] ) {
			$att = self::sideload( $row['photo_url'], $id, $base );
			if ( is_wp_error( $att ) ) {
				$errors[] = sprintf( 'Row %d (%s): photo not added. %s', $line, $row['title'], $att->get_error_message() );
			} else {
				set_post_thumbnail( $id, $att );
				if ( '' === trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) ) ) {
					call_user_func( $log, '  Note: the photo has no alt text yet. Add it in the media library.' );
				}
			}
		}

		for ( $n = 1; $n <= 3; $n++ ) {
			$url = $row[ 'doc_' . $n . '_url' ];
			if ( '' === $url ) {
				continue;
			}
			$att = self::sideload( $url, $id, $base );
			if ( is_wp_error( $att ) ) {
				$errors[] = sprintf( 'Row %d (%s): PDF %d not added. %s', $line, $row['title'], $n, $att->get_error_message() );
				continue;
			}
			self::set_field( $id, 'flhist_doc_' . $n . '_file', $att );
			self::set_field( $id, 'flhist_doc_' . $n . '_label', sanitize_text_field( $row[ 'doc_' . $n . '_label' ] ) );
		}

		FLHist_Post_Types::assign_decade( $id );
		return $id;
	}

	/**
	 * Save a field through ACF when it is active, so ACF can read it back. Plain meta otherwise.
	 */
	private static function set_field( $post_id, $name, $value ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $name, $value, $post_id );
		} else {
			update_post_meta( $post_id, $name, $value );
		}
	}

	/**
	 * Add a file to the media library. $src is a web address, or a path relative to the CSV.
	 * An item that came from the same source is reused, so a re-run does not duplicate it.
	 *
	 * @return int|WP_Error Attachment ID.
	 */
	private static function sideload( $src, $parent_id, $base ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_flhist_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $src, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		if ( preg_match( '#^https?://#i', $src ) ) {
			$tmp = download_url( $src );
			if ( is_wp_error( $tmp ) ) {
				return $tmp;
			}
			$name = basename( wp_parse_url( $src, PHP_URL_PATH ) );
		} else {
			$path = realpath( $base . '/' . $src );
			if ( ! $path || 0 !== strpos( $path, realpath( $base ) ) || ! is_readable( $path ) ) {
				return new WP_Error( 'flhist_file', 'Cannot find the file: ' . $src );
			}
			$tmp = wp_tempnam( $path );
			if ( ! copy( $path, $tmp ) ) {
				return new WP_Error( 'flhist_copy', 'Cannot copy the file: ' . $src );
			}
			$name = basename( $path );
		}

		$att = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $parent_id );
		if ( is_wp_error( $att ) ) {
			wp_delete_file( $tmp );
			return $att;
		}
		update_post_meta( $att, '_flhist_source', $src );
		return (int) $att;
	}
}
