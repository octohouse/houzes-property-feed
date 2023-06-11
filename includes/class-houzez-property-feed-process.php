<?php

class Houzez_Property_Feed_Process {

	/**
	 * @var int
	 */
	public $instance_id;

	/**
	 * @var int
	 */
	public $import_id;

	/**
	 * @var array
	 */
	public $properties = array();

	/**
	 * @var array
	 */
	public $errors;

	/**
	 * @var array
	 */
	public $mappings;

	/**
	 * @var array
	 */
	public $import_log;

    public function __construct() 
    {

    }

	public function import_start()
	{
		wp_suspend_cache_invalidation( true );

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		if ( !function_exists('media_handle_upload') ) 
		{
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');
		}
	}

	public function import_end()
	{
		wp_cache_flush();

		wp_suspend_cache_invalidation( false );

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );
	}

	public function do_geocoding_lookup( $post_id, $agent_ref, $address, $country = 'GB' )
	{
		

		return false;
	}

	public function do_remove_old_properties( $import_refs = array() )
	{
		global $wpdb, $post;

		if ( !empty($import_refs) )
		{
			$imported_ref_key = ( ( $this->import_id != '' ) ? '_imported_ref_' . $this->import_id : '_imported_ref' );

			// Get all properties that don't have an _imported_ref matching the properties in $this->properties

			$args = array(
				'post_type' => 'property',
				'post_status' => 'publish',
				'nopaging' => true,
				'fields' => 'ids',
				'meta_query' => array(
					array(
						'key'     => $imported_ref_key,
						'value'   => $import_refs,
						'compare' => 'NOT IN',
					),
				),
			);
			$property_query = new WP_Query( $args );

			if ( $property_query->have_posts() )
			{
				while ( $property_query->have_posts() )
				{
					$property_query->the_post();

					wp_update_post(
			            array(
			                'ID' => get_the_ID(), 
			                'post_status' => 'draft'
			            )
			        );

			        $this->log( 'Property removed', get_post_meta(get_the_ID(), $imported_ref_key, TRUE), get_the_ID() );

					do_action( "save_post_property", get_the_ID(), get_post(get_the_ID()), false );
					do_action( "save_post", get_the_ID(), get_post(get_the_ID()), false );

					do_action( "houzez_property_feed_property_removed", get_the_ID(), $this->import_id );
				}
			}
			wp_reset_postdata();
		}
	}

	public function delete_media( $post_id, $meta_key, $except_first = false )
	{
		$media_ids = get_post_meta( $post_id, $meta_key, TRUE );
		if ( !empty( $media_ids ) )
		{
			$i = 0;
			foreach ( $media_ids as $media_id )
			{
				if ( !$except_first || ( $except_first && $i > 0 ) )
				{
					if ( wp_delete_attachment( $media_id, TRUE ) !== FALSE )
					{
						// Deleted succesfully. Now remove from array
						if( ($key = array_search($media_id, $media_ids)) !== false)
						{
						    unset($media_ids[$key]);
						}
					}
					else
					{
						$this->log_error( 'Failed to delete ' . $meta_key . ' with attachment ID ' . $media_id, get_post_meta($post_id, $imported_ref_key, TRUE) );
					}
				}
				++$i;
			}
		}
		update_post_meta( $post_id, $meta_key, $media_ids );
	}

	public function add_missing_mapping( $mappings, $custom_field, $value, $import_id = '' )
	{
		

		return array();
	}

	public function log_error( $message, $agent_ref = '', $post_id = 0, $received_data = '' )
	{
		$current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
		$current_date = $current_date->format("Y-m-d H:i:s");

		$this->errors[] = $current_date . ' - ' . ( ( $agent_ref != '' ) ? 'AGENT_REF: ' . $agent_ref . ' - ' : '' ) . $message;

		if ( $this->instance_id != '' )
		{
			global $wpdb;

			$data = array(
                'instance_id' => $this->instance_id,
                'severity' => 1,
                'post_id' => $post_id,
                'crm_id' => $agent_ref,
                'entry' => $message,
                'log_date' => $current_date
            );

            if ( $received_data != '' )
            {
            	$data['received_data'] = $received_data;
            }
        
	        $wpdb->insert( 
	            $wpdb->prefix . "houzez_property_feed_logs_instance_log", 
	            $data
	        );
		}
	}

	public function get_import_log()
	{
		return $this->import_log;
	}

	public function get_errors()
	{
		return $this->errors;
	}

	public function log( $message, $agent_ref = '', $post_id = 0, $received_data = '' )
	{
		$current_date = new DateTimeImmutable( 'now', new DateTimeZone('UTC') );
		$current_date = $current_date->format("Y-m-d H:i:s");

		if ( $this->instance_id != '' )
		{
			global $wpdb;

			$data = array(
                'instance_id' => $this->instance_id,
                'severity' => 0,
                'post_id' => $post_id,
                'crm_id' => $agent_ref,
                'entry' => $message,
                'log_date' => $current_date
            );

            if ( $received_data != '' )
            {
            	$data['received_data'] = $received_data;
            }
        
	        $wpdb->insert( 
	            $wpdb->prefix . "houzez_property_feed_logs_instance_log", 
	            $data
	        );
		}

		$this->import_log[] = $current_date . ' - ' . ( ( $agent_ref != '' ) ? 'AGENT_REF: ' . $agent_ref . ' - ' : '' ) . $message;
	}

	public function open_ftp_connection( $host, $username, $password, $directory, $passive = '' )
	{
		// Connect to FTP directory and get file
		$ftp_connected = false;
		$ftp_conn = ftp_connect( $host );
		if ( $ftp_conn !== FALSE )
		{
			$ftp_login = ftp_login( $ftp_conn, $username, $password );
			if ( $ftp_login !== FALSE )
			{
				if ( $passive == 'yes' )
				{
					ftp_pasv( $ftp_conn, true );
				}

				if ( empty($directory) || ( !empty($directory) && ftp_chdir( $ftp_conn, $directory ) ) )
				{
					$ftp_connected = true;
				}
			}
		}
		return $ftp_connected ? $ftp_conn : null;
	}

	public function compare_meta_and_taxonomy_data( $post_id, $crm_id, $metadata_before = array(), $taxonomy_terms_before = array() )
	{
		$metadata_after = get_metadata('post', $post_id, '', true);

		foreach ( $metadata_after as $key => $value)
		{
			if ( in_array($key, array('fave_property_images', 'floor_plans', 'fave_attachments', 'fave_floor_plans_enable', '_property_import_data')) )
			{
				continue;
			}

			if ( !isset($metadata_before[$key]) )
			{
				$this->log( 'New meta data for ' . trim($key, '_') . ': ' . ( ( is_array($value) ) ? implode(", ", $value) : $value ), $crm_id, $post_id );
			}
			elseif ( $metadata_before[$key] != $metadata_after[$key] )
			{
				$this->log( 'Updated ' . trim($key, '_') . '. Before: ' . ( ( is_array($metadata_before[$key]) ) ? implode(", ", $metadata_before[$key]) : $metadata_before[$key] ) . ', After: ' . ( ( is_array($value) ) ? implode(", ", $value) : $value ), $crm_id, $post_id );
			}
		}

		$taxonomy_terms_after = array();
		$taxonomy_names = get_post_taxonomies( $post_id );
		foreach ( $taxonomy_names as $taxonomy_name )
		{
			$taxonomy_terms_after[$taxonomy_name] = wp_get_post_terms( $post_id, $taxonomy_name, array('fields' => 'ids') );
		}

		foreach ( $taxonomy_terms_after as $taxonomy_name => $ids)
		{
			if ( !isset($taxonomy_terms_before[$taxonomy_name]) )
			{
				$this->log( 'New taxonomy data for ' . $taxonomy_name . ': ' . ( ( is_array($ids) ) ? implode(", ", $ids) : $ids ), $crm_id, $post_id );
			}
			elseif ( $taxonomy_terms_before[$taxonomy_name] != $taxonomy_terms_after[$taxonomy_name] )
			{
				$this->log( 'Updated ' . $taxonomy_name . '. Before: ' . ( ( is_array($taxonomy_terms_before[$taxonomy_name]) ) ? implode(", ", $taxonomy_terms_before[$taxonomy_name]) : $taxonomy_terms_before[$taxonomy_name] ) . ', After: ' . ( ( is_array($ids) ) ? implode(", ", $ids) : $ids ), $crm_id, $post_id );
			}
		}
	}
}