<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !function_exists('houzez_property_feed_import_fatal_handler') )
{
	function houzez_property_feed_import_fatal_handler() {

	    $error = error_get_last();

	    if ($error !== NULL) 
	    {
	    	if ( ($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR) || ($error['type'] === E_USER_NOTICE) ) 
	    	{
		        $errno   = $error["type"];
		        $errfile = $error["file"];
		        $errline = $error["line"];
		        $errstr  = $error["message"];

				$error_text = houzez_property_feed_import_format_error( $errno, $errstr, $errfile, $errline );

				global $wpdb, $instance_id;

				$current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
				$current_date = $current_date->format("Y-m-d H:i:s");

				$instance_id = isset($instance_id) && !empty($instance_id) ? $instance_id : 0;

				$wpdb->insert(
					$wpdb->prefix . "houzez_property_feed_logs_instance_log",
					array(
						'instance_id' => $instance_id,
						'post_id' => 0,
						'crm_id' => '',
						'severity' => 1,
						'entry' => $error_text,
						'log_date' => $current_date
					)
				);
			}
	    }
	}
	register_shutdown_function( "houzez_property_feed_import_fatal_handler" );
}

if ( !function_exists('houzez_property_feed_import_format_error') )
{
	// Returns a formatted version of the fatal error, showing the error message and number, filename and line number
	function houzez_property_feed_import_format_error( $errno, $errstr, $errfile, $errline ) {
		$trace = print_r( debug_backtrace( false ), true );
		$file_split = explode('/', $errfile);
		$trimmed_filename = implode('/', array_slice($file_split, -2));
		$content = 'Error:' . $errstr . '|' . $errno . '|' . $trimmed_filename . '|' . $errline . '|' . $trace;
		return $content;
	}
}

error_reporting( 0 );

$instance_id = 0;

global $wpdb, $post, $instance_id;

$keep_logs_days = (string)apply_filters( 'houzez_property_feed_keep_logs_days', '1' );

// Revert back to 1 days if anything other than numbers has been passed
// This prevent SQL injection and errors
if ( !preg_match("/^\d+$/", $keep_logs_days) )
{
    $keep_logs_days = '1';
}

// Delete logs older than 1 days
$wpdb->query( "DELETE FROM " . $wpdb->prefix . "houzez_property_feed_logs_instance WHERE start_date < DATE_SUB(NOW(), INTERVAL " . $keep_logs_days . " DAY)" );
$wpdb->query( "DELETE FROM " . $wpdb->prefix . "houzez_property_feed_logs_instance_log WHERE log_date < DATE_SUB(NOW(), INTERVAL " . $keep_logs_days . " DAY)" );

$options = get_option( 'houzez_property_feed', array() );
$imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

