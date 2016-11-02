<?php
/*
Plugin Name: Automatic Export to CSV for Gravity Forms 
Plugin URI: http://gravitycsv.com
Description: Automatically send an email containing a CSV export of your Gravity Form entries on a schedule.
Version: 0.3.2
Author: Alex Cavender
Author URI: http://alexcavender.com/
Text Domain: gravityforms-automatic-csv-export
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die();

define( 'GF_AUTOMATIC_CSV_VERSION', '0.3.2' );



class GravityFormsAutomaticCSVExport {

	public function __construct() {

		if ( class_exists( 'GFAPI' ) ) {

			add_filter( 'cron_schedules', array($this, 'add_weekly' ) ); 
			add_filter( 'cron_schedules', array($this, 'add_monthly' ) ); 
			add_action( 'admin_init', array($this, 'gforms_create_schedules' ) );

			$forms = GFAPI::get_forms();

			foreach ( $forms as $form ) {

				$form_id = $form['id'];
				
				$enabled = 0;

				if ( isset( $form['gravityforms-automatic-csv-export']['enabled'] ) ) {
					$enabled = $form['gravityforms-automatic-csv-export']['enabled'];
				}

				if ( $enabled == 1 ) {
					add_action( 'csv_export_' . $form_id , array($this, 'gforms_automated_export' ) );
				}	
			
			}

		}
	}
	/**
		* Set up weekly schedule as an interval
		*
		* @since 0.1
		*
		* @param array $schedules.
		* @return array $schedules.
	*/
	public function add_weekly( $schedules ) {
		// add a 'weekly' schedule to the existing set
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Once Weekly')
		);
		return $schedules;
	}
	

	/**
		* Set up monthly schedule as an interval
		*
		* @since 0.1
		*
		* @param array $schedules.
		* @return array $schedules.
	*/
	public function add_monthly( $schedules ) {
		// add a 'weekly' schedule to the existing set
		$schedules['monthly'] = array(
			'interval' => 604800 * 4,
			'display' => __('Once Monthly')
		);
		return $schedules;
	}
	

	/**
		* Create schedules for each enabled form 
		*
		* @since 0.1
		*
		* @param void
		* @return void
	*/
	public function gforms_create_schedules(){

		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {

			$form_id = $form['id'];

			$enabled = 0;

			if ( isset( $form['gravityforms-automatic-csv-export']['enabled'] ) ) {
				$enabled = $form['gravityforms-automatic-csv-export']['enabled'];
			}

			if ( $enabled == 1 ) {

				if ( ! wp_next_scheduled( 'csv_export_' . $form_id ) ) {
					
					$form = GFAPI::get_form( $form_id ); 

					$frequency = $form['gravityforms-automatic-csv-export']['csv_export_frequency'];
					
					wp_schedule_event( time(), $frequency, 'csv_export_' . $form_id );
					
				}

			}

			else {

				$timestamp = wp_next_scheduled( 'csv_export_' . $form_id );
				
				wp_unschedule_event( $timestamp, 'csv_export_' . $form_id );

			}

		}

	}

	
	/**
		* Run Automated Exports
		*
		* @since 0.1
		*
		* @param void
		* @return void 
	*/
	public function gforms_automated_export() {

		$output = "";
		$form_id = explode('_', current_filter())[2];
		$form = GFAPI::get_form( $form_id ); // get form by ID 
		$search_criteria = array();

		if ( $form['gravityforms-automatic-csv-export']['search_criteria'] == 'all' ) {
			$search_criteria = array();
		}

		if ( $form['gravityforms-automatic-csv-export']['search_criteria'] == 'previous_day' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 60 * 60 * 24 );
		
			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 ); 

		}

		if ( $form['gravityforms-automatic-csv-export']['search_criteria'] == 'previous_week' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 604800000 );
		
			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 ); 

		}

		if ( $form['gravityforms-automatic-csv-export']['search_criteria'] == 'previous_month' ) {

			$search_criteria['start_date'] = date('Y-m-d', time() - 2678400000 );
		
			$search_criteria['end_date'] = date('Y-m-d', time() - 60 * 60 * 24 ); 

		}
		
	
		//get the total number of entries that the form has
		$total_entries = GFAPI::count_entries( $form_id );
		//set paging to the total number of entries for the form
		$paging = array( 'offset' => 0, 'page_size' => $total_entries );
		//Pass a non-null value to get the total count in the results
		$total_count = 0;

		$all_form_entries = GFAPI::get_entries( $form_id , $search_criteria, null, $paging, $total_count);
		

		foreach( $form['fields'] as $field ) {

            if($field->type == 'section') 
                continue;

            //don't include hidden name fields
            if( $field->type == 'name' ) {
            	if ( !empty( $field->choices) ){
	                foreach( $field->choices as $choice ) {
	                    if( $choice->isHidden == 1 )
	                        continue;
	                }
            	}
            }

            $output .= preg_replace('/[,]/', '', $field->label) . ','; 
        }

        $output .= "\r\n";

        //loop over form entries
        if ( !empty( $all_form_entries ) ){
	        foreach ( $all_form_entries as $entry ) {

	            foreach($entry as $key => $val) {
	                //skip blank values
	                if(strlen($val) > 0) {

	                    //if next value is not a decimal key but previous one was, stop appending for that field
	                    if(strpos( $key, '.' ) === false && substr($output, strlen($output)-1, 1) == ' ') {
	                        $output .= ',';
	                    }

	                    //decimal keys (EX: for a Name field which has several inputs)
	                    if (is_numeric( $key ) && strpos( $key, '.' )) {
	                        $output .= preg_replace('/[,]/', '', sanitize_text_field($val));
	                        $output .= ' ';
	                    }
	                    else if (is_int($key)) { //regular integer key for standard fields
	                        $output .= preg_replace('/[,]/', '', sanitize_text_field($val));
	                        $output .= ',';
	                    }
	                }
	            }

	            $output .= ',';
	            $output .= "\r\n";
	        }
    	}
		
		$upload_dir = wp_upload_dir();

		$baseurl = $upload_dir['baseurl'];
		
		$path = $upload_dir['path'];

		$myfile = fopen( $path . "/form_" . $form_id . '_' . date('Y-m-d-giA') . ".csv", "w") or die("Unable to open file!");
		$csv_contents = $output;
		
		fwrite($myfile, $csv_contents);
		fclose($myfile);

		// To-Do = add to media library

		$server = $_SERVER['HTTP_HOST'];

		$email_address = $form['gravityforms-automatic-csv-export']['email_address'];

		// Send an email using the latest csv file
		$attachments = $path . '/form_' . $form_id . '_' . date('Y-m-d-giA') . '.csv';
		$headers[] = 'From: WordPress <wordpress@' . $server . '>';
		//$headers[] = 'Bcc: bcc@yourdomain.com';
		wp_mail( $email_address , 'Automatic Form Export', 'CSV export is attached to this message', $headers, $attachments);
	}

}

$automatedexportclass = new GravityFormsAutomaticCSVExport();



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
