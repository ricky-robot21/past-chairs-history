<?php
/**
 * Entry post type, taxonomies, and decade auto-assign.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Post_Types {

	const POST_TYPE = 'flhist_entry';
	const TAX_TYPE  = 'flhist_type';
	const TAX_DECADE = 'flhist_decade';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		// ACF saves its fields on this hook. Run after it so the year is in place.
		add_action( 'acf/save_post', array( __CLASS__, 'on_save' ), 20 );
		// Fallback for saves that do not go through ACF (importer, WP-CLI, REST).
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'on_save' ), 20 );
	}

	/**
	 * Register the post type and both taxonomies.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'History Entries', 'flhist' ),
					'singular_name'      => __( 'History Entry', 'flhist' ),
					'add_new_item'       => __( 'Add History Entry', 'flhist' ),
					'edit_item'          => __( 'Edit History Entry', 'flhist' ),
					'new_item'           => __( 'New History Entry', 'flhist' ),
					'search_items'       => __( 'Search History Entries', 'flhist' ),
					'not_found'          => __( 'No history entries found.', 'flhist' ),
					'not_found_in_trash' => __( 'No history entries found in the trash.', 'flhist' ),
					'menu_name'          => __( 'History Entries', 'flhist' ),
					'featured_image'     => __( 'Photo', 'flhist' ),
					'set_featured_image' => __( 'Set photo', 'flhist' ),
					'remove_featured_image' => __( 'Remove photo', 'flhist' ),
					'use_featured_image' => __( 'Use as photo', 'flhist' ),
				),
				'public'              => true,
				'show_ui'             => true,
				'show_in_rest'        => true,
				'publicly_queryable'  => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-backup',
				'menu_position'       => 25,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			)
		);

		// Type: Chair or Milestone. The ACF "Type" field is the only picker, so the default box is hidden.
		register_taxonomy(
			self::TAX_TYPE,
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Entry Types', 'flhist' ),
					'singular_name' => __( 'Entry Type', 'flhist' ),
				),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_rest'       => false,
				'show_admin_column'  => false,
				'meta_box_cb'        => false,
				'rewrite'            => false,
				'query_var'          => false,
			)
		);

		// Decade: set by code from the start year. Nobody edits it by hand.
		register_taxonomy(
			self::TAX_DECADE,
			self::POST_TYPE,
			array(
				'labels'             => array(
					'name'          => __( 'Decades', 'flhist' ),
					'singular_name' => __( 'Decade', 'flhist' ),
				),
				'hierarchical'       => false,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_rest'       => false,
				'show_admin_column'  => false,
				'meta_box_cb'        => false,
				'rewrite'            => false,
				'query_var'          => false,
			)
		);
	}

	/**
	 * Create the two type terms if they are missing.
	 */
	public static function create_type_terms() {
		foreach ( array( 'Chair', 'Milestone' ) as $name ) {
			if ( ! term_exists( $name, self::TAX_TYPE ) ) {
				wp_insert_term( $name, self::TAX_TYPE );
			}
		}
	}

	/**
	 * Run after an entry is saved.
	 *
	 * @param int|string $post_id Post ID. ACF can pass other values, such as "options".
	 */
	public static function on_save( $post_id ) {
		if ( ! is_numeric( $post_id ) ) {
			return;
		}
		$post_id = (int) $post_id;
		if ( self::POST_TYPE !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		self::assign_decade( $post_id );
		self::ensure_type( $post_id );
	}

	/**
	 * Label for a year, for example 1994 gives "1990s". Returns '' for a bad year.
	 *
	 * @param mixed $year Four digit year.
	 * @return string
	 */
	public static function decade_label( $year ) {
		$year = (int) $year;
		if ( $year < 1000 || $year > 9999 ) {
			return '';
		}
		return ( floor( $year / 10 ) * 10 ) . 's';
	}

	/**
	 * Set the decade term from the start year.
	 *
	 * @param int $post_id Entry ID.
	 */
	public static function assign_decade( $post_id ) {
		$label = self::decade_label( get_post_meta( $post_id, 'flhist_year_start', true ) );
		if ( '' === $label ) {
			wp_set_object_terms( $post_id, array(), self::TAX_DECADE );
			return;
		}
		wp_set_object_terms( $post_id, $label, self::TAX_DECADE );
	}

	/**
	 * Every entry needs a type. Fall back to Chair.
	 *
	 * @param int $post_id Entry ID.
	 */
	public static function ensure_type( $post_id ) {
		$terms = wp_get_object_terms( $post_id, self::TAX_TYPE, array( 'fields' => 'ids' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			self::create_type_terms();
			wp_set_object_terms( $post_id, 'Chair', self::TAX_TYPE );
		}
	}

	/**
	 * Re-run the decade for every entry. Used on activation and after import.
	 *
	 * @return int Number of entries updated.
	 */
	public static function assign_all_decades() {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			self::assign_decade( (int) $id );
		}
		return count( $ids );
	}
}
