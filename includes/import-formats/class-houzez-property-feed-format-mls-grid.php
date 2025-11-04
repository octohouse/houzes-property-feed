<?php
/**
 * Class for managing the import process of an MLS Grid JSON file
 *
 * @package WordPress
 */
if ( class_exists( 'Houzez_Property_Feed_Process' ) ) {

class Houzez_Property_Feed_Format_Mls_Grid extends Houzez_Property_Feed_Process {

	private $only_updated = false;

	public function __construct( $instance_id = '', $import_id = '' )
	{
		$this->instance_id = $instance_id;
		$this->import_id = $import_id;

		if ( $this->instance_id != '' && isset($_GET['custom_property_import_cron']) )
	    {
	    	$current_user = wp_get_current_user();

	    	$this->log("Executed manually by " . ( ( isset($current_user->display_name) ) ? $current_user->display_name : '' ), '', 0, '', false );
	    }

	    add_filter( 'houzez_property_feed_remove_old_properties', array( $this, 'dont_remove' ), 10, 2 );
	}

	public function dont_remove( $remove, $import_id )
	{
		if ($this->only_updated === true)
		{
			return false;
		}

		return $remove;
	}

	public function parse()
	{
		$this->properties = array(); // Reset properties in the event we're importing multiple files

		$this->log("Parsing properties", '', 0, '', false);

		$import_settings = houzez_property_feed_get_import_settings_from_id( $this->import_id );

		$per_page = 1000;
		
		$statuses = ( isset($import_settings['statuses']) && !empty($import_settings['statuses']) && is_array($import_settings['statuses']) ) ? $import_settings['statuses'] : array( 'Active', 'Coming Soon' );

		$additional_url = '%20and%20MlgCanView%20eq%20true';
		if ( ( isset($import_settings['only_updated']) && $import_settings['only_updated'] == 'yes' ) || !isset($import_settings['only_updated']) )
        {
        	// get last ran date
        	$last_ran_date = get_option( 'houzez_property_feed_last_ran_' . $this->import_id, '' );
        	if ( !empty($last_ran_date) )
        	{
        		$additional_url = '%20and%20ModificationTimestamp%20gt%20' . date("Y-m-d\TH:i:s\Z");
        		$this->only_updated = true;
        	}
        }

        $limit = apply_filters( "houzez_property_feed_property_limit", 25 );
		if ( $limit !== false )
        {
        
        }
        else
        {
        	$pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

        	// using pro, but check for limit setting
        	if ( 
        		$pro_active === true &&
        		isset($import_settings['limit']) && 
        		!empty((int)$import_settings['limit']) && 
        		is_numeric($import_settings['limit'])
        	)
        	{
        		$limit = (int)$import_settings['limit'];
        	}
        }

		foreach ( $statuses as $status )
		{
			$this->log("Obtaining properties with status " . $status);

			$more_properties = true;
			$current_page = 1;

			$url = 'https://api.mlsgrid.com/v2/Property?$filter=OriginatingSystemName%20eq%20%27' . $import_settings['originating_system_name'] . '%27%20and%20StandardStatus+eq+%27' . urlencode($status) . '%27' . $additional_url .'&$expand=Media,Rooms&$top=' . $per_page;

			while ( $more_properties )
			{
				$this->log("Obtaining properties on page " . $current_page . " from URL: " . $url);

				$response = wp_remote_get( 
					$url, 
					array( 
						'timeout' => 360, 
						'headers' => array(
							'Content-Type' => 'application/json',
							'Authorization' => 'Bearer ' . $import_settings['access_token'],
						) 
					) 
				);

				if ( is_wp_error( $response ) )
				{
					$this->log_error( 'Response: ' . $response->get_error_message() );

					return false;
				}

				if ( wp_remote_retrieve_response_code($response) !== 200 )
		        {
		            $this->log_error( wp_remote_retrieve_response_code($response) . ' response received when requesting properties. Error message: ' . wp_remote_retrieve_response_message($response) );
		            return false;
		        }

				if ( is_array( $response ) )
				{
					$contents = $response['body'];

					$json = json_decode( $contents, TRUE );

					if ( $json !== FALSE && is_array($json) )
					{
						if ( isset($json['error']) && !empty($json['error']) )
						{
							$this->log_error( 'Error received from MLS Grid API: ' . print_r($json['error'], true) );
							return false;
						}

						if ( isset($json['value']) && !empty($json['value']) )
						{
							foreach ($json['value'] as $property)
							{
								if ( !$this->only_updated )
								{
									if ( $limit !== FALSE && count($this->properties) >= $limit )
				                	{
				                		return true;
				                	}
								}

								if ( $this->only_updated || ( !$this->only_updated && isset($property['MlgCanView']) && $property['MlgCanView'] === true ) )
								{
									if ( isset($property['MlgCanUse']) && is_array($property['MlgCanUse']) && in_array('IDX', $property['MlgCanUse']) )
									{
										$this->properties[] = $property;
									}
								}
							}
						}

						if ( !isset($json['@odata.nextLink']) || ( isset($json['@odata.nextLink']) && empty($json['@odata.nextLink']) ) )
						{
							$more_properties = false;
						}
						else
						{
							$url = $json['@odata.nextLink'];
							usleep(1500000); // 1.5 seconds to stay within rate limiting
							++$current_page;
						}
					}
					else
					{
						// Failed to parse JSON
						$this->log_error( 'Failed to parse JSON file: ' . $contents );
						return false;
					}
				}
				else
				{
					$this->log_error( 'Failed to obtain JSON: ' . print_r($response, TRUE) );
					return false;
				}
			}
		}

		if ( empty($this->properties) )
		{
			if ( $this->only_updated === true )
			{
				$this->log_error( 'No properties modified since the last time an import ran.' );
			}
			else
			{
				$this->log_error( 'No properties found. We\'re not going to continue as this could likely be wrong and all properties will get removed if we continue.' );
			}
			return false;
		}

		return true;
	}

	public function import()
	{
		global $wpdb;

		$imported_ref_key = ( ( $this->import_id != '' ) ? '_imported_ref_' . $this->import_id : '_imported_ref' );
		$imported_ref_key = apply_filters( 'houzez_property_feed_property_imported_ref_key', $imported_ref_key, $this->import_id );

		$import_settings = houzez_property_feed_get_import_settings_from_id( $this->import_id );

		$pro_active = apply_filters( 'houzez_property_feed_pro_active', false );

		$this->import_start();

		do_action( "houzez_property_feed_pre_import_properties", $this->properties, $this->import_id );
        do_action( "houzez_property_feed_pre_import_properties_mls_grid", $this->properties, $this->import_id );

        $this->properties = apply_filters( "houzez_property_feed_properties_due_import", $this->properties, $this->import_id );
        $this->properties = apply_filters( "houzez_property_feed_properties_due_import_mls_grid", $this->properties, $this->import_id );

        $limit = apply_filters( "houzez_property_feed_property_limit", 25 );
        $additional_message = '';
        if ( $limit !== false )
        {
        	$this->properties = array_slice( $this->properties, 0, $limit );
        	$additional_message = '. <a href="https://houzezpropertyfeed.com/pricing" target="_blank">Upgrade to PRO</a> to import unlimited properties';
        }
        else
        {
        	// using pro, but check for limit setting
        	if ( isset($import_settings['limit']) && !empty((int)$import_settings['limit']) && is_numeric($import_settings['limit']) )
        	{
        		$this->properties = array_slice( $this->properties, 0, (int)$import_settings['limit'] );
        		$additional_message = '. Limited to ' . number_format((int)$import_settings['limit']) . ' properties due to advanced setting in <a href="' . admin_url('admin.php?page=houzez-property-feed-import&action=editimport&import_id=' . $this->import_id) . '">import settings</a>.';
        	}
        }

		$this->log( 'Beginning to loop through ' . count($this->properties) . ' properties' . $additional_message );

		$start_at_property = get_option( 'houzez_property_feed_property_' . $this->import_id );

		$property_row = 1;
		// $this->properties could contain all properties, or only updated one. Check $this->only_updated
		foreach ( $this->properties as $property )
		{
			if ( $this->only_updated )
			{
				update_option( 'houzez_property_feed_property_' . $this->import_id, '', false );

				// delete if applicable
				if ( isset($property['MlgCanView']) && $property['MlgCanView'] === false )
				{
					$this->remove_property( $property['ListingKey'], '' );
				}

				if ( 
	        		$pro_active === true &&
	        		isset($import_settings['limit']) && 
	        		!empty((int)$import_settings['limit']) && 
	        		is_numeric($import_settings['limit'])
	        	)
	        	{
	        		// Check how many published properties there are belonging to this import and, if more than limit, break out
	        		$property_count = $this->get_number_published_properties();

					if ( $property_count >= (int)$import_settings['limit'] )
					{
						$this->log( 'Stopping as we\'ve hit the advanced limit setting in <a href="' . admin_url('admin.php?page=houzez-property-feed-import&action=editimport&import_id=' . $this->import_id) . '">import settings</a>.' );
						break;
					}
	        	}
			}
			else
			{
				if ( !empty($start_at_property) )
				{
					// we need to start on a certain property
					if ( $property['ListingKey'] == $start_at_property )
					{
						// we found the property. We'll continue for this property onwards
						$this->log( 'Previous import failed to complete. Continuing from property ' . $property_row . ' with ID ' . $property['ListingKey'] );
						$start_at_property = false;
					}
					else
					{
						++$property_row;
						continue;
					}
				}
			}

			update_option( 'houzez_property_feed_property_' . $this->import_id, $property['ListingKey'], false );
			
			$this->log( 'Importing property ' . $property_row . ' with reference ' . $property['ListingKey'], $property['ListingKey'], 0, '', false );

			$this->ping(array('status' => 'importing', 'property' => $property_row, 'total' => count($this->properties)));

			$inserted_updated = false;

			$args = array(
	            'post_type' => 'property',
	            'posts_per_page' => 1,
	            'post_status' => 'any',
	            'meta_query' => array(
	            	array(
		            	'key' => $imported_ref_key,
		            	'value' => $property['ListingKey']
		            )
	            )
	        );
	        $property_query = new WP_Query($args);

	        $display_address = array();
	        
	        $address_parts = array();
            if ( isset($property['StreetDirPrefix']) ) { $address_parts[] = $property['StreetDirPrefix']; }
            if ( isset($property['StreetName']) ) { $address_parts[] = $property['StreetName']; }
            if ( isset($property['StreetSuffix']) ) { $address_parts[] = $property['StreetSuffix']; }
            if ( isset($property['StreetDirSuffix']) ) { $address_parts[] = $property['StreetDirSuffix']; }
            $address_parts = array_unique($address_parts);
            $address_parts = array_filter($address_parts);

    		if ( !empty($address_parts) )
    		{
    			$display_address[] = implode(' ', $address_parts);
    		}
    		if ( isset($property['City']) && !empty($property['City']) )
    		{
    			$display_address[] = $property['City'];
    		}
    		if ( isset($property['StateOrProvince']) && !empty($property['StateOrProvince']) )
    		{
    			$display_address[] = $property['StateOrProvince'];
    		}
    		if ( isset($property['PostalCode']) && !empty($property['PostalCode']) )
    		{
    			$display_address[] = $property['PostalCode'];
    		}

	        $display_address = implode(", ", $display_address);
	        
	        if ($property_query->have_posts())
	        {
	        	// We've imported this property before
	            while ($property_query->have_posts())
	            {
	                $property_query->the_post();

	                $post_id = get_the_ID();

	                $this->log( 'This property has been imported before. Updating it', $property['ListingKey'], $post_id );

	                $my_post = array(
				    	'ID'          	 => $post_id,
				    	'post_title'     => wp_strip_all_tags( $display_address ),
				    	'post_excerpt'   => $property['PublicRemarks'],
				    	'post_content' 	 => $property['PublicRemarks'],
				    	'post_status'    => 'publish',
				  	);

				  	$my_post = apply_filters( 'houzez_property_feed_update_postarr', $my_post, $property, $this->import_id, $post_id );

				 	// Update the post into the database
				    $post_id = wp_update_post( $my_post, true );

				    if ( is_wp_error( $post_id ) ) 
					{
						$this->log_error( 'Failed to update post. The error was as follows: ' . $post_id->get_error_message(), $property['ListingKey'] );
					}
					else
					{
						$inserted_updated = 'updated';
					}
	            }
	        }
	        else
	        {
	        	$this->log( 'This property hasn\'t been imported before. Inserting it', $property['ListingKey'] );

	        	// We've not imported this property before
				$postdata = array(
					'post_excerpt'   => $property['PublicRemarks'],
					'post_content' 	 => $property['PublicRemarks'],
					'post_title'     => wp_strip_all_tags( $display_address ),
					'post_status'    => 'publish',
					'post_type'      => 'property',
					'comment_status' => 'closed',
				);

				$postdata = apply_filters( 'houzez_property_feed_insert_postarr', $postdata, $property, $this->import_id );

				$post_id = wp_insert_post( $postdata, true );

				if ( is_wp_error( $post_id ) ) 
				{
					$this->log_error( 'Failed to insert post. The error was as follows: ' . $post_id->get_error_message(), $property['ListingKey'] );
				}
				else
				{
					$inserted_updated = 'inserted';
				}
			}
			$property_query->reset_postdata();

			if ( $inserted_updated !== false )
			{
				// Inserted property ok. Continue

				if ( $inserted_updated == 'updated' )
				{
					// Get all meta data so we can compare before and after to see what's changed
					$metadata_before = get_metadata('post', $post_id, '', true);

					// Get all taxonomy/term data
					$taxonomy_terms_before = array();
					$taxonomy_names = get_post_taxonomies( $post_id );
					foreach ( $taxonomy_names as $taxonomy_name )
					{
						$taxonomy_terms_before[$taxonomy_name] = wp_get_post_terms( $post_id, $taxonomy_name, array('fields' => 'ids') );
					}
				}

				$this->log( 'Successfully ' . $inserted_updated . ' post', $property['ListingKey'], $post_id );

				update_post_meta( $post_id, $imported_ref_key, $property['ListingKey'] );

				update_post_meta( $post_id, '_property_import_data', json_encode($property, JSON_PRETTY_PRINT) );

				$department = ( isset($property['PropertyType']) && in_array($property['PropertyType'], array('Commercial Lease', 'Residential Lease')) ) ? 'residential-lettings' : 'residential-sales';

				$poa = false;

				if ( $poa === true ) 
                {
                    update_post_meta( $post_id, 'fave_property_price', 'POA');
                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
                }
                else
                {
                    update_post_meta( $post_id, 'fave_property_price_prefix', '' );
                    update_post_meta( $post_id, 'fave_property_price', $property['ListPrice'] );
                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
                }

                update_post_meta( $post_id, 'fave_property_bedrooms', ( ( isset($property['BedroomsTotal']) ) ? $property['BedroomsTotal'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_bathrooms', ( ( isset($property['BathroomsTotalInteger']) ) ? $property['BathroomsTotalInteger'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_rooms', '' );
	            update_post_meta( $post_id, 'fave_property_garage', '' ); // need to look at parking
	            update_post_meta( $post_id, 'fave_property_id', $property['ListingId'] );

	            update_post_meta( $post_id, 'fave_property_size', ( ( isset($property['LivingArea']) && !empty($property['LivingArea']) ) ? $property['LivingArea'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_size_prefix', ( ( isset($property['LivingArea']) && !empty($property['LivingArea']) ) ? 'Sq Ft' : '' ) );
	            update_post_meta( $post_id, 'fave_property_land', ( ( isset($property['LotSizeArea']) && !empty($property['LotSizeArea']) ) ? $property['LotSizeArea'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_land_postfix', ( ( isset($property['LotSizeArea']) && !empty($property['LotSizeArea']) && isset($property['LotSizeUnits']) && !empty($property['LotSizeUnits']) ) ? $property['LotSizeUnits'] : '' ) );

	            update_post_meta( $post_id, 'fave_property_map', '1' );
	            update_post_meta( $post_id, 'fave_property_map_address', $display_address );
	            $lat = '';
	            $lng = '';
	            if ( isset($property['Latitude']) && !empty($property['Latitude']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_lat', $property['Latitude'] );
	                $lat = $property['Latitude'];
	            }
	            if ( isset($property['Longitude']) && !empty($property['Longitude']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_long', $property['Longitude'] );
	                $lng = $property['Longitude'];
	            }
	            update_post_meta( $post_id, 'fave_property_location', $lat . "," . $lng . ",14" );
	            update_post_meta( $post_id, 'fave_property_country', 'US' );

	            $address_parts = array();
	            if ( isset($property['StreetDirPrefix']) ) { $address_parts[] = $property['StreetDirPrefix']; }
	            if ( isset($property['StreetName']) ) { $address_parts[] = $property['StreetName']; }
	            if ( isset($property['StreetSuffix']) ) { $address_parts[] = $property['StreetSuffix']; }
	            if ( isset($property['StreetDirSuffix']) ) { $address_parts[] = $property['StreetDirSuffix']; }
	            $address_parts = array_unique($address_parts);
	            $address_parts = array_filter($address_parts);

	            update_post_meta( $post_id, 'fave_property_address', implode(", ", $address_parts) );
	            update_post_meta( $post_id, 'fave_property_zip', ( ( isset($property['PostalCode']) ) ? $property['PostalCode'] : '' ) );

	            add_post_meta( $post_id, 'fave_featured', '0', TRUE );
	            update_post_meta( $post_id, 'fave_agent_display_option', ( isset($import_settings['agent_display_option']) ? $import_settings['agent_display_option'] : 'none' ) );

	            if ( 
	            	isset($import_settings['agent_display_option']) && 
	            	isset($import_settings['agent_display_option_rules']) && 
	            	is_array($import_settings['agent_display_option_rules']) && 
	            	!empty($import_settings['agent_display_option_rules']) 
	            )
	            {
		            switch ( $import_settings['agent_display_option'] )
		            {
		            	case "author_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				default:
		            				{
		            					$value_in_feed_to_check = $property[$rule['field']];
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				// set post author
		            				$my_post = array(
								    	'ID'          	 => $post_id,
								    	'post_author'    => $rule['result'],
								  	);

								 	// Update the post into the database
								    wp_update_post( $my_post, true );

		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            	case "agent_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				default:
		            				{
		            					$value_in_feed_to_check = $property[$rule['field']];
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				update_post_meta( $post_id, 'fave_agents', $rule['result'] );
		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            	case "agency_info":
		            	{
		            		foreach ( $import_settings['agent_display_option_rules'] as $rule )
		            		{
		            			$value_in_feed_to_check = '';
		            			switch ( $rule['field'] )
		            			{
		            				default:
		            				{
		            					$value_in_feed_to_check = $property[$rule['field']];
		            				}
		            			}

		            			if ( $value_in_feed_to_check == $rule['equal'] || $rule['equal'] == '*' )
		            			{
		            				update_post_meta( $post_id, 'fave_property_agency', $rule['result'] );
		            				break; // Rule matched. Lets not do anymore
		            			}
		            		}
		            		break;
		            	}
		            }
	        	}
	        	
				$mappings = ( isset($import_settings['mappings']) && is_array($import_settings['mappings']) && !empty($import_settings['mappings']) ) ? $import_settings['mappings'] : array();

				// status taxonomies
				$mapping_name = 'lettings_status';
				if ( $department == 'residential-sales' )
				{
					$mapping_name = 'sales_status';
				}

				$taxonomy_mappings = ( isset($mappings[$mapping_name]) && is_array($mappings[$mapping_name]) && !empty($mappings[$mapping_name]) ) ? $mappings[$mapping_name] : array();

				if ( isset($property['StandardStatus']) && !empty($property['StandardStatus']) )
				{
					if ( isset($taxonomy_mappings[$property['StandardStatus']]) && !empty($taxonomy_mappings[$property['StandardStatus']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['StandardStatus']], "property_status" );
					}
					else
					{
						$this->log( 'Received status of ' . $property['StandardStatus'] . ' that isn\'t mapped in the import settings', $property['ListingKey'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, $mapping_name, $property['StandardStatus'], $this->import_id );
					}
				}

				// property type taxonomies
				$taxonomy_mappings = ( isset($mappings['property_type']) && is_array($mappings['property_type']) && !empty($mappings['property_type']) ) ? $mappings['property_type'] : array();

				if ( isset($property['PropertySubType']) && !empty($property['PropertySubType']) )
				{
					if ( isset($taxonomy_mappings[$property['PropertySubType']]) && !empty($taxonomy_mappings[$property['PropertySubType']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['PropertySubType']], "property_type" );
					}
					else
					{
						$this->log( 'Received property type of ' . $property['PropertySubType'] . ' that isn\'t mapped in the import settings', $property['ListingKey'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, 'property_type', $property['PropertySubType'], $this->import_id );
					}
				}

				// Location taxonomies
				$create_location_taxonomy_terms = isset( $import_settings['create_location_taxonomy_terms'] ) ? $import_settings['create_location_taxonomy_terms'] : false;

				$houzez_tax_settings = get_option('houzez_tax_settings', array() );
				
				$location_taxonomies = array();
				if ( !isset($houzez_tax_settings['property_city']) || ( isset($houzez_tax_settings['property_city']) && $houzez_tax_settings['property_city'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_city';
				}
				if ( !isset($houzez_tax_settings['property_area']) || ( isset($houzez_tax_settings['property_area']) && $houzez_tax_settings['property_area'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_area';
				}
				if ( !isset($houzez_tax_settings['property_state']) || ( isset($houzez_tax_settings['property_state']) && $houzez_tax_settings['property_state'] != 'disabled' ) )
				{
					$location_taxonomies[] = 'property_state';
				}

				foreach ( $location_taxonomies as $location_taxonomy )
				{
					$address_field_to_use = isset( $import_settings[$location_taxonomy . '_address_field'] ) ? $import_settings[$location_taxonomy . '_address_field'] : '';
					if ( !empty($address_field_to_use) )
					{
						$location_term_ids = array();
						if ( isset($property[$address_field_to_use]) && !empty($property[$address_field_to_use]) )
		            	{
		            		$term = term_exists( trim($property[$address_field_to_use]), $location_taxonomy);
							if ( $term !== 0 && $term !== null && isset($term['term_id']) )
							{
								$location_term_ids[] = (int)$term['term_id'];
							}
							else
							{
								if ( $create_location_taxonomy_terms === true )
								{
									$term = wp_insert_term( trim($property[$address_field_to_use]), $location_taxonomy );
									if ( is_array($term) && isset($term['term_id']) )
									{
										$location_term_ids[] = (int)$term['term_id'];
									}
								}
							}
		            	}
		            	if ( !empty($location_term_ids) )
						{
							wp_set_object_terms( $post_id, $location_term_ids, $location_taxonomy );
						}
						else
						{
							wp_delete_object_term_relationships( $post_id, $location_taxonomy );
						}
					}
				}

				// Images
				if ( 
					apply_filters('houzez_property_feed_images_stored_as_urls', false, $post_id, $property, $this->import_id) === true ||
					apply_filters('houzez_property_feed_images_stored_as_urls_mls_grid', false, $post_id, $property, $this->import_id) === true
				)
				{
					$this->log( 'MLS Grid don\'t allow storing of images as URLs', $property['ListingKey'], $post_id );
				}
				else
				{
					$media_ids = array();
					$new = 0;
					$existing = 0;
					$deleted = 0;
					$image_i = 0;
					$queued = 0;
					$previous_media_ids = get_post_meta( $post_id, 'fave_property_images' );

					$start_at_image_i = false;
					$previous_import_media_ids = get_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id );

					if ( !empty($previous_import_media_ids) )
					{
						// an import stopped previously whilst doing images. Check if it was this post
						$explode_previous_import_media_ids = explode("|", $previous_import_media_ids);
						if ( $explode_previous_import_media_ids[0] == $post_id )
						{
							// yes it was this property. now loop through the media already imported to ensure it's not imported again
							if ( isset($explode_previous_import_media_ids[1]) && !empty($explode_previous_import_media_ids[1]) )
							{
								$media_ids = explode(",", $explode_previous_import_media_ids[1]);
								$start_at_image_i = count($media_ids);

								$this->log( 'Imported ' . count($media_ids) . ' images before failing in the previous import. Continuing from here', $property['ListingKey'], $post_id );
							}
						}
					}

					if ( isset($property['Media']) && is_array($property['Media']) && !empty($property['Media']) )
					{
						foreach ( $property['Media'] as $image )
						{
							if ( $pro_active === true )
							{
								if ( 
									isset($import_settings['limit_images']) && 
									!empty((int)$import_settings['limit_images']) && 
									is_numeric($import_settings['limit_images']) &&
									(count($media_ids) + $queued) >= $import_settings['limit_images']
								)
					        	{
					        		break;
					        	}
					        }
					        
							if ( 
								isset($image['MediaURL']) && $image['MediaURL'] != ''
								&&
								(
									substr( strtolower($image['MediaURL']), 0, 2 ) == '//' || 
									substr( strtolower($image['MediaURL']), 0, 4 ) == 'http'
								)
								&&
								isset($image['MediaCategory']) && in_array(strtolower($image['MediaCategory']), array('photo', 'image'))
							)
							{
								if ( $start_at_image_i !== false )
								{
									// we need to start at a specific image
									if ( $image_i < $start_at_image_i )
									{
										++$existing;
										++$image_i;
										continue;
									}
								}

								// This is a URL
								$url = $image['MediaURL'];
								$description = '';
								$modified = $image['MediaModificationTimestamp'];
								if ( !empty($modified) )
								{
									$dateTime = new DateTime($modified);
									$modified = $dateTime->format('Y-m-d H:i:s');
								}
							    
								$filename = basename( $url );

								// Check, based on the URL, whether we have previously imported this media
								$imported_previously = false;
								$imported_previously_id = '';
								if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
								{
									foreach ( $previous_media_ids as $previous_media_id )
									{
										if ( 
											get_post_meta( $previous_media_id, '_imported_url', TRUE ) == $url
											&&
											(
												get_post_meta( $previous_media_id, '_modified', TRUE ) == '' 
												||
												(
													get_post_meta( $previous_media_id, '_modified', TRUE ) != '' &&
													get_post_meta( $previous_media_id, '_modified', TRUE ) == $modified
												)
											)
										)
										{
											$imported_previously = true;
											$imported_previously_id = $previous_media_id;
											break;
										}
									}
								}

								if ($imported_previously)
								{
									$media_ids[] = $imported_previously_id;

									if ( $description != '' )
									{
										$my_post = array(
									    	'ID'          	 => $imported_previously_id,
									    	'post_title'     => $description,
									    );

									 	// Update the post into the database
									    wp_update_post( $my_post );
									}

									if ( $image_i == 0 ) set_post_thumbnail( $post_id, $imported_previously_id );

									++$existing;

									++$image_i;

									update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, $post_id . '|' . implode(",", $media_ids), false );
								}
								else
								{
									if ( apply_filters( 'houzez_property_feed_import_media', true, $this->import_id, $post_id, $property['ListingKey'], $url, $url, $description, 'image', $image_i, $modified ) === true )
									{
										$this->ping();

										$tmp = download_url( $url );

									    $file_array = array(
									        'name' => $filename,
									        'tmp_name' => $tmp
									    );

									    // Check for download errors
									    if ( is_wp_error( $tmp ) ) 
									    {
									        $this->log_error( 'An error occurred whilst importing ' . $url . '?width=' . $image_width . '. The error was as follows: ' . $tmp->get_error_message(), $property['ListingKey'], $post_id );
									    }
									    else
									    {
										    $id = media_handle_sideload( $file_array, $post_id, $description );

										    // Check for handle sideload errors.
										    if ( is_wp_error( $id ) ) 
										    {
										        @unlink( $file_array['tmp_name'] );
										        
										        $this->log_error( 'ERROR: An error occurred whilst importing ' . $url . '?width=' . $image_width . '. The error was as follows: ' . $id->get_error_message(), $property['ListingKey'], $post_id );
										    }
										    else
										    {
										    	$media_ids[] = $id;

										    	update_post_meta( $id, '_imported_url', $url);
										    	update_post_meta( $id, '_modified', $modified);

										    	if ( $image_i == 0 ) set_post_thumbnail( $post_id, $id );

										    	++$new;

										    	++$image_i;

										    	update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, $post_id . '|' . implode(",", $media_ids), false );
										    }
										}
									}
									else
									{
										++$queued;
										++$image_i;
									}
								}
							}
						}
					}
					if ( $media_ids != $previous_media_ids )
					{
						delete_post_meta( $post_id, 'fave_property_images' );
						foreach ( $media_ids as $media_id )
						{
							add_post_meta( $post_id, 'fave_property_images', $media_id );
						}
					}

					update_post_meta( $post_id, 'images_stored_as_urls', false );

					// Loop through $previous_media_ids, check each one exists in $media_ids, and if it doesn't then delete
					if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
					{
						foreach ( $previous_media_ids as $previous_media_id )
						{
							if ( !in_array($previous_media_id, $media_ids) )
							{
								if ( wp_delete_attachment( $previous_media_id, TRUE ) !== FALSE )
								{
									++$deleted;
								}
							}
						}
					}

					$this->log( 'Imported ' . count($media_ids) . ' photos (' . $new . ' new, ' . $existing . ' existing, ' . $deleted . ' deleted)', $property['ListingKey'], $post_id );
					if ( $queued > 0 ) 
					{
						$this->log( $queued . ' photos added to download queue', $property['ListingKey'], $post_id );
					}
					
					update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, '', false );
				}

				update_post_meta( $post_id, 'fave_video_url', '' );
				update_post_meta( $post_id, 'fave_virtual_tour', '' );

				if ( isset($property['VirtualTourURLUnbranded']) && !empty($property['VirtualTourURLUnbranded']) )
				{
					if (
						substr( strtolower($property['VirtualTourURLUnbranded']), 0, 2 ) == '//' || 
						substr( strtolower($property['VirtualTourURLUnbranded']), 0, 4 ) == 'http'
					)
					{
						// This is a URL
						$url = $property['VirtualTourURLUnbranded'];

						if ( strpos(strtolower($url), 'youtu') !== false || strpos(strtolower($url), 'vimeo') !== false )
						{
							update_post_meta( $post_id, 'fave_video_url', $url );
						}
						else
						{
							$iframe = '<iframe src="' . $url . '" style="border:0; height:360px; width:640px; max-width:100%" allowFullScreen="true"></iframe>';
							update_post_meta( $post_id, 'fave_virtual_tour', $iframe );
						}
					}
				}

				do_action( "houzez_property_feed_property_imported", $post_id, $property, $this->import_id, $this->instance_id );
				do_action( "houzez_property_feed_property_imported_mls_grid", $post_id, $property, $this->import_id, $this->instance_id );

				$post = get_post( $post_id );
				do_action( "save_post_property", $post_id, $post, false );
				do_action( "save_post", $post_id, $post, false );

				if ( $inserted_updated == 'updated' )
				{
					$this->compare_meta_and_taxonomy_data( $post_id, $property['ListingKey'], $metadata_before, $taxonomy_terms_before );
				}
			}

			++$property_row;

		} // end foreach property

		update_option( 'houzez_property_feed_last_ran_' . $this->import_id, time() );

		do_action( "houzez_property_feed_post_import_properties_mls_grid", $this->import_id );

		$this->import_end();
	}

	public function remove_old_properties()
	{
		global $wpdb, $post;

		if ( !empty($this->properties) )
		{
			$import_refs = array();
			foreach ($this->properties as $property)
			{
				$import_refs[] = $property['ListingKey'];
			}

			$this->do_remove_old_properties( $import_refs );

			unset($import_refs);
		}
	}
}

}