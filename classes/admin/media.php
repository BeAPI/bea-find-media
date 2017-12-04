<?php namespace BEA\Find_Media\Admin;

use BEA\Find_Media\Singleton;
use BEA\Find_Media\DB;

class Media {
	use Singleton;

	protected function init() {
		// Hooks
		add_action( 'delete_attachment', [ $this, 'delete_attachment' ] );

		// Views
		add_filter( 'attachment_fields_to_edit', [ $this, 'modal_view' ], 20, 2 );
		add_filter( 'attachment_fields_to_edit', [ $this, 'edit_view' ], 20, 2 );

		// Custom admin columns
		add_filter( 'manage_media_columns', [ $this, 'admin_columns_header' ] );
		add_action( 'manage_media_custom_column', [ $this, 'admin_columns_values' ], 10, 2 );
		// TODO : No inline hook + css
		add_action( 'admin_head', function () {
			echo '<style type="text/css">.column-bea-find-media-counter { width: 7%; }</style>';
		} );
	}

	/**
	 * Display into media's modal, the number of indexed
	 *
	 * @param $form_fields
	 * @param $media
	 *
	 * @since  1.0.0
	 * @author Maxime CULEA
	 *
	 * @return array
	 */
	public function modal_view( $form_fields, $media ) {
		$counter = DB::get_counter( $media->ID );
		if ( ! empty( $counter ) ) {
			if ( 1 == $counter ) {
				$label = __( 'One time.' );
			} else {
				$label = sprintf( __( '%s times.', 'bea-find-media' ), esc_html( $counter ) );
			}
			$html = sprintf( '<span class="value"><a href="%s" style="vertical-align: -webkit-baseline-middle;">%s</a></span>', get_edit_post_link( $media->ID ), $label );
		} else {
			$html = sprintf( '<span>%s</span>', __( 'No usage.', 'bea-find-media' ) );
		}

		$form_fields['bea_find_media_view'] = array(
			'label'         => _n( 'Usage :', 'Usages :', $counter, 'bea-find-media' ),
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => false,
			'show_in_modal' => true,
		);

		return $form_fields;
	}

	public function edit_view( $form_fields, $media ) {
		$counter = DB::get_counter( $media->ID );
		if ( 0 === $counter ) {
			$title = __( 'This media has no usage into contents.', 'bea-find-media' );
		} elseif ( 1 == $counter ) {
			$title = __( 'One time used into contents :', 'bea-find-media' );
		} else {
			$title = sprintf( __( '%s many times used into contents :', 'bea-find-media' ), $counter );
		}

		/**
		 * $amlt = new Admin_Media_List_Table();
		 * ob_start();
		 * $amlt->display();
		 * $html = ob_get_clean();
		 */

		$data = DB::get_data( $media->ID );
		if ( empty( $data ) ) {
			$html = ' ';
		} else {
			$html = '<ul>';
			foreach ( $data as $object_type ) {
				foreach ( $object_type as $media_id ) {
					foreach ( $media_id as $content_id => $object_id ) {
						foreach ( $object_id as $types ) {
							foreach ( $types as $type ) {
								$html .= sprintf( '<li><a href="%s" target="_blank">%s</a> : %s</li>', get_edit_post_link( $content_id ), get_the_title( $content_id ), esc_html( $type ) );
							}
						}
					}
				}
			}
			$html .= '</ul>';
		}

		$form_fields['bea_find_media_edit'] = array(
			'label'         => $title,
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

		return $form_fields;
	}

	/**
	 * On media delete, remove indexed associated data
	 *
	 * @param int $media_id
	 *
	 * @since  1.0.0
	 * @author Maxime CULEA
	 */
	public function delete_attachment( $media_id ) {
		DB::delete_all_media_id( $media_id );
	}

	/**
	 * Add custom headers for attachment
	 *
	 * @param $headers
	 * @param $post_type
	 *
	 * @since  1.0.0
	 * @author Maxime CULEA
	 *
	 * @return mixed
	 */
	public function admin_columns_header( $headers, $post_type ) {
		$headers['bea-find-media-counter'] = __( 'Usages', 'bea-find-media' );

		return $headers;
	}

	/**
	 * Add values to the custom headers
	 *
	 * @param     $column_name
	 * @param int $media_id
	 *
	 * @since  1.0.0
	 * @author Maxime CULEA
	 */
	public function admin_columns_values( $column_name, $media_id ) {
		$counter = '';
		if ( 'bea-find-media-counter' === $column_name ) {
			$counter = DB::get_counter( $media_id );
		}

		// Depending on if has value, display the edit link to see them
		if ( empty( $counter ) ) {
			echo '0';
		} else {
			printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $media_id ) ), esc_html( $counter ) );
		}
	}
}