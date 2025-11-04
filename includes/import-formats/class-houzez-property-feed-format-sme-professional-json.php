<?php
/**
 * Class for managing the import process of a SME Professional JSON file
 *
 * @package WordPress
 */
if ( class_exists( 'Houzez_Property_Feed_Process' ) ) {

class Houzez_Property_Feed_Format_SME_Professional_JSON extends Houzez_Property_Feed_Process {

	public function __construct( $instance_id = '', $import_id = '' )
	{
		$this->instance_id = $instance_id;
		$this->import_id = $import_id;

		if ( $this->instance_id != '' && isset($_GET['custom_property_import_cron']) )
	    {
	    	$current_user = wp_get_current_user();

	    	$this->log("Executed manually by " . ( ( isset($current_user->display_name) ) ? $current_user->display_name : '' ), '', 0, '', false );
	    }
	}

	public function parse()
	{
		$this->properties = array(); // Reset properties in the event we're importing multiple files

		$this->log("Parsing properties", '', 0, '', false);

		$import_settings = houzez_property_feed_get_import_settings_from_id( $this->import_id );

		$departments = array( 'residential-sales', 'residential-lettings' );
		$departments = apply_filters( 'houzez_property_feed_sme_professional_json_departments', $departments, $import_id );

		$branch_ids = array( '' );
		$branch_ids = apply_filters( 'houzez_property_feed_sme_professional_json_branch_ids', $branch_ids, $import_id );

		foreach ( $branch_ids as $branch_id )
		{
			// Lettings properties
			if ( in_array('residential-lettings', $departments) )
			{
				$response = wp_remote_get(
					'https://home.smelogin.co.uk/CustomerData/' . $import_settings['company_id'] . '/GeneratedDocuments/Marketing/waas/all_marketed_properties' . $branch_id . '.json',
					array( 'timeout' => 120 )
				);

				if ( !is_wp_error($response) && is_array( $response ) ) 
				{
					if ( isset($response['response']['code']) && $response['response']['code'] == 404 )
					{
						// do nothing. This scenario is fine
					}
					else
					{
						$contents = $response['body'];

						$properties_json = json_decode( $contents, TRUE );

						if ($properties_json !== FALSE && !is_null($properties_json))
						{
							$this->log("Found " . count($properties_json) . " lettings properties in JSON ready for parsing");

							foreach ($properties_json as $id => $property)
							{
								if ( $test === true )
								{
									$this->properties[] = $property;
								}
								else
								{
									$response = wp_remote_get(
										$property['file'],
										array( 'timeout' => 120 )
									);

									if ( !is_wp_error($response) && is_array( $response ) ) 
									{
										$property_contents = $response['body'];

										$property_json = json_decode( $property_contents, TRUE );

										if ($property_json !== FALSE && !is_null($property_json))
										{
											$property_json['property']['department'] = 'residential-lettings';
											$this->properties[] = $property_json['property'];
								        }
								        else
								        {
								        	// Failed to parse JSON
								        	$this->log( 'Failed to parse letting property JSON file: ' . print_r($property_contents, true) );
								        	return false;
								        }
									}
									else
									{

										$this->log_error( 'Failed to obtain letting property response: ' . print_r($response, TRUE) );
										return false;
									}
								}
							}
				        }
				        else
				        {
				        	// Failed to parse JSON
				        	$this->log_error( 'Failed to parse lettings JSON file: ' . print_r($contents, true) );
				        	return false;
				        }
			        }
				}
				else
				{

					$this->log_error( 'Failed to obtain lettings response: ' . print_r($response, TRUE) );
					return false;
				}
			}

			// Sales properties
			if ( in_array('residential-sales', $departments) )
			{
				$response = wp_remote_get(
					'https://home.smelogin.co.uk/CustomerData/' . $import_settings['company_id'] . '/GeneratedDocuments/Marketing/waas_s/all_marketed_properties' . $branch_id . '.json',
					array( 'timeout' => 120 )
				);

				if ( !is_wp_error($response) && is_array( $response ) ) 
				{
					if ( isset($response['response']['code']) && $response['response']['code'] == 404 )
					{
						// do nothing. This scenario is fine
					}
					else
					{
						$contents = $response['body'];

						$properties_json = json_decode( $contents, TRUE );

						if ($properties_json !== FALSE && !is_null($properties_json))
						{
							$this->log("Found " . count($properties_json) . " sales properties in JSON ready for parsing");

							foreach ($properties_json as $property)
							{
								if ( $test === true )
								{
									$this->properties[] = $property;
								}
								else
								{
									$response = wp_remote_get(
										$property['file'],
										array( 'timeout' => 120 )
									);

									if ( !is_wp_error($response) && is_array( $response ) ) 
									{
										$property_contents = $response['body'];

										$property_json = json_decode( $property_contents, TRUE );

										if ($property_json !== FALSE && !is_null($property_json))
										{
											$property_json['property']['department'] = 'residential-sales';
											$this->properties[] = $property_json['property'];
								        }
								        else
								        {
								        	// Failed to parse JSON
								        	$this->log_error( 'Failed to parse sales property JSON file: ' . print_($property_contents, true) );
								        	return false;
								        }
									}
									else
									{

										$this->log_error( 'Failed to obtain sales property response: ' . print_r($response, TRUE) );
										return false;
									}
								}
							}
				        }
				        else
				        {
				        	// Failed to parse JSON
				        	$this->log_error( 'Failed to parse sales JSON file: ' . print_r($contents, true) );
				        	return false;
				        }
				    }
				}
				else
				{

					$this->log_error( 'Failed to obtain sales response: ' . print_r($response, TRUE) );
					return false;
				}
			}
		}

		if ( empty($this->properties) )
		{
			$this->log_error( 'No properties found. We\'re not going to continue as this could likely be wrong and all properties will get removed if we continue.' );

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
        do_action( "houzez_property_feed_pre_import_properties_sme_professional_json", $this->properties, $this->import_id );

        $this->properties = apply_filters( "houzez_property_feed_properties_due_import", $this->properties, $this->import_id );
        $this->properties = apply_filters( "houzez_property_feed_properties_due_import_sme_professional_json", $this->properties, $this->import_id );

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
		foreach ( $this->properties as $property )
		{
			if ( !empty($start_at_property) )
			{
				// we need to start on a certain property
				if ( $property['id'] == $start_at_property )
				{
					// we found the property. We'll continue for this property onwards
					$this->log( 'Previous import failed to complete. Continuing from property ' . $property_row . ' with ID ' . $property['id'] );
					$start_at_property = false;
				}
				else
				{
					++$property_row;
					continue;
				}
			}

			update_option( 'houzez_property_feed_property_' . $this->import_id, $property['id'], false );
			
			$this->log( 'Importing property ' . $property_row . ' with reference ' . $property['id'], $property['id'], 0, '', false );

			$this->ping(array('status' => 'importing', 'property' => $property_row, 'total' => count($this->properties)));

			$inserted_updated = false;

			$args = array(
	            'post_type' => 'property',
	            'posts_per_page' => 1,
	            'post_status' => 'any',
	            'meta_query' => array(
	            	array(
		            	'key' => $imported_ref_key,
		            	'value' => $property['id']
		            )
	            )
	        );
	        $property_query = new WP_Query($args);

	        $display_address = '';
	        if ( isset($property['address']['display_address']) && trim($property['address']['display_address']) != '' )
	        {
	        	$display_address = trim($property['address']['display_address']);
	        }

			$post_content = $property['details']['description'];
	        
	        if ($property_query->have_posts())
	        {
	        	// We've imported this property before
	            while ($property_query->have_posts())
	            {
	                $property_query->the_post();

	                $post_id = get_the_ID();

	                $this->log( 'This property has been imported before. Updating it', $property['id'], $post_id );

	                $my_post = array(
				    	'ID'          	 => $post_id,
				    	'post_title'     => wp_strip_all_tags( $display_address ),
				    	'post_excerpt'   => $property['details']['summary'],
				    	'post_content' 	 => $post_content,
				    	'post_status'    => 'publish',
				  	);

				  	$my_post = apply_filters( 'houzez_property_feed_update_postarr', $my_post, $property, $this->import_id, $post_id );

				 	// Update the post into the database
				    $post_id = wp_update_post( $my_post, true );

				    if ( is_wp_error( $post_id ) ) 
					{
						$this->log_error( 'Failed to update post. The error was as follows: ' . $post_id->get_error_message(), $property['id'] );
					}
					else
					{
						$inserted_updated = 'updated';
					}
	            }
	        }
	        else
	        {
	        	$this->log( 'This property hasn\'t been imported before. Inserting it', $property['id'] );

	        	// We've not imported this property before
				$postdata = array(
					'post_excerpt'   => $property['details']['summary'],
					'post_content' 	 => $post_content,
					'post_title'     => wp_strip_all_tags( $display_address ),
					'post_status'    => 'publish',
					'post_type'      => 'property',
					'comment_status' => 'closed',
				);

				$postdata = apply_filters( 'houzez_property_feed_insert_postarr', $postdata, $property, $this->import_id );

				$post_id = wp_insert_post( $postdata, true );

				if ( is_wp_error( $post_id ) ) 
				{
					$this->log_error( 'Failed to insert post. The error was as follows: ' . $post_id->get_error_message(), $property['id'] );
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

				$this->log( 'Successfully ' . $inserted_updated . ' post', $property['id'], $post_id );

				update_post_meta( $post_id, $imported_ref_key, $property['id'] );

				update_post_meta( $post_id, '_property_import_data', json_encode($property, JSON_PRETTY_PRINT) );

				$department = $property['department'];

				$poa = false;
				if ( 
					isset($property['price_information']['price_qualifier']) && $property['price_information']['price_qualifier'] == '1'
				)
				{
					$poa = true;
				}

				if ( $poa === true ) 
                {
                    update_post_meta( $post_id, 'fave_property_price', 'POA');
                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
                }
                else
                {
                	if ( $department == 'residential-sales' )
                	{
                		$price = '';
                		if ( isset($property['price_information']['price']) && !empty($property['price_information']['price']) )
                		{
	                		$price = round(preg_replace("/[^0-9.]/", '', $property['price_information']['price']));
	                	}
	                    update_post_meta( $post_id, 'fave_property_price_prefix', '' );
	                    update_post_meta( $post_id, 'fave_property_price', $price );
	                    update_post_meta( $post_id, 'fave_property_price_postfix', '' );
	                }
	                elseif ( $department == 'residential-lettings' )
	                {
	                	$price = '';
	                	if ( isset($property['price_information']['price']) && !empty($property['price_information']['price']) )
                		{
	                		$price = round(preg_replace("/[^0-9.]/", '', $property['price_information']['price']));
	                	}

	                	$rent_frequency = 'pcm';
						switch ((string)$property->rentFrequency)
						{
							case 52: { $rent_frequency = 'pw'; break; }
							case 4: { $rent_frequency = 'pq'; break; }
							case 1: { $rent_frequency = 'pa'; break; }
						}
	                	update_post_meta( $post_id, 'fave_property_price_prefix', '' );
	                    update_post_meta( $post_id, 'fave_property_price', $price );
	                    update_post_meta( $post_id, 'fave_property_price_postfix', $rent_frequency );
	                }
                }

                update_post_meta( $post_id, 'fave_property_bedrooms', ( ( isset($property['details']['bedrooms']) ) ? $property['details']['bedrooms'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_bathrooms', ( ( isset($property['details']['bathrooms']) ) ? $property['details']['bathrooms'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_rooms', ( ( isset($property['details']['reception_rooms']) ) ? $property['details']['reception_rooms'] : '' ) );
	            update_post_meta( $post_id, 'fave_property_garage', '' );
	            update_post_meta( $post_id, 'fave_property_id', $property['id'] );

	            $address_parts = array();
	            if ( isset($property['address']['address_2']) && $property['address']['address_2'] != '' )
	            {
	                $address_parts[] = $property['address']['address_2'];
	            }
	            if ( isset($property['address']['address_3']) && $property['address']['address_3'] != '' )
	            {
	                $address_parts[] = $property['address']['address_3'];
	            }
	            if ( isset($property['address']['town']) && $property['address']['town'] != '' )
	            {
	                $address_parts[] = $property['address']['town'];
	            }
	            if ( isset($property['address']['address_4']) && $property['address']['address_4'] != '' )
	            {
	                $address_parts[] = $property['address']['address_4'];
	            }
	            if ( isset($property['address']['postcode_1']) && $property['address']['postcode_1'] != '' && isset($property['address']['postcode_2']) && $property['address']['postcode_2'] != '' )
	            {
	                $address_parts[] = trim( $property['address']['postcode_1'] . ' ' . $property['address']['postcode_2'] );
	            }
	            $address_parts = array_unique($address_parts);

	            update_post_meta( $post_id, 'fave_property_map', '1' );
	            update_post_meta( $post_id, 'fave_property_map_address', implode(", ", $address_parts) );
	            $lat = '';
	            $lng = '';
	            if ( isset($property['address']['latitude']) && !empty($property['address']['latitude']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_lat', $property['address']['latitude'] );
	                $lat = $property['address']['latitude'];
	            }
	            if ( isset($property['address']['longitude']) && !empty($property['address']['longitude']) )
	            {
	                update_post_meta( $post_id, 'houzez_geolocation_long', $property['address']['longitude'] );
	                $lng = $property['address']['longitude'];
	            }
	            update_post_meta( $post_id, 'fave_property_location', $lat . "," . $lng . ",14" );
	            update_post_meta( $post_id, 'fave_property_country', 'GB' );
	            
	            $address_parts = array();
	            if ( isset($property['address']['address_2']) && $property['address']['address_2'] != '' )
	            {
	                $address_parts[] = $property['address']['address_2'];
	            }
	            update_post_meta( $post_id, 'fave_property_address', implode(", ", $address_parts) );
	            update_post_meta( $post_id, 'fave_property_zip', ( ( isset($property['address']['postcode_1']) && $property['address']['postcode_2'] ) ? $property['address']['postcode_1'] . ' ' . $property['address']['postcode_2'] : '' ) );

	            update_post_meta( $post_id, 'fave_featured', isset($property['details']['featured']) && $property['details']['featured'] === true ? '1' : '0' );
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
		            				case "branch":
		            				{
		            					$value_in_feed_to_check = $property['branch'];
		            					break;
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
		            				case "branch":
		            				{
		            					$value_in_feed_to_check = $property['branch'];
		            					break;
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
		            				case "branch":
		            				{
		            					$value_in_feed_to_check = $property['branch'];
		            					break;
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
	        	
	            // Turn bullets into property features
	            $feature_term_ids = array();
	            if ( isset($property['details']['features']) && is_array($property['details']['features']) && !empty($property['details']['features']) )
				{
					foreach ( $property['details']['features'] as $feature )
					{
						$term = term_exists( trim($feature), 'property_feature');
						if ( $term !== 0 && $term !== null && isset($term['term_id']) )
						{
							$feature_term_ids[] = (int)$term['term_id'];
						}
						elseif ( apply_filters( 'houzez_property_feed_auto_create_new_features', true ) === true )
						{
							$term = wp_insert_term( trim($feature), 'property_feature' );
							if ( is_array($term) && isset($term['term_id']) )
							{
								$feature_term_ids[] = (int)$term['term_id'];
							}
						}
					}
					if ( !empty($feature_term_ids) )
					{
						wp_set_object_terms( $post_id, $feature_term_ids, "property_feature" );
					}
					else
					{
						wp_delete_object_term_relationships( $post_id, "property_feature" );
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

				$status_field = str_replace('residential-', '', str_replace('sales', 'sale', $department));

				if ( isset($property['status']) && !empty($property['status']) )
				{
					if ( isset($taxonomy_mappings[$property['status']]) && !empty($taxonomy_mappings[$property['status']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['status']], "property_status" );
					}
					else
					{
						$this->log( 'Received status of ' . $property['status'] . ' that isn\'t mapped in the import settings', $property['id'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, $mapping_name, $property['status'], $this->import_id );
					}
				}

				// property type taxonomies
				$taxonomy_mappings = ( isset($mappings['property_type']) && is_array($mappings['property_type']) && !empty($mappings['property_type']) ) ? $mappings['property_type'] : array();

				if ( isset($property['property_type']) && !empty($property['property_type']) )
				{
					if ( isset($taxonomy_mappings[$property['property_type']]) && !empty($taxonomy_mappings[$property['property_type']]) )
					{
						wp_set_object_terms( $post_id, (int)$taxonomy_mappings[$property['property_type']], "property_type" );
					}
					else
					{
						$this->log( 'Received property type of ' . $property['property_type'] . ' that isn\'t mapped in the import settings', $property['id'], $post_id );

						$import_settings = $this->add_missing_mapping( $mappings, 'property_type', $property['property_type'], $this->import_id );
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
						if ( isset($property['address'][$address_field_to_use]) && !empty($property['address'][$address_field_to_use]) )
		            	{
		            		$term = term_exists( trim($property['address'][$address_field_to_use]), $location_taxonomy);
							if ( $term !== 0 && $term !== null && isset($term['term_id']) )
							{
								$location_term_ids[] = (int)$term['term_id'];
							}
							else
							{
								if ( $create_location_taxonomy_terms === true )
								{
									$term = wp_insert_term( trim($property['address'][$address_field_to_use]), $location_taxonomy );
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
					apply_filters('houzez_property_feed_images_stored_as_urls_sme_professional_json', false, $post_id, $property, $this->import_id) === true
				)
				{
					$urls = array();

					if ( isset($property['media']) && is_array($property['media']) && !empty($property['media']) )
					{
						foreach ( $property['media'] as $image )
						{
							if ( $pro_active === true )
							{
								if ( 
									isset($import_settings['limit_images']) && 
									!empty((int)$import_settings['limit_images']) && 
									is_numeric($import_settings['limit_images']) &&
									count($urls) >= $import_settings['limit_images']
								)
					        	{
					        		break;
					        	}
					        }

							if ( 
								isset($image['media_url']) && $image['media_url'] != ''
								&&
								(
									substr( strtolower($image['media_url']), 0, 2 ) == '//' || 
									substr( strtolower($image['media_url']), 0, 4 ) == 'http'
								)
								&&
								isset($image['media_type']) && $image['media_type'] == '1'
							)
							{
								$url = $image['media_url'];

								$urls[] = array(
									'url' => $url
								);
							}
						}
					}

					update_post_meta( $post_id, 'image_urls', $urls );
					update_post_meta( $post_id, 'images_stored_as_urls', true );

					$this->log( 'Imported ' . count($urls) . ' photo URLs', $property['id'], $post_id );
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

								$this->log( 'Imported ' . count($media_ids) . ' images before failing in the previous import. Continuing from here', $property['id'], $post_id );
							}
						}
					}

					if ( isset($property['media']) && is_array($property['media']) && !empty($property['media']) )
					{
						foreach ( $property['media'] as $image )
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
								isset($image['media_url']) && $image['media_url'] != ''
								&&
								(
									substr( strtolower($image['media_url']), 0, 2 ) == '//' || 
									substr( strtolower($image['media_url']), 0, 4 ) == 'http'
								)
								&&
								isset($image['media_type']) && $image['media_type'] == '1'
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
								$url = $image['media_url'];
								$description = '';
							    
								$explode_url = explode('?', $url);
								$filename = basename( $explode_url[0] );

								// Check, based on the URL, whether we have previously imported this media
								$imported_previously = false;
								$imported_previously_id = '';
								if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
								{
									foreach ( $previous_media_ids as $previous_media_id )
									{
										if ( 
											get_post_meta( $previous_media_id, '_imported_url', TRUE ) == $url
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
									if ( apply_filters( 'houzez_property_feed_import_media', true, $this->import_id, $post_id, $property['id'], $url, $url, $description, 'image', $image_i, '' ) === true )
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
									        $this->log_error( 'An error occurred whilst importing ' . $url . '. The error was as follows: ' . $tmp->get_error_message(), $property['id'], $post_id );
									    }
									    else
									    {
										    $id = media_handle_sideload( $file_array, $post_id, $description );

										    // Check for handle sideload errors.
										    if ( is_wp_error( $id ) ) 
										    {
										        @unlink( $file_array['tmp_name'] );
										        
										        $this->log_error( 'ERROR: An error occurred whilst importing ' . $url . '. The error was as follows: ' . $id->get_error_message(), $property['id'], $post_id );
										    }
										    else
										    {
										    	$media_ids[] = $id;

										    	update_post_meta( $id, '_imported_url', $url);

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

					$this->log( 'Imported ' . count($media_ids) . ' photos (' . $new . ' new, ' . $existing . ' existing, ' . $deleted . ' deleted)', $property['id'], $post_id );
					if ( $queued > 0 ) 
					{
						$this->log( $queued . ' photos added to download queue', $property['id'], $post_id );
					}
					
					update_option( 'houzez_property_feed_property_image_media_ids_' . $this->import_id, '', false );
				}

				// Floorplans
				$floorplans = array();

				if ( isset($property['media']) && is_array($property['media']) && !empty($property['media']) )
				{
					foreach ( $property['media'] as $image )
					{
						if ( 
							isset($image['media_url']) && $image['media_url'] != ''
							&&
							(
								substr( strtolower($image['media_url']), 0, 2 ) == '//' || 
								substr( strtolower($image['media_url']), 0, 4 ) == 'http'
							)
							&&
							isset($image['media_type']) && $image['media_type'] == '2'
						)
						{
							// This is a URL
							$url = $image['media_url'];
							$description = __( 'Floorplan', 'houzezpropertyfeed' );

							$floorplans[] = array( 
								"fave_plan_title" => $description, 
								"fave_plan_image" => $floorplan['url']
							);
						}
					}
				}

				if ( !empty($floorplans) )
				{
	                update_post_meta( $post_id, 'floor_plans', $floorplans );
	                update_post_meta( $post_id, 'fave_floor_plans_enable', 'enable' );
	            }
	            else
	            {
	            	update_post_meta( $post_id, 'fave_floor_plans_enable', 'disable' );
	            }

				$this->log( 'Imported ' . count($floorplans) . ' floorplans', $property['id'], $post_id );

				// Brochures and EPCs
				$media_ids = array();
				$new = 0;
				$existing = 0;
				$deleted = 0;
				$previous_media_ids = get_post_meta( $post_id, 'fave_attachments' );

				if ( isset($property['media']) && is_array($property['media']) && !empty($property['media']) )
				{
					foreach ( $property['media'] as $image )
					{
						if ( 
							isset($image['media_url']) && $image['media_url'] != ''
							&&
							(
								substr( strtolower($image['media_url']), 0, 2 ) == '//' || 
								substr( strtolower($image['media_url']), 0, 4 ) == 'http'
							)
							&&
							isset($image['media_type']) && ( $image['media_type'] == '3' || $image['media_type'] == '6' || $image['media_type'] == '7' )
						)
						{
							// This is a URL
							$url = $image['media_url'];
							$description = '';
						    
							$explode_url = explode("?", $url);
							$filename = basename( $explode_url[0] );

							// Check, based on the URL, whether we have previously imported this media
							$imported_previously = false;
							$imported_previously_id = '';
							if ( is_array($previous_media_ids) && !empty($previous_media_ids) )
							{
								foreach ( $previous_media_ids as $previous_media_id )
								{
									if ( 
										get_post_meta( $previous_media_id, '_imported_url', TRUE ) == $url
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

								++$existing;
							}
							else
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
							        $this->log_error( 'An error occurred whilst importing ' . $url . '. The error was as follows: ' . $tmp->get_error_message(), $property['id'], $post_id );
							    }
							    else
							    {
								    $id = media_handle_sideload( $file_array, $post_id, $description, array(
	                                    'post_title' => __( 'Brochure', 'houzezpropertyfeed' ),
	                                    'post_excerpt' => $description
	                                ) );

								    // Check for handle sideload errors.
								    if ( is_wp_error( $id ) ) 
								    {
								        @unlink( $file_array['tmp_name'] );
								        
								        $this->log_error( 'ERROR: An error occurred whilst importing ' . $url . '. The error was as follows: ' . $id->get_error_message(), $property['id'], $post_id );
								    }
								    else
								    {
								    	$media_ids[] = $id;

								    	update_post_meta( $id, '_imported_url', $url);

								    	++$new;
								    }
								}
							}
						}
					}
				}

				if ( $media_ids != $previous_media_ids )
				{
					delete_post_meta( $post_id, 'fave_attachments' );
					foreach ( $media_ids as $media_id )
					{
						add_post_meta( $post_id, 'fave_attachments', $media_id );
					}
				}

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

				$this->log( 'Imported ' . count($media_ids) . ' brochures (' . $new . ' new, ' . $existing . ' existing, ' . $deleted . ' deleted)', $property['id'], $post_id );
				
				$virtual_tours = array();
				if ( isset($property['media']) && is_array($property['media']) && !empty($property['media']) )
				{
					foreach ( $property['media'] as $image )
					{
						if ( 
							isset($image['media_url']) && $image['media_url'] != ''
							&&
							(
								substr( strtolower($image['media_url']), 0, 2 ) == '//' || 
								substr( strtolower($image['media_url']), 0, 4 ) == 'http'
							)
							&&
							isset($image['media_type']) && $image['media_type'] == '4'
						)
						{
							// This is a URL
							$url = $image['media_url'];

							$virtual_tours[] = $url;
						}
					}
				}

				update_post_meta( $post_id, 'fave_video_url', '' );
				update_post_meta( $post_id, 'fave_virtual_tour', '' );

				if ( !empty($virtual_tours) )
				{
					foreach ( $virtual_tours as $virtual_tour )
					{
						if ( 
							$virtual_tour != ''
							&&
							(
								substr( strtolower($virtual_tour), 0, 2 ) == '//' || 
								substr( strtolower($virtual_tour), 0, 4 ) == 'http'
							)
						)
						{
							// This is a URL
							$url = $virtual_tour;

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
				}

				do_action( "houzez_property_feed_property_imported", $post_id, $property, $this->import_id, $this->instance_id );
				do_action( "houzez_property_feed_property_imported_sme_professional_json", $post_id, $property, $this->import_id, $this->instance_id );

				$post = get_post( $post_id );
				do_action( "save_post_property", $post_id, $post, false );
				do_action( "save_post", $post_id, $post, false );

				if ( $inserted_updated == 'updated' )
				{
					$this->compare_meta_and_taxonomy_data( $post_id, $property['id'], $metadata_before, $taxonomy_terms_before );
				}
			}

			++$property_row;

		} // end foreach property

		do_action( "houzez_property_feed_post_import_properties_sme_professional_json", $this->import_id );

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
				$import_refs[] = $property['id'];
			}

			$this->do_remove_old_properties( $import_refs );

			unset($import_refs);
		}
	}
}

}