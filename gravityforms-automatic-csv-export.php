<?php

/*
	Plugin Name: Gravity Forms Automatic Export to CSV
	Plugin URI:
	Description: Simple way to automatically email with CSV export of your Gravity Form entries on a schedule.
	Version: 0.1
	Author: Alex Cavender
	License: GPL-2.0+
	License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/


define( 'GF_AUTOMATIC_CSV_VERSION', '0.1' );

add_action( 'gform_loaded', array( 'GF_Automatic_Csv_Bootstrap', 'load' ), 5 );

class GF_Automatic_Csv_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

         require_once( 'class-gf-automatic-csv-addon.php' );

        GFAddOn::register( 'GFAutomaticCSVAddOn' );
    }

}

function gf_simple_addon() {
    return GFAutomaticCSVAddOn::get_instance();
}



function gforms_automated_export() {

	//test writing 
	$myfile = fopen("wp-content/uploads/test.txt", "w") or die("Unable to open file!");
	$contents = "working";
	fwrite($myfile, $contents);
	fclose($myfile);

	/*
	// Go through the entries that match search criteria, and write them to a csv file
	$output = "";

	$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24);
	$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24); 
	$all_form_entries = GFAPI::get_entries( 1 ); // add search criteria back in


	$form = GFAPI::get_form( 1 ); // get form by ID 

	foreach( $form['fields'] as $field ) {
		$output .= preg_replace('/[.,]/', '', $field->label) . ',' ;
	}

	$output .= "\r\n";

	foreach ( $all_form_entries as $entry ) {

		for ( $i = 1; $i < 100; $i++ ){
			if ( array_key_exists( $i, $entry ) ) {
	
				$output .= preg_replace('/[.,]/', '', $entry[$i]) . ',';

			}
			
		}	

		$output .= ','; 
		$output .= "\r\n";
	}
	
	
	$upload_dir = wp_upload_dir();
	
	// To-do: Use standard WP function to upload to wp-content directory

	$myfile = fopen("wp-content/uploads/" . date('Y-m-d-gA') . ".csv", "w") or die("Unable to open file!");
	$csv_contents = $output;
	
	fwrite($myfile, $csv_contents);
	fclose($myfile);


	$email_address = $form['gravityforms-automatic-csv-export']['email_address'];

	//Is the current timestamp greater than the time the last export was sent, plus one day? it has to be more than 24 hours for exports to get emailed.

	//if ( time() > ( get_option( 'gform_last_export_sent' ) + 86400) ) {

		// Send an email using the latest csv file
		$attachments = 'wp-content/uploads/' . date('Y-m-d-gA') . '.csv';
		$headers[] = 'From: WordPress <you@yourdomain.org>';
		//$headers[] = 'Bcc: bcc@yourdomain.com';
		wp_mail( $email_address , 'Automatic Form Export', 'CSV export is attached to this message', $headers, $attachments);

		$current_timestamp = time();
		update_option('gforms_last_export_sent', $current_timestamp);

	//}
	*/
}
// add_shortcode( 'export_csv', 'gforms_automated_export');


if ( ! wp_next_scheduled( 'csv_task_hook' ) ) {
  wp_schedule_event( time(), 'daily', 'csv_task_hook' );
}

add_action( 'csv_task_hook', 'gforms_automated_export' );


