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

	// STEP 1 go through the last day of entries, and write them to a csv file
	$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24);
	$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24); 
	$all_form_entries = GFAPI::get_entries( 10, $search_criteria );

	$output = "" . "\r\n";

	foreach ( $all_form_entries as $entry => $value ) {

		$output .= $value['1'] . ',';    // Todays Date 
		$output .= $value['2.6'] . ',';  // Last Name
		$output .= $value['2.3'] . ',';  // First Name
		$output .= $value['3.1'] . $value['3.2'] . ',';  // Street 
		$output .= $value['3.3'] . ',';  // City
		$output .= $value['3.4'] . $value['3.5'] . ',';  // state_zip
		$output .= $value['4']   . ',';  // home phone
		$output .= $value['53']	 . ',';  // cell phone
		$output .= $value['5']   . ',';  // email
		$output .= $value['7'] . ',';    // unemployed
		$output .= $value['45'] . ',';   // how long
		$output .= $value['8.1'] . $value['8.2'] . $value['8.3'] . $value['8.4'] . $value['8.5'] .  $value['8.6'] . ',';    // Age Range
		$output .= $value['9'] . ',';    // In US Military?
		$output .= $value['10'] . ',';   // Have you participated in the SCSEP Program in the past?
		$output .= $value['15.1'] . $value['15.2'] . $value['15.3'] . $value['15.4'] . $value['15.5'] . $value['15.6'] . ',';   // What is counted as income
		$output .= $value['17'] . ',';   // Total annual counted income
		$output .= $value['18'] . ',';	 // Total # of people in family;
		$output .= $value['16.1'] . $value['16.2'] . $value['16.3'] . $value['16.4'] . $value['16.5'] .',';  // What is NOT counted as income 
		
		$desired_job = $value['20.1'] . $value['20.2'] . $value['20.3'] . $value['20.4'] . $value['20.5'] . $value['20.6'];  // Desired Occupational Goal
		$output .= str_replace( array('.', ','), ' ' , $desired_job ) . ',';
		$output .= $value['28.1'] . $value['28.2'] . $value['28.3'] . $value['28.4'] . $value['28.5'] . $value['28.6'] . ','; // Amount of time You Will Need for Occupational Skills Training
		$output .= $value['30'] . ','; // Are you looking for FT or PT work
		$output .= $value['31'] . ','; // Highest level of education
		$output .= $value['32'] . ','; // If College Degree studied
		$output .= $value['33'] . ','; // Primary source of transportation
		$output .= $value['35'] . ','; // Homeless or at risk
		$output .= $value['36.1'] . $value['36.2'] . $value['36.3'] . ','; // How would you describe your math skills
		$output .= $value['37.1'] . $value['37.2'] . $value['37.3'] . ','; // What is your computer skill level?
		$output .= $value['38.1'] . $value['38.2'] . $value['38.3'] . ',';	// How would you describe your writing skills?
		$output .= $value['39.1'] . $value['39.2'] . $value['39.3'] . $value['39.4'] . ','; // Please identify other agencies you are currently receiving services from
		
		$previous_jobs = unserialize( $value['47']); // Previous Work Experience
		foreach( $previous_jobs as $job => $field ){
			$output .=  $field["Employer's Name"] . ',';
			$output .=  $field["Job Title / Description of Work"] . ',';
			$output .=  $field["Start Date / End Date"] . ',';
			$output .=  str_replace( array('.', ','), '' , $field["City, State"] ) . ',';

		}

		$output .= ','; 
		$output .= "\r\n";
	}
	
	$upload_dir = wp_upload_dir();

	$myfile = fopen("wp-content/uploads/csv_export/" . date('Y-m-d') . ".csv", "w") or die("Unable to open file!");
	$csv_contents = $output;
	
	fwrite($myfile, $csv_contents);
	fclose($myfile);

	//Is the current timestamp greater than the time the last export was sent, plus one day? it has to be more than 24 hours for exports to get emailed.

	if ( time() > ( get_option( 'gform_last_export_sent' ) + 86400) ) {

		// Send an email using the latest csv file
		$attachments = 'wp-content/uploads/csv_export/' . date('Y-m-d') . '.csv';
		$headers[] = 'From: WordPress <you@yourdomain.org>';
		$headers[] = 'Bcc: bcc@yourdomain.com';
		wp_mail( 'you@yourdomain', 'Automatic Form Export', 'CSV export is attached to this message', $headers, $attachments);

		$current_timestamp = time();
		update_option('gforms_last_export_sent', $current_timestamp);

	}

	
}
add_shortcode( 'export_csv', 'gforms_automated_export');