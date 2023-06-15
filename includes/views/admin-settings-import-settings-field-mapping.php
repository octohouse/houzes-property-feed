<div class="notice notice-error no-format-notice inline"><p>Please select an import format in order to configure the following page.</p></div>

<h3><?php echo __( 'Additional Field Mapping', 'houzezpropertyfeed' ); ?></h3>

<p>Here you can do any additional field mapping to cater for non-standard mapping or to import into any custom fields you've set up in the <a href="<?php echo admin_url('admin.php?page=houzez_fbuilder'); ?>" target="_blank">Houzez Field Builder</a>.</p>

<?php
	$houzez_fields = array(
		'fave_property_sec_price' => __( 'Second Price (Optional)', 'houzez' ),
		'fave_property_price_prefix' => __( 'Price Prefix', 'houzez' ),
		'fave_property_price_postfix' => __( 'After The Price', 'houzez' ),
		'fave_property_size' => __( 'Area Size', 'houzez' ),
		'fave_property_size_prefix' => __( 'Size Postfix', 'houzez' ),
		'fave_property_bedrooms' => __( 'Bedrooms', 'houzez' ),
		'fave_property_rooms' => __( 'Rooms', 'houzez' ),
		'fave_property_bathrooms' => __( 'Bathrooms', 'houzez' ),
		'fave_property_garage' => __( 'Garages', 'houzez' ),
		'fave_property_garage_size' => __( 'Garage Size', 'houzez' ),
		'fave_property_year' => __( 'Year Built', 'houzez' ),
		'fave_property_id' => __( 'Property ID', 'houzez' ),
		'fave_property_address' => __( 'Street Address', 'houzez' ),
		'fave_property_zip' => __( 'Zip/Postal Code', 'houzez' ),
		'fave_property_disclaimer' => __( 'Disclaimer', 'houzez' ),
		'fave_video_url' => __( 'Video URL', 'houzez' ),
		'fave_virtual_tour' => __( '360° Virtual Tour', 'houzez' ),
		'fave_energy_class' => __( 'Energy Class', 'houzez' ),
		'fave_energy_global_index' => __( 'Global Energy Performance Index', 'houzez' ),
		'fave_renewable_energy_global_index' => __( 'Renewable energy performance index', 'houzez' ),
		'fave_energy_performance' => __( 'Energy performance of the building', 'houzez' ),
		'fave_epc_current_rating' => __( 'EPC Current Rating', 'houzez' ),
		'fave_epc_potential_rating' => __( 'EPC Potential Rating', 'houzez' ),
		'fave_property_land' => __( 'Land Area', 'houzez' ),
		'fave_property_land_postfix' => __( 'Land Area Size Postfix', 'houzez' ),
		'fave_property_price' => __( 'Sale or Rent Price', 'houzez' ),
	);

	// add any fields from field builder
	$houzez_fields_builder = new Houzez_Fields_Builder();
	$houzez_fields_built = $houzez_fields_builder::get_form_fields();

	if ( $houzez_fields_built !== FALSE && is_array($houzez_fields_built) && !empty($houzez_fields_built) )
	{
		foreach ( $houzez_fields_built as $field_build )
		{
			$houzez_fields['fave_' . $field_build->field_id] = $field_build->label;
		}
	}

	$houzez_fields = apply_filters( 'houzez_property_feed_field_mapping_houzez_fields', $houzez_fields );

	asort($houzez_fields);

	// convert old-style rules to new style
	if ( isset($import_settings['field_mapping_rules']) && !empty($import_settings['field_mapping_rules']) )
	{
		$import_settings['field_mapping_rules'] = convert_old_field_mapping_to_new( $import_settings['field_mapping_rules'] );
	}
?>

