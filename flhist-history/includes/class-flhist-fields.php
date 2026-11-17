<?php
/**
 * ACF field group, validation, admin list columns, notices, and help tab.
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FLHist_Fields {

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register_field_group' ) );
		add_filter( 'acf/load_value/key=field_flhist_type', array( __CLASS__, 'default_type' ) );
		add_filter( 'acf/validate_value/key=field_flhist_year_end', array( __CLASS__, 'validate_year_end' ), 10, 4 );
		add_filter( 'acf/validate_value/key=field_flhist_other_years', array( __CLASS__, 'validate_other_years' ), 10, 4 );
		add_filter( 'acf/validate_value/key=field_flhist_video_url', array( __CLASS__, 'validate_video_url' ), 10, 4 );

		add_filter( 'admin_post_thumbnail_html', array( __CLASS__, 'photo_hint' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notice_acf_missing' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice_missing_alt' ) );

		add_filter( 'manage_' . FLHist_Post_Types::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . FLHist_Post_Types::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-' . FLHist_Post_Types::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'list_query' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'list_filters' ) );

		add_action( 'load-post.php', array( __CLASS__, 'help_tab' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'help_tab' ) );
	}

	/**
	 * The "History Entry" field group. Fixed fields only, so free ACF works.
	 */
	public static function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$doc_fields = array();
		for ( $i = 1; $i <= 3; $i++ ) {
			$doc_fields[] = array(
				'key'           => 'field_flhist_doc_' . $i . '_file',
				'label'         => sprintf( /* translators: %d: document number */ __( 'PDF %d', 'flhist' ), $i ),
				'name'          => 'flhist_doc_' . $i . '_file',
				'type'          => 'file',
				'instructions'  => __( 'Optional. Upload a PDF, such as a program or a newsletter.', 'flhist' ),
				'return_format' => 'id',
				'library'       => 'all',
				'mime_types'    => 'pdf',
				'wrapper'       => array( 'width' => '50' ),
			);
			$doc_fields[] = array(
				'key'          => 'field_flhist_doc_' . $i . '_label',
				'label'        => sprintf( /* translators: %d: document number */ __( 'PDF %d label', 'flhist' ), $i ),
				'name'         => 'flhist_doc_' . $i . '_label',
				'type'         => 'text',
				'instructions' => __( 'The link text visitors see. Example: 1994 Annual Report.', 'flhist' ),
				'wrapper'      => array( 'width' => '50' ),
			);
		}

		$fields = array_merge(
			array(
				array(
					'key'           => 'field_flhist_type',
					'label'         => __( 'Type', 'flhist' ),
					'name'          => 'flhist_type',
					'type'          => 'taxonomy',
					'instructions'  => __( 'Choose Chair for a past chair. Choose Milestone for an event or a first in the Section.', 'flhist' ),
					'required'      => 1,
					'taxonomy'      => FLHist_Post_Types::TAX_TYPE,
					'field_type'    => 'radio',
					'allow_null'    => 0,
					'add_term'      => 0,
					'save_terms'    => 1,
					'load_terms'    => 1,
					'return_format' => 'id',
				),
				array(
					'key'          => 'field_flhist_year_start',
					'label'        => __( 'Start year', 'flhist' ),
					'name'         => 'flhist_year_start',
					'type'         => 'number',
					'instructions' => __( 'Four digits. For a chair, the year the term began. For a milestone, the year it happened.', 'flhist' ),
					'required'     => 1,
					'min'          => 1900,
					'max'          => 2100,
					'step'         => 1,
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_flhist_year_end',
					'label'        => __( 'End year', 'flhist' ),
					'name'         => 'flhist_year_end',
					'type'         => 'number',
					'instructions' => __( 'Optional. The year a chair\'s term ended. Leave blank for milestones and for a single year.', 'flhist' ),
					'min'          => 1900,
					'max'          => 2100,
					'step'         => 1,
					'wrapper'      => array( 'width' => '50' ),
				),
				array(
					'key'          => 'field_flhist_display_date',
					'label'        => __( 'Date label', 'flhist' ),
					'name'         => 'flhist_display_date',
					'type'         => 'text',
					'instructions' => __( 'Optional. Replaces the years shown on the card. Example: 2019 to 2020 (co-chairs).', 'flhist' ),
				),
				array(
					'key'          => 'field_flhist_other_years',
					'label'        => __( 'Other years', 'flhist' ),
					'name'         => 'flhist_other_years',
					'type'         => 'text',
					'instructions' => __( 'Optional. Extra years that are not between the start and end year, separated by commas. Example: 2013, 2015. Visitors who search for one of these years will find this entry.', 'flhist' ),
				),
				array(
					'key'          => 'field_flhist_city',
					'label'        => __( 'City', 'flhist' ),
					'name'         => 'flhist_city',
					'type'         => 'text',
					'instructions' => __( 'Optional. The chair\'s city, such as Lubbock. It shows on the card and can be searched.', 'flhist' ),
				),
				array(
					'key'          => 'field_flhist_snippet',
					'label'        => __( 'Short summary', 'flhist' ),
					'name'         => 'flhist_snippet',
					'type'         => 'textarea',
					'instructions' => __( 'One or two sentences, about 200 characters. This shows on the card.', 'flhist' ),
					'required'     => 1,
					'rows'         => 3,
					'new_lines'    => '',
				),
				array(
					'key'          => 'field_flhist_video_url',
					'label'        => __( 'YouTube link', 'flhist' ),
					'name'         => 'flhist_video_url',
					'type'         => 'url',
					'instructions' => __( 'Optional. Paste a YouTube link, such as https://www.youtube.com/watch?v=... or https://youtu.be/...', 'flhist' ),
				),
			),
			$doc_fields
		);

		acf_add_local_field_group(
			array(
				'key'                   => 'group_flhist_entry',
				'title'                 => __( 'History Entry', 'flhist' ),
				'fields'                => $fields,
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => FLHist_Post_Types::POST_TYPE,
						),
					),
				),
				'position'              => 'acf_after_title',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => array( 'discussion', 'comments', 'slug', 'author', 'send-trackbacks' ),
			)
		);
	}

	/**
	 * Preselect Chair on a new entry.
	 *
	 * @param mixed $value Current value.
	 * @return mixed
	 */
	public static function default_type( $value ) {
		if ( empty( $value ) ) {
			$term = get_term_by( 'name', 'Chair', FLHist_Post_Types::TAX_TYPE );
			if ( $term ) {
				return (int) $term->term_id;
			}
		}
		return $value;
	}

	/**
	 * End year must be at least the start year.
	 */
	public static function validate_year_end( $valid, $value, $field, $input ) {
		if ( true !== $valid || '' === $value || null === $value ) {
			return $valid;
		}
		$start = isset( $_POST['acf']['field_flhist_year_start'] ) ? (int) wp_unslash( $_POST['acf']['field_flhist_year_start'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies the nonce before validation.
		if ( $start && (int) $value < $start ) {
			return __( 'The end year cannot be earlier than the start year.', 'flhist' );
		}
		return $valid;
	}

	/**
	 * Only YouTube links are allowed.
	 */
	/**
	 * A note under the Photo box so the committee picks a suitable picture.
	 */
	public static function photo_hint( $content, $post_id ) {
		if ( FLHist_Post_Types::POST_TYPE !== get_post_type( $post_id ) ) {
			return $content;
		}
		return $content . '<p class="description">' . esc_html__( 'On the timeline the photo is shown as a tall rectangle (4 wide by 5 tall), so the edges of a wide photo are cut off. In the pop-up window the whole photo shows. Use a photo at least 400 pixels wide, and put faces in the middle.', 'flhist' ) . '</p>';
	}

	/**
	 * Other years: four-digit years separated by commas or spaces.
	 */
	public static function validate_other_years( $valid, $value, $field, $input ) {
		if ( true !== $valid || '' === trim( (string) $value ) ) {
			return $valid;
		}
		foreach ( preg_split( '/[\s,;]+/', trim( (string) $value ) ) as $part ) {
			if ( ! preg_match( '/^\d{4}$/', $part ) ) {
				return __( 'Use four-digit years separated by commas, such as 2013, 2015.', 'flhist' );
			}
		}
		return $valid;
	}

	public static function validate_video_url( $valid, $value, $field, $input ) {
		if ( true !== $valid || '' === $value || null === $value ) {
			return $valid;
		}
		if ( '' === self::youtube_id( $value ) ) {
			return __( 'Use a YouTube link, such as https://www.youtube.com/watch?v=... or https://youtu.be/...', 'flhist' );
		}
		return $valid;
	}

	/**
	 * Pull the video ID out of a YouTube watch or youtu.be link.
	 *
	 * @param string $url URL.
	 * @return string Video ID, or '' if the URL is not a YouTube link.
	 */
	public static function youtube_id( $url ) {
		$parts = wp_parse_url( trim( (string) $url ) );
		if ( empty( $parts['host'] ) ) {
			return '';
		}
		$host = strtolower( preg_replace( '/^www\./', '', $parts['host'] ) );
		$id   = '';
		if ( 'youtu.be' === $host && ! empty( $parts['path'] ) ) {
			$id = trim( $parts['path'], '/' );
		} elseif ( in_array( $host, array( 'youtube.com', 'm.youtube.com', 'youtube-nocookie.com' ), true ) ) {
			if ( ! empty( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
				if ( ! empty( $query['v'] ) ) {
					$id = (string) $query['v'];
				}
			}
			if ( '' === $id && ! empty( $parts['path'] ) && preg_match( '#^/(embed|shorts)/([^/]+)#', $parts['path'], $m ) ) {
				$id = $m[2];
			}
		}
		return preg_match( '/^[A-Za-z0-9_-]{6,20}$/', $id ) ? $id : '';
	}

	/**
	 * Tell the admin that ACF is required. Does not fatal.
	 */
	public static function notice_acf_missing() {
		if ( function_exists( 'acf_add_local_field_group' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Past Chairs History Timeline needs Advanced Custom Fields.', 'flhist' ) . '</strong> ';
		echo esc_html__( 'Install and activate the ACF plugin (the free version works). Until then, entries cannot be given years, summaries, videos, or PDFs.', 'flhist' ) . '</p></div>';
	}

	/**
	 * Warn on the edit screen when the photo has no alt text.
	 */
	public static function notice_missing_alt() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || FLHist_Post_Types::POST_TYPE !== $screen->post_type || 'post' !== $screen->base ) {
			return;
		}
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
		$thumb   = $post_id ? get_post_thumbnail_id( $post_id ) : 0;
		if ( $thumb && '' === trim( (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) ) ) {
			$link = get_edit_post_link( $thumb );
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'This entry\'s photo has no alt text. Alt text describes the photo for people who cannot see it.', 'flhist' ) . ' ';
			if ( $link ) {
				echo '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Add alt text in the media library.', 'flhist' ) . '</a>';
			}
			echo '</p></div>';
		}
		$meta = $thumb ? wp_get_attachment_metadata( $thumb ) : array();
		if ( $thumb && ! empty( $meta['width'] ) && (int) $meta['width'] < 400 ) {
			echo '<div class="notice notice-warning"><p>' . esc_html(
				sprintf(
					/* translators: %d: photo width in pixels */
					__( 'This entry\'s photo is only %d pixels wide. It may look blurry on the timeline. Use a photo at least 400 pixels wide.', 'flhist' ),
					(int) $meta['width']
				)
			) . '</p></div>';
		}
	}

	/* ---------------------------------------------------------------- List screen */

	public static function columns( $columns ) {
		return array(
			'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'         => __( 'Name', 'flhist' ),
			'flhist_type'   => __( 'Type', 'flhist' ),
			'flhist_years'  => __( 'Years', 'flhist' ),
			'flhist_decade' => __( 'Decade', 'flhist' ),
			'flhist_city'   => __( 'City', 'flhist' ),
			'flhist_photo'  => __( 'Photo', 'flhist' ),
			'flhist_video'  => __( 'Video', 'flhist' ),
		);
	}

	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'flhist_type':
			case 'flhist_decade':
				$tax   = 'flhist_type' === $column ? FLHist_Post_Types::TAX_TYPE : FLHist_Post_Types::TAX_DECADE;
				$terms = wp_get_object_terms( $post_id, $tax, array( 'fields' => 'names' ) );
				echo ! empty( $terms ) && ! is_wp_error( $terms ) ? esc_html( implode( ', ', $terms ) ) : '&ndash;';
				break;
			case 'flhist_years':
				$start = get_post_meta( $post_id, 'flhist_year_start', true );
				$end   = get_post_meta( $post_id, 'flhist_year_end', true );
				if ( '' === $start ) {
					echo '&ndash;';
				} elseif ( '' !== $end && $end !== $start ) {
					echo esc_html( $start . ' to ' . $end );
				} else {
					echo esc_html( $start );
				}
				break;
			case 'flhist_city':
				$city = trim( (string) get_post_meta( $post_id, 'flhist_city', true ) );
				echo '' !== $city ? esc_html( $city ) : '&ndash;';
				break;
			case 'flhist_photo':
				echo has_post_thumbnail( $post_id ) ? esc_html__( 'Yes', 'flhist' ) : '&ndash;';
				break;
			case 'flhist_video':
				echo '' !== (string) get_post_meta( $post_id, 'flhist_video_url', true ) ? esc_html__( 'Yes', 'flhist' ) : '&ndash;';
				break;
		}
	}

	public static function sortable_columns( $columns ) {
		$columns['flhist_years'] = 'flhist_years';
		return $columns;
	}

	/**
	 * Sort by year and apply the type and decade filters on the list screen.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function list_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || FLHist_Post_Types::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}
		$orderby = $query->get( 'orderby' );
		if ( '' === $orderby || 'flhist_years' === $orderby ) {
			$query->set( 'meta_key', 'flhist_year_start' );
			$query->set( 'orderby', array( 'meta_value_num' => $query->get( 'order' ) ? $query->get( 'order' ) : 'ASC', 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}

		$tax_query = array();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read only list filters.
		if ( ! empty( $_GET['flhist_type_filter'] ) ) {
			$tax_query[] = array(
				'taxonomy' => FLHist_Post_Types::TAX_TYPE,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['flhist_type_filter'] ) ),
			);
		}
		if ( ! empty( $_GET['flhist_decade_filter'] ) ) {
			$tax_query[] = array(
				'taxonomy' => FLHist_Post_Types::TAX_DECADE,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET['flhist_decade_filter'] ) ),
			);
		}
		// phpcs:enable
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
	}

	public static function list_filters( $post_type ) {
		if ( FLHist_Post_Types::POST_TYPE !== $post_type ) {
			return;
		}
		$filters = array(
			'flhist_type_filter'   => array( FLHist_Post_Types::TAX_TYPE, __( 'All types', 'flhist' ) ),
			'flhist_decade_filter' => array( FLHist_Post_Types::TAX_DECADE, __( 'All decades', 'flhist' ) ),
		);
		foreach ( $filters as $param => $info ) {
			$terms = get_terms( array( 'taxonomy' => $info[0], 'hide_empty' => true, 'orderby' => 'name' ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$current = isset( $_GET[ $param ] ) ? sanitize_title( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<select name="' . esc_attr( $param ) . '"><option value="">' . esc_html( $info[1] ) . '</option>';
			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->slug ) . '"' . selected( $current, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';
		}
	}

	/* ---------------------------------------------------------------- Help tab */

	public static function help_tab() {
		$screen = get_current_screen();
		if ( ! $screen || FLHist_Post_Types::POST_TYPE !== $screen->post_type ) {
			return;
		}
		$screen->add_help_tab(
			array(
				'id'      => 'flhist_how_to',
				'title'   => __( 'How to add an entry', 'flhist' ),
				'content' => '<ol>'
					. '<li>' . esc_html__( 'Type the chair\'s name, or the milestone title, in the title box.', 'flhist' ) . '</li>'
					. '<li>' . esc_html__( 'Pick Chair or Milestone, then enter the start year. Add an end year for a chair\'s term.', 'flhist' ) . '</li>'
					. '<li>' . esc_html__( 'Write a short summary of one or two sentences. This is the only text an early entry needs.', 'flhist' ) . '</li>'
					. '<li>' . esc_html__( 'Optional: add a photo, the full story in the main text box, a YouTube link, and up to three PDFs.', 'flhist' ) . '</li>'
					. '<li>' . esc_html__( 'Click Publish. The entry appears on the timeline page in the right decade.', 'flhist' ) . '</li>'
					. '</ol>',
			)
		);
	}
}
