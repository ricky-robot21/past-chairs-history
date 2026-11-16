<?php
/**
 * The [flhist_timeline] shortcode, data prep, and search blob.
 *
 * Step 3 builds the real output. For now the shortcode prints a plain list so
 * the sample data can be checked on a page.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Render {

	public static function init() {
		add_shortcode( 'flhist_timeline', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * All entries in timeline order: start year, then menu order, then title.
	 *
	 * @return WP_Post[]
	 */
	public static function get_entries() {
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

	public static function shortcode( $atts ) {
		$entries = self::get_entries();
		if ( ! $entries ) {
			return '<div class="flhist"><p>' . esc_html__( 'No history entries yet.', 'flhist' ) . '</p></div>';
		}
		$html = '<div class="flhist"><ul>';
		foreach ( $entries as $entry ) {
			$types = wp_get_object_terms( $entry->ID, FLHist_Post_Types::TAX_TYPE, array( 'fields' => 'names' ) );
			$html .= '<li>' . esc_html( get_post_meta( $entry->ID, 'flhist_year_start', true ) . ' - ' . $entry->post_title . ' (' . implode( ', ', $types ) . ')' ) . '</li>';
		}
		return $html . '</ul></div>';
	}
}