if ( is_array($imports) && !empty($imports) )
{
    $wp_upload_dir = wp_upload_dir();
    $uploads_dir_ok = true;
    if ( $wp_upload_dir['error'] !== FALSE )
    {
        echo "Unable to create uploads folder. Please check permissions";
        $uploads_dir_ok = false;
    }
    else
    {
        $uploads_dir = $wp_upload_dir['basedir'] . '/houzez_property_feed_import/';

        if ( ! @file_exists($uploads_dir) )
        {
            if ( ! @mkdir($uploads_dir) )
            {
                echo "Unable to create directory " . $uploads_dir;
                $uploads_dir_ok = false;
            }
        }
        else
        {
            if ( ! @is_writeable($uploads_dir) )
            {
                echo "Directory " . $uploads_dir . " isn't writeable";
                $uploads_dir_ok = false;
            }
        }
    }

    if ( $uploads_dir_ok )
    {
    	if ( apply_filters( 'houzez_property_feed_pro_active', false ) === true )
        {
	    	// Sort imports into random order. If timing out is an issue this can ensure they all get executed fairly (or as fairly as random allows)
	    	$shuffled_import_array = array();
	    	$import_id_keys = array_keys($imports);

	    	shuffle($import_id_keys);

	    	foreach( $import_id_keys as $import_id_key )
	    	{
	    		$import_settings = $imports[$import_id_key];

	    		if ( !isset($import_settings['running']) || ( isset($import_settings['running']) && $import_settings['running'] !== true ) )
	            {
	            	continue;
	            }

	            if ( isset($import_settings['deleted']) && $import_settings['deleted'] === true )
	            {
	            	continue;
	            }

	    		if ( isset($_GET['import_id']) && !empty((int)$_GET['import_id']) )
	            {
	            	if ( $import_id_key != (int)$_GET['import_id'] )
	            	{
	            		continue;
	            	}
	            }
	            if ( isset($_GET['import_ids']) )
	            {
	            	// should only exist when coming from background mode

	            	if ( empty($_GET['import_ids']) )
	            	{
	            		continue;
	            	}

	            	$explode_import_ids = explode("|", sanitize_text_field($_GET['import_ids']));

	            	if ( !in_array($import_id_key, $explode_import_ids) )
	            	{
	            		continue;
	            	}
	            }

		    	$shuffled_import_array[$import_id_key] = $imports[$import_id_key];
	    	}

	    	$imports = $shuffled_import_array;
	    }
	    else
	    {
	    	foreach ( $imports as $import_id => $import_settings )
    		{
    			if ( !isset($import_settings['running']) || ( isset($import_settings['running']) && $import_settings['running'] !== true ) )
	            {
	            	continue;
	            }

	            if ( isset($import_settings['deleted']) && $import_settings['deleted'] === true )
	            {
	            	continue;
	            }

	            if ( isset($_GET['import_id']) && !empty((int)$_GET['import_id']) )
	            {
	            	if ( $import_id != (int)$_GET['import_id'] )
	            	{
	            		continue;
	            	}
	            }
	            if ( isset($_GET['import_ids']) )
	            {
	            	// should only exist when coming from background mode (which shouldn't be in here anyway as this is non-pro)
	            	if ( empty($_GET['import_ids']) )
	            	{
	            		continue;
	            	}

	            	$explode_import_ids = explode("|", sanitize_text_field($_GET['import_ids']));

	            	if ( !in_array($import_id, $explode_import_ids) )
	            	{
	            		continue;
	            	}
	            }

	            $imports = array( $import_id => $import_settings );
	            break;
    		}
	    }

    	$frequencies = houzez_property_feed_get_import_frequencies();

    	$process_background_queue_afterwards = false;

    	$import_ids = array_keys($imports);

    	foreach ( $imports as $import_id => $import_settings )
    	{
	    	$ok_to_run_import = true;

	    	if ( !isset($import_settings['running']) || ( isset($import_settings['running']) && $import_settings['running'] !== true ) )
            {
            	$ok_to_run_import = false;
            	continue;
            }

            if ( isset($import_settings['deleted']) && $import_settings['deleted'] === true )
            {
            	$ok_to_run_import = false;
            	continue;
            }

            // ensure frequency is not a PRO one if PRO not enabled
            if ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true )
            {
                if ( isset($frequencies[$import_settings['frequency']]['pro']) && $frequencies[$import_settings['frequency']]['pro'] === true )
                {
                    $import_settings['frequency'] = 'daily';
                }
            }

        	if ( !isset($_GET['force']) )
        	{
        		// Make sure there's been no activity in the logs for at least 5 minutes for this feed as that indicates there's possible a feed running
	        	$row = $wpdb->get_row( "
	                SELECT 
	                    status_date
	                FROM 
	                    " . $wpdb->prefix . "houzez_property_feed_logs_instance
	                WHERE
	                    " . ( ( apply_filters( 'houzez_property_feed_one_import_at_a_time', false ) === false ) ? " import_id = '" . (int)$import_id . "' AND " : "" ) . "
	                	end_date = '0000-00-00 00:00:00'
	                ORDER BY status_date DESC
	                LIMIT 1
	            ", ARRAY_A);
	            if ( null !== $row )
	            {
	                if ( ( ( time() - strtotime($row['status_date']) ) / 60 ) < 5 )
	                {
	                	$ok_to_run_import = false;

	                	$message = "There has been activity within the past 5 minutes on an unfinished import. To prevent multiple imports running at the same time and possible duplicate properties being created we won't currently allow manual execution. Please try again in a few minutes or check the logs to see the status of the current import.";
	                	
	                	// if we're running it manually. Needs to be presented nicer
			            if ( isset($_GET['custom_property_import_cron']) )
			            {
			            	echo $message; die();
			            }

	                	continue;
	                }
	            }
	        }

	        $originally_ran_manually = '';
            if ( isset($_GET['custom_property_import_cron']) )
            {
            	$originally_ran_manually = 'yes';
            }
            else
            {
	            // Work out if we need to send this portal by looking
	            // at the send frequency and the last date sent
	            $last_start_date = '2000-01-01 00:00:00';
	            $row = $wpdb->get_row( $wpdb->prepare("
	                SELECT 
	                    start_date
	                FROM 
	                    " .$wpdb->prefix . "houzez_property_feed_logs_instance
	                WHERE
	                    import_id = %d
	                ORDER BY start_date DESC LIMIT 1
	            ", $import_id), ARRAY_A);
	            if ( null !== $row )
	            {
	                $last_start_date = $row['start_date'];   
	            }

	            $diff_secs = time() - strtotime($last_start_date);

	            switch ($import_settings['frequency'])
	            {
	            	case "every_fifteen_minutes":
	                {
	                    if (($diff_secs / 60 / 60) < 0.25)
	                    {
	                        $ok_to_run_import = false;
	                    }
	                    break;
	                }
	                case "hourly":
	                {
	                    if (($diff_secs / 60 / 60) < 1)
	                    {
	                        $ok_to_run_import = false;
	                    }
	                    break;
	                }
	                case "twicedaily":
	                {
	                    if (($diff_secs / 60 / 60) < 12)
	                    {
	                        $ok_to_run_import = false;
	                    }
	                    break;
	                }
	                case "exact_hours":
	                {
	                	$ok_to_run_import = false;

	                	$exact_hours = array();
	                	if ( isset($import_settings['exact_hours']) && is_array($import_settings['exact_hours']) && !empty($import_settings['exact_hours']) )
	                	{
	                		$exact_hours = $import_settings['exact_hours'];
	                		sort($exact_hours, SORT_NUMERIC); 

	                		if ( !empty($exact_hours) )
	                		{
	                			$current_date = current_datetime();
	                			$current_hour = $current_date->format('H');

                                $last_start_date_to_check = new DateTimeImmutable( $last_start_date, new DateTimeZone('UTC') );
                                $last_start_date_to_check = $last_start_date_to_check->getTimestamp();

	                			// get timestamp of today at next hour entered
	                			foreach ( $exact_hours as $hour_to_execute )
	                			{
	                				$hour_to_execute = explode(":", $hour_to_execute);
                                    $hour_to_execute = $hour_to_execute[0];

	                				if ( $current_hour >= $hour_to_execute )
                                    {
		                				$hour_to_execute = str_pad($hour_to_execute, 2, '0', STR_PAD_LEFT);

		                				$date_to_check = new DateTimeImmutable( $current_date->format('Y-m-d') . ' ' . $hour_to_execute . ':00:00', wp_timezone() );
                                        $date_to_check = $date_to_check->getTimestamp();

		                				if ( $current_date->getTimestamp() >= $date_to_check && $last_start_date_to_check < $date_to_check )
		                				{
		                					$ok_to_run_import = true;
		                					break;
		                				}
		                			}
	                			}
	                		}
	                	}
	                	
	                    break;
	                }
	                default: // daily
	                {
	                    if (($diff_secs / 60 / 60) < 24)
	                    {
	                        $ok_to_run_import = false;
	                    }
	                }
	            }
	        }

            if ($ok_to_run_import)
            {
            	$format = $import_settings['format'];
            	$background_mode = false;

            	if ( apply_filters( 'houzez_property_feed_pro_active', false ) === true )
            	{
            		$format_details = houzez_property_feed_get_import_format($format);
			    	
			    	if ( $format_details !== FALSE && isset($format_details['background_mode']) && $format_details['background_mode'] === true )
			    	{
			    		if ( isset($import_settings['background_mode']) && $import_settings['background_mode'] == 'yes' )
			    		{
				    		$background_mode = true;
				    	}
				    }

				    if ( $background_mode !== true )
				    {
				    	// Delete any queued properties
				    	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}houzez_property_feed_property_queue WHERE import_id = %d", $import_id));
				    }

	            	// Before we do anything, let's ensure there are no properties in the queue.
	            	// If there is we should process them first
	            	$property_queue = $wpdb->get_results(
						"
						SELECT
							id, instance_id
						FROM
							" . $wpdb->prefix . "houzez_property_feed_property_queue
						WHERE
							`status` = 'pending'
						LIMIT 1
						"
					);

					if ( !empty($property_queue) ) 
					{
						// Yes. There are properties queued. Let's fork a process
						// At the end of the forked process maybe run the normal cron
						$url = admin_url('admin-ajax.php?action=houzez_property_feed_import_properties_batch&originally_ran_manually=' . $originally_ran_manually . '&import_ids=' . rawurlencode(implode("|", $import_ids) ));

						// Using wget to make a background HTTP request
					    $command = "wget -q -O /dev/null \"$url\" > /dev/null 2>&1 &";
					    exec($command);

						return;
					}
				}

				if ( $background_mode === false )
				{
					$import_ids = array_filter($import_ids, function($value) use ($import_id) {
		                return (int)$value !== (int)$import_id;
		            });
				}

	            // log instance start
	            $current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
				$current_date = $current_date->format("Y-m-d H:i:s");

	            $wpdb->insert( 
	                $wpdb->prefix . "houzez_property_feed_logs_instance", 
	                array(
	                	'import_id' => $import_id,
	                    'start_date' => $current_date,
	                    'status' => json_encode(array('status' => 'starting')),
	                    'status_date' => $current_date,
	                ),
	                array(
	                	'%d',
	                	'%s',
	                	'%s',
	                	'%s'
	                )
	            );
	            $instance_id = $wpdb->insert_id;

		    	$parsed_in_class = false;

		    	$import_object = hpf_get_import_object_from_format($format, $instance_id, $import_id);

		    	// parsed in class
		    	if ( 
		    		in_array(
		    			$format, 
		    			apply_filters( 'houzez_property_feed_formats_parsed_in_class', array( 'blm_local', 'openimmo_local', 'reaxml_local', 'rentman' ) ) 
		    		)
		    	)
		    	{
		    		$import_object->parse_and_import();

            		$parsed_in_class = true;
		    	}

		    	if ( !$parsed_in_class && isset($import_object) && !empty($import_object) )
		    	{
		    		$import_object->ping(array('status' => 'parsing'));
		    		
			    	$parsed = $import_object->parse();

	                if ( $parsed !== false )
	                {
	                	if ( $background_mode === false ) // this'll be handled separately if running in background mode
	                	{
	                		$import_object->total_properties = count($import_object->properties);
		                    $import_object->import();

		                    if ( apply_filters( 'houzez_property_feed_remove_old_properties', true, $import_id ) === true )
		                    {
			                    $import_object->remove_old_properties();
			                }
			            }
			            else
			            {
			            	$process_background_queue_afterwards = true;
			            }
	                }

	                unset($import_object);
	            }

	            if ( $background_mode === false ) // this'll be handled separately if running in background mode
	            {
			    	// log instance end
			    	$current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
					$current_date = $current_date->format("Y-m-d H:i:s");

			    	$wpdb->update( 
			            $wpdb->prefix . "houzez_property_feed_logs_instance", 
			            array( 
			                'end_date' => $current_date,
			                'status' => json_encode(array('status' => 'finished')),
		                	'status_date' => $current_date
			            ),
			            array( 'id' => $instance_id )
			        );

			        do_action( 'houzez_property_feed_cron_end', $instance_id, $import_id );
			        do_action( 'houzez_property_feed_import_cron_end', $instance_id, $import_id );
			    }
	    	}
	    } // end foreach import

	    if ( $process_background_queue_afterwards === true ) // at least one import had background mode enabled so let's fire it off
	    {
	    	$url = admin_url('admin-ajax.php?action=houzez_property_feed_import_properties_batch&originally_ran_manually=' . $originally_ran_manually . '&import_ids=' . rawurlencode(implode("|", $import_ids)));
	    	
	    	// Using wget to make a background HTTP request
		    $command = "wget -q -O /dev/null \"$url\" > /dev/null 2>&1 &";
		    exec($command);
		}
    }
}