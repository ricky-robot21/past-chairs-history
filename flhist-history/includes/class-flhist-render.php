<?php
/**
 * The [flhist_timeline] shortcode, data prep, and search blob.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Render {

	public static function init() {
		add_shortcode( 'flhist_timeline', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_action( 'save_post_page', array( __CLASS__, 'forget_timeline_page' ) );
		add_action( 'trashed_post', array( __CLASS__, 'forget_timeline_page' ) );
	}

	/**
	 * Address of the page that shows the timeline, or an empty string if none is published.
	 * Looks for a published page that contains the shortcode. A site can pick a page with
	 * the flhist_timeline_page_id filter.
	 *
	 * @return string
	 */
	public static function timeline_page_url() {
		$id = (int) apply_filters( 'flhist_timeline_page_id', 0 );
		if ( ! $id ) {
			$id = get_transient( 'flhist_timeline_page' );
			if ( false === $id ) {
				$found = get_posts(
					array(
						'post_type'      => 'page',
						'post_status'    => 'publish',
						's'              => '[flhist_timeline',
						'posts_per_page' => 5,
						'orderby'        => 'ID',
						'order'          => 'ASC',
						'fields'         => 'ids',
						'no_found_rows'  => true,
					)
				);
				$id = 0;
				foreach ( $found as $page_id ) {
					if ( has_shortcode( (string) get_post_field( 'post_content', $page_id ), 'flhist_timeline' ) ) {
						$id = (int) $page_id;
						break;
					}
				}
				set_transient( 'flhist_timeline_page', $id, 12 * HOUR_IN_SECONDS );
			}
		}
		$url = $id ? get_permalink( $id ) : '';
		return $url ? $url : '';
	}

	/** Clear the remembered timeline page when any page changes. */
	public static function forget_timeline_page() {
		delete_transient( 'flhist_timeline_page' );
	}

	/**
	 * Address of one entry on the timeline, or an empty string.
	 *
	 * @param WP_Post $post Entry.
	 * @return string
	 */
	public static function entry_url( $post ) {
		$page = self::timeline_page_url();
		return ( $page && 'publish' === $post->post_status && '' !== $post->post_name ) ? $page . '#entry-' . $post->post_name : '';
	}

	/**
	 * Load the CSS and JS only on pages that contain the shortcode.
	 */
	public static function maybe_enqueue() {
		$post = get_post();
		if ( $post && has_shortcode( $post->post_content, 'flhist_timeline' ) ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueue the assets. Also called from the shortcode, so a page builder that
	 * stores shortcodes outside post content still gets the styles.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'flhist', FLHIST_URL . 'assets/css/flhist.css', array(), self::asset_version( 'assets/css/flhist.css' ) );
		wp_enqueue_script( 'flhist', FLHIST_URL . 'assets/js/flhist.js', array(), self::asset_version( 'assets/js/flhist.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}

	/**
	 * Version string for an asset: the plugin version plus the file's modified time.
	 * Browsers fetch a fresh copy whenever the file changes, with no manual version bump.
	 *
	 * @param string $file Path inside the plugin folder.
	 * @return string
	 */
	private static function asset_version( $file ) {
		$time = @filemtime( FLHIST_DIR . $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file just falls back to the plugin version.
		return $time ? FLHIST_VERSION . '.' . $time : FLHIST_VERSION;
	}

	/**
	 * Published entries in timeline order: start year, then menu order, then title.
	 *
	 * @return WP_Post[]
	 */
	public static function get_posts() {
		return get_posts(
			array(
				'post_type'      => FLHist_Post_Types::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_key'       => 'flhist_year_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => array( 'meta_value_num' => 'ASC', 'menu_order' => 'ASC', 'title' => 'ASC' ),
			)
		);
	}

	/**
	 * Turn a post into the plain array the templates use.
	 *
	 * @param WP_Post $post Entry.
	 * @return array
	 */
	public static function prepare( $post ) {
		$id    = $post->ID;
		$start = (int) get_post_meta( $id, 'flhist_year_start', true );
		$end   = (int) get_post_meta( $id, 'flhist_year_end', true );

		$types = wp_get_object_terms( $id, FLHist_Post_Types::TAX_TYPE );
		$type  = ( ! empty( $types ) && ! is_wp_error( $types ) ) ? $types[0] : null;

		$label = trim( (string) get_post_meta( $id, 'flhist_display_date', true ) );
		if ( '' === $label ) {
			$label = ( $end && $end !== $start ) ? $start . ' to ' . $end : (string) $start;
		}

		$body_raw = trim( (string) $post->post_content );
		$thumb_id = (int) get_post_thumbnail_id( $id );
		if ( $thumb_id && ! wp_get_attachment_image_url( $thumb_id, 'medium' ) ) {
			$thumb_id = 0; // The photo was deleted or its file is missing. Do not show an empty box.
		}
		$video_id = FLHist_Fields::youtube_id( get_post_meta( $id, 'flhist_video_url', true ) );

		$docs = array();
		for ( $n = 1; $n <= 3; $n++ ) {
			$file_id = (int) get_post_meta( $id, 'flhist_doc_' . $n . '_file', true );
			$url     = $file_id ? wp_get_attachment_url( $file_id ) : '';
			if ( ! $url ) {
				continue;
			}
			$path  = get_attached_file( $file_id );
			$label_text = trim( (string) get_post_meta( $id, 'flhist_doc_' . $n . '_label', true ) );
			$docs[] = array(
				'url'   => $url,
				'label' => '' !== $label_text ? $label_text : get_the_title( $file_id ),
				'type'  => strtoupper( pathinfo( (string) $path, PATHINFO_EXTENSION ) ),
				'size'  => ( $path && file_exists( $path ) ) ? size_format( filesize( $path ) ) : '',
			);
		}

		// Full text and photo caption are only needed for the dialog.
		$body_html = '';
		if ( '' !== $body_raw ) {
			$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- content filters expect a current post.
			setup_postdata( $post );
			$body_html = apply_filters( 'the_content', $body_raw );
			wp_reset_postdata();
		}
		$caption = $thumb_id ? trim( wp_strip_all_tags( (string) wp_get_attachment_caption( $thumb_id ) ) ) : '';

		$snippet = (string) get_post_meta( $id, 'flhist_snippet', true );
		$city    = trim( (string) get_post_meta( $id, 'flhist_city', true ) );
		$transcript = trim( (string) get_post_meta( $id, 'flhist_transcript', true ) );
		$others  = self::other_years( get_post_meta( $id, 'flhist_other_years', true ) );
		$entry   = array(
			'id'         => $id,
			'slug'       => $post->post_name,
			'title'      => get_the_title( $post ),
			'type'       => $type ? $type->name : 'Chair',
			'type_slug'  => $type ? $type->slug : 'chair',
			'year_start' => $start,
			'year_end'   => $end ? $end : $start,
			'date_label' => $label,
			'decade'     => FLHist_Post_Types::decade_label( $start ),
			'snippet'    => $snippet,
			'city'       => $city,
			'transcript' => $transcript,
			'transcript_html' => ( '' !== $transcript && $video_id ) ? wpautop( esc_html( $transcript ) ) : '',
			'other_years' => $others,
			'body_raw'   => $body_raw,
			'body_html'  => $body_html,
			'caption'    => $caption,
			'thumb_id'   => $thumb_id,
			'video_id'   => $video_id,
			'docs'       => $docs,
			// An entry with none of these is "compact": name, years, summary. It still opens a short dialog.
			'is_rich'    => ( '' !== $body_raw || $thumb_id || $video_id || $docs ),
		);
		$entry['search'] = self::search_blob( $entry );
		return $entry;
	}

	/**
	 * Turn "2013, 2015" into array( 2013, 2015 ). Anything that is not a four-digit year is dropped.
	 *
	 * @param string $raw Field value.
	 * @return int[]
	 */
	public static function other_years( $raw ) {
		$years = array();
		foreach ( preg_split( '/[\s,;]+/', trim( (string) $raw ) ) as $part ) {
			if ( preg_match( '/^\d{4}$/', $part ) ) {
				$years[] = (int) $part;
			}
		}
		$years = array_values( array_unique( $years ) );
		sort( $years );
		return $years;
	}

	/**
	 * Lowercase, accent-free text the JS filter searches.
	 */
	public static function search_blob( $entry ) {
		$text = implode( ' ', array( $entry['title'], $entry['type'], $entry['date_label'], implode( ' ', $entry['other_years'] ), $entry['city'], $entry['snippet'], wp_strip_all_tags( $entry['body_raw'] ), $entry['transcript'] ) );
		$text = remove_accents( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
		$text = str_replace( array( "'", "\u{2019}", "\u{2018}", '`' ), '', $text ); // O'Brien and OBrien both match.
		return trim( preg_replace( '/\s+/', ' ', mb_strtolower( $text ) ) );
	}

	/**
	 * Group prepared entries by decade, in order.
	 *
	 * @param array  $entries Prepared entries, oldest first.
	 * @param string $order   asc or desc.
	 * @return array Map of decade label to list of entries.
	 */
	public static function group_by_decade( $entries, $order ) {
		if ( 'desc' === $order ) {
			$entries = array_reverse( $entries );
		}
		$groups = array();
		foreach ( $entries as $entry ) {
			$groups[ $entry['decade'] ][] = $entry;
		}
		return $groups;
	}

	/**
	 * Load a template file from templates/ with variables.
	 *
	 * @param string $name Template name without .php.
	 * @param array  $vars Variables for the template.
	 * @return string
	 */
	public static function template( $name, $vars ) {
		$file = FLHIST_DIR . 'templates/' . $name . '.php';
		if ( ! is_readable( $file ) ) {
			return '';
		}
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- limited to template variables.
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $file;
		return ob_get_clean();
	}

	/**
	 * [flhist_timeline order="asc|desc" show_filters="true|false"]
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'order'        => 'asc',
				'show_filters' => 'true',
			),
			$atts,
			'flhist_timeline'
		);
		$order        = 'desc' === strtolower( $atts['order'] ) ? 'desc' : 'asc';
		$show_filters = ! in_array( strtolower( $atts['show_filters'] ), array( 'false', '0', 'no' ), true );

		self::enqueue();

		$entries = array_map( array( __CLASS__, 'prepare' ), self::get_posts() );
		// An entry with no start year cannot be placed on the timeline. Leave it out until it has one.
		$entries = array_values(
			array_filter(
				$entries,
				function ( $entry ) {
					return $entry['year_start'] > 0;
				}
			)
		);
		$html = self::template(
			'timeline',
			array(
				'groups'       => self::group_by_decade( $entries, $order ),
				'order'        => $order,
				'show_filters' => $show_filters,
				'count'        => count( $entries ),
			)
		);
		return $html . self::structured_data( $entries );
	}

	/**
	 * JSON-LD describing the timeline as a list, so search engines can read it.
	 * Printed once per page, even if the shortcode is used twice.
	 *
	 * @param array $entries Prepared entries, oldest first.
	 * @return string A script tag, or an empty string.
	 */
	private static function structured_data( $entries ) {
		static $printed = false;
		if ( $printed || ! $entries ) {
			return '';
		}
		$printed = true;

		$page  = get_permalink();
		$page  = $page ? $page : home_url( '/' );
		$items = array();
		foreach ( $entries as $i => $entry ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $entry['title'] . ' (' . $entry['date_label'] . ')',
				'url'      => $page . '#entry-' . $entry['slug'],
			);
			if ( '' !== $entry['snippet'] ) {
				$item['description'] = $entry['snippet'];
			}
			$items[] = $item;
		}
		$data = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'name'            => wp_strip_all_tags( get_the_title() ),
			'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
		return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
	}
}