<table class="form-table">
	<tbody>
		<tr>
			<th>Rules</th>
			<td>
 				<div id="field_mapping_rule_template" style="display:none">
					<div class="field-mapping-rule">
						<div class="and-rules">
							<div class="or-rule">
								<div>
									If 
									<input type="text" name="field_mapping_rules[{rule_count}][field][]" value="">
									field in <span class="hpf-import-format-name"></span> feed
								</div>
								<div>
									Is equal to 
									<input type="text" name="field_mapping_rules[{rule_count}][equal][]" placeholder="Value in feed, or use * wildcard">
								</div>
								<div class="rule-actions">
									<a href="" class="add-and-rule-action"><span class="dashicons dashicons-plus2"></span> Add AND Rule</a> | <a href="" class="delete-action"><span class="dashicons dashicons-trash"></span> Delete Rule</a>
								</div>
							</div>
						</div>
						<div class="then">
							<div style="padding:20px 0; font-weight:600">THEN</div>
							<div>
								Set Houzez field
								<select name="field_mapping_rules[{rule_count}][houzez_field]">
									<option value=""></option>
									<?php
										if ( !empty($houzez_fields) )
										{
											foreach ( $houzez_fields as $key => $value )
											{
												echo '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
											}
										}
									?>
								</select> 
							</div>
							<div>
								To
								<input type="text" name="field_mapping_rules[{rule_count}][result]" style="width:100%; max-width:340px;" value="" placeholder="Enter value or {field_name_here} to use value sent">
							</div>
						</div>
					</div>
				</div>

				<div id="field_mapping_rules">
					<?php
						if ( isset($import_settings['field_mapping_rules']) && !empty($import_settings['field_mapping_rules']) )
						{
							foreach ( $import_settings['field_mapping_rules'] as $i => $and_rules )
							{
					?>
					<div class="field-mapping-rule">
						<div class="and-rules">
							<?php $rule_i = 0; foreach ( $and_rules['rules'] as $or_rule ) { ?>
							<div class="or-rule">
								<?php if ( $rule_i > 0 ) { echo '<div style="padding:20px 0; font-weight:600" class="and-label">AND</div>'; } ?>
								<div>
									If 
									<input type="text" name="field_mapping_rules[<?php echo $i; ?>][field][]" value="<?php echo esc_attr($or_rule['field']); ?>">
									field in <span class="hpf-import-format-name"></span> feed
								</div>
								<div>
									Is equal to 
									<input type="text" name="field_mapping_rules[<?php echo $i; ?>][equal][]" value="<?php echo esc_attr($or_rule['equal']); ?>" placeholder="Value in feed, or use * wildcard">
								</div>
								<div class="rule-actions">
									<a href="" class="add-and-rule-action"><span class="dashicons dashicons-plus-alt2"></span> Add AND Rule</a> | <a href="" class="delete-action"><span class="dashicons dashicons-trash"></span> Delete Rule</a>
								</div>
							</div>
							<?php ++$rule_i; } // end foreach AND rules ?>
						</div>
						<div class="then">
							<div style="padding:20px 0; font-weight:600">THEN</div>
							<div>
								Set Houzez field
								<select name="field_mapping_rules[<?php echo $i; ?>][houzez_field]">
									<option value=""></option>
									<?php
										if ( !empty($houzez_fields) )
										{
											foreach ( $houzez_fields as $key => $value )
											{
												echo '<option value="' . esc_attr($key) . '"';
												if ( $key == $and_rules['houzez_field'] ) { echo ' selected'; }
												echo '>' . esc_html($value) . '</option>';
											}
										}
									?>
								</select> 
							</div>
							<div>
								To
								<input type="text" name="field_mapping_rules[<?php echo $i; ?>][result]" style="width:100%; max-width:340px;" value="<?php echo esc_attr($and_rules['result']); ?>" placeholder="Enter value or {field_name_here} to use value sent">
							</div>
						</div>
					</div>
					<?php
							}
						}
					?>
				</div>

				<a href="" class="button field-mapping-add-or-rule-button">Add Field Mapping Rule</a>
			
			</td>
		</tr>
	</tbody>
</table>

<script>
	var hpf_rule_count = <?php echo ( isset($import_settings['field_mapping_rules']) && !empty($import_settings['field_mapping_rules']) ) ? count($import_settings['field_mapping_rules']) : 0; ?>;
</script>