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
		wp_enqueue_style( 'flhist', FLHIST_URL . 'assets/css/flhist.css', array(), FLHIST_VERSION );
		wp_enqueue_script( 'flhist', FLHIST_URL . 'assets/js/flhist.js', array(), FLHIST_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
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
			'body_raw'   => $body_raw,
			'body_html'  => $body_html,
			'caption'    => $caption,
			'thumb_id'   => $thumb_id,
			'video_id'   => $video_id,
			'docs'       => $docs,
			// An entry with none of these is "compact": name, years, summary, nothing else.
			'is_rich'    => ( '' !== $body_raw || $thumb_id || $video_id || $docs ),
		);
		$entry['search'] = self::search_blob( $entry );
		return $entry;
	}

	/**
	 * Lowercase, accent-free text the JS filter searches.
	 */
	public static function search_blob( $entry ) {
		$text = implode( ' ', array( $entry['title'], $entry['type'], $entry['date_label'], $entry['snippet'], wp_strip_all_tags( $entry['body_raw'] ) ) );
		$text = remove_accents( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
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
		return self::template(
			'timeline',
			array(
				'groups'       => self::group_by_decade( $entries, $order ),
				'order'        => $order,
				'show_filters' => $show_filters,
				'count'        => count( $entries ),
			)
		);
	}
}
