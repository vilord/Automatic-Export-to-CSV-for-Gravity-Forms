<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}


if ( class_exists( 'GFAPI' ) ){

	$forms = GFAPI::get_forms();

	foreach ( $forms as $form ) {

		$form_id = $form['id'];

		wp_clear_scheduled_hook( 'csv_export_' . $form_id );

	}

}
