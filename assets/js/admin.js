jQuery(document).ready(function()
{
	jQuery('.hpf-admin-settings-import-settings .left-tabs ul li a').click(function(e)
	{
		e.preventDefault();

		var this_href = jQuery(this).attr('href');

		jQuery('.hpf-admin-settings-import-settings .left-tabs ul li').removeClass('active');
		jQuery(this).parent().addClass('active');

		jQuery('.hpf-admin-settings-import-settings .settings-panel').hide();
		jQuery(this_href).fadeIn('fast');
	});

	jQuery('.hpf-admin-settings-import-settings .settings-panel #format').change(function()
	{
		hpf_show_format_settings();
	});

	jQuery('.hpf-admin-settings-import-settings .settings-panel input[name=\'agent_display_option\']').change(function()
	{
		hpf_show_contact_info_rules();
	});

	jQuery('.hpf-admin-settings-import-settings input[name=\'email_reports\']').change(function()
	{
		hpf_show_email_reports_settings();
	});

	jQuery('.hpf-admin-settings-automatic-imports .automatic-imports-table .trash a').click(function()
	{
		var confirm_box = confirm( "Are you sure you want to delete this import?\n\nPLEASE NOTE: If any properties have been imported via this import they will remain in place and will need to be deleted manually" );

		return confirm_box;
	});

	jQuery('.agent-display-option-add-rule-button').click(function(e)
	{
		e.preventDefault();

		var this_id = jQuery(this).attr('id');
		this_id = this_id.replace("agent_display_option_add_rule_button_", "");

		var template_html = jQuery('#agent_display_option_rule_template_' + this_id).html();

		jQuery('#agent_display_option_rules_' + this_id).append(template_html);
	});

	jQuery('body').on('click', '.agent-display-option-rule .delete-rule a', function(e)
	{
		e.preventDefault();
		jQuery(this).parent().parent().remove();
	});

	jQuery('.field-mapping-add-rule-button').click(function(e)
	{
		e.preventDefault();

		var template_html = jQuery('#field_mapping_rule_template').html();

		jQuery('#field_mapping_rules').append(template_html);
	});

	jQuery('body').on('click', '.field-mapping-rule .delete-rule a', function(e)
	{
		e.preventDefault();
		jQuery(this).parent().parent().remove();
	});

	jQuery('body').on('click', '.hpf-admin-settings-import-settings a.add-additional-mapping', function(e)
	{
		e.preventDefault();
		
		var taxonomy = jQuery(this).attr('href').replace("#", "");

		var taxonomy_options = new Array();
		switch (taxonomy)
		{
			case "sales_status":
			case "lettings_status":
			{
				taxonomy_options = hpf_admin_object.statuses;
				break;
			}
			case "property_type":
			{
				taxonomy_options = hpf_admin_object.property_types;
				break;
			}
		}

		var row_html_dropdown = '';
		row_html_dropdown += '<select name="custom_mapping_value[' + taxonomy + '][]">';
		row_html_dropdown += '<option value=""></option>';
		if ( Object.keys(taxonomy_options).length > 0 )
		{	
			for ( var j in taxonomy_options )
			{
				row_html_dropdown += '<option value="' + j + '">' + taxonomy_options[j] + '</option>';
			}
		}
		row_html_dropdown += '</select>';

		var row_html = '';
		row_html += '<tr>';
		row_html += '<td style="padding-left:0"><input type="text" name="custom_mapping[' + taxonomy + '][]" value=""></td>';
		row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
		row_html += '</tr>';

		jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_' + taxonomy).append(row_html);

	});

	hpf_show_email_reports_settings();
	hpf_show_format_settings();
	hpf_show_contact_info_rules();
});

function hpf_show_email_reports_settings()
{
	jQuery('.hpf-admin-settings-import-settings #email_reports_to_row').hide();

	if ( jQuery('.hpf-admin-settings-import-settings input[name=\'email_reports\']').is(':checked') )
	{
		jQuery('.hpf-admin-settings-import-settings #email_reports_to_row').show();
	}
}

function hpf_show_format_settings()
{
	var selected_format = jQuery('.hpf-admin-settings-import-settings .settings-panel #format').val();

	jQuery('.hpf-admin-settings-import-settings .settings-panel .import-settings-format').hide();
	jQuery('#import_settings_' + selected_format).fadeIn('fast');

	jQuery('.no-format-notice').hide();
	jQuery('.hpf-import-format-name').html('');

	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_property_type').hide();
	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_sales_status').hide();
	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_lettings_status').hide();

	jQuery('.hpf-admin-settings-import-settings #property_city_address_field').empty();
	jQuery('.hpf-admin-settings-import-settings #property_area_address_field').empty();
	jQuery('.hpf-admin-settings-import-settings #property_state_address_field').empty();

	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_property_type tr').not(':nth-child(1)').remove();

	jQuery('.hpf-admin-settings-import-settings select[name=\'author_info_rules_field[]\']').empty();
	jQuery('.hpf-admin-settings-import-settings select[name=\'agent_info_rules_field[]\']').empty();
	jQuery('.hpf-admin-settings-import-settings select[name=\'agency_info_rules_field[]\']').empty();

	if ( selected_format == '' )
	{
		jQuery('.no-format-notice').show();
	}
	else
	{
		var taxonomy_values_sales_status = new Array();
		var taxonomy_values_lettings_status = new Array();
		var taxonomy_values_property_type = new Array();
		var address_fields = new Array();
		var contact_information_fields = new Array();

		for ( var i in hpf_admin_object.formats )
		{
			if ( i == selected_format )
			{
				if ( Object.keys(hpf_admin_object.formats[i].taxonomy_values.sales_status).length > 0 ) { taxonomy_values_sales_status = hpf_admin_object.formats[i].taxonomy_values.sales_status; }
				if ( Object.keys(hpf_admin_object.formats[i].taxonomy_values.lettings_status).length > 0 ) { taxonomy_values_lettings_status = hpf_admin_object.formats[i].taxonomy_values.lettings_status; }
				if ( Object.keys(hpf_admin_object.formats[i].taxonomy_values.property_type).length > 0 ) { taxonomy_values_property_type = hpf_admin_object.formats[i].taxonomy_values.property_type; }
				address_fields = hpf_admin_object.formats[i].address_fields;
				if ( Object.keys(hpf_admin_object.formats[i].contact_information_fields).length > 0 ) { contact_information_fields = hpf_admin_object.formats[i].contact_information_fields; }

				jQuery('.hpf-import-format-name').html(hpf_admin_object.formats[i].name);
				break;
			}
		}

		// Sales status taxonomy mapping
		if ( Object.keys(taxonomy_values_sales_status).length > 0 )
		{
			jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_sales_status').show();

			for ( var i in taxonomy_values_sales_status )
			{
				var row_html_dropdown = '';
				row_html_dropdown += '<select name="taxonomy_mapping[sales_status][' + i + ']">';
				row_html_dropdown += '<option value=""></option>';
				if ( Object.keys(hpf_admin_object.statuses).length > 0 )
				{	
					for ( var j in hpf_admin_object.statuses )
					{
						var selected_status = false;
						if ( 
							hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
							hpf_admin_object.import_settings.mappings.hasOwnProperty('sales_status') &&
							hpf_admin_object.import_settings.mappings.sales_status.hasOwnProperty(i)
						)
						{
							if ( hpf_admin_object.import_settings.mappings.sales_status[i] == j )
							{
								selected_status = true;
							}
						}
						if ( !selected_status )
						{
							// TO DO: set by default if match found
						}
						row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.statuses[j] + '</option>';
					}
				}
				row_html_dropdown += '</select>';

				var row_html = '';
				row_html += '<tr>';
				row_html += '<td style="padding-left:0">' + i + ( taxonomy_values_sales_status[i] != i ? ' - <span style="color:#999">' + taxonomy_values_sales_status[i] + '</span>' : '' )  + '</td>';
				row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
				row_html += '</tr>';

				jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_sales_status').append(row_html);
			}

			// add any custom mappings
			if ( Object.keys(hpf_admin_object.import_settings.mappings.sales_status).length > 0 )
			{
				for ( var i in hpf_admin_object.import_settings.mappings.sales_status )
				{
					var found_in_standard_list = false;
					for ( var j in taxonomy_values_sales_status )
					{
						if ( i == j )
						{
							found_in_standard_list = true;
							break;
						}
					}
					if ( !found_in_standard_list )
					{
						var row_html_dropdown = '';
						row_html_dropdown += '<select name="custom_mapping_value[sales_status][' + i + ']">';
						row_html_dropdown += '<option value=""></option>';
						if ( Object.keys(hpf_admin_object.statuses).length > 0 )
						{	
							for ( var j in hpf_admin_object.statuses )
							{
								var selected_status = false;
								if ( 
									hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
									hpf_admin_object.import_settings.mappings.hasOwnProperty('sales_status') &&
									hpf_admin_object.import_settings.mappings.sales_status.hasOwnProperty(i)
								)
								{
									if ( hpf_admin_object.import_settings.mappings.sales_status[i] == j )
									{
										selected_status = true;
									}
								}
								if ( !selected_status )
								{
									// TO DO: set by default if match found
								}
								row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.statuses[j] + '</option>';
							}
						}
						row_html_dropdown += '</select>';

						var row_html = '';
						row_html += '<tr>';
						row_html += '<td style="padding-left:0"><input type="text" name="custom_mapping[sales_status][' + i + ']" value="' + i + '"></td>';
						row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
						row_html += '</tr>';

						jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_sales_status').append(row_html);
					}
				}
			}
		}

		// Lettings status taxonomy mapping
		if ( Object.keys(taxonomy_values_lettings_status).length > 0 )
		{
			jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_lettings_status').show();

			for ( var i in taxonomy_values_lettings_status )
			{
				var row_html_dropdown = '';
				row_html_dropdown += '<select name="taxonomy_mapping[lettings_status][' + i + ']">';
				row_html_dropdown += '<option value=""></option>';
				if ( Object.keys(hpf_admin_object.statuses).length > 0 )
				{	
					for ( var j in hpf_admin_object.statuses )
					{
						var selected_status = false;
						if ( 
							hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
							hpf_admin_object.import_settings.mappings.hasOwnProperty('lettings_status') &&
							hpf_admin_object.import_settings.mappings.lettings_status.hasOwnProperty(i)
						)
						{
							if ( hpf_admin_object.import_settings.mappings.lettings_status[i] == j )
							{
								selected_status = true;
							}
						}
						if ( !selected_status )
						{
							// TO DO: set by default if match found
						}
						row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.statuses[j] + '</option>';
					}
				}
				row_html_dropdown += '</select>';

				var row_html = '';
				row_html += '<tr>';
				row_html += '<td style="padding-left:0">' + i + ( taxonomy_values_lettings_status[i] != i ? ' - <span style="color:#999">' + taxonomy_values_lettings_status[i] + '</span>' : '' )  + '</td>';
				row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
				row_html += '</tr>';

				jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_lettings_status').append(row_html);
			}

			// add any custom mappings
			if ( Object.keys(hpf_admin_object.import_settings.mappings.lettings_status).length > 0 )
			{
				for ( var i in hpf_admin_object.import_settings.mappings.lettings_status )
				{
					var found_in_standard_list = false;
					for ( var j in taxonomy_values_lettings_status )
					{
						if ( i == j )
						{
							found_in_standard_list = true;
							break;
						}
					}
					if ( !found_in_standard_list )
					{
						var row_html_dropdown = '';
						row_html_dropdown += '<select name="custom_mapping_value[lettings_status][' + i + ']">';
						row_html_dropdown += '<option value=""></option>';
						if ( Object.keys(hpf_admin_object.statuses).length > 0 )
						{	
							for ( var j in hpf_admin_object.statuses )
							{
								var selected_status = false;
								if ( 
									hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
									hpf_admin_object.import_settings.mappings.hasOwnProperty('lettings_status') &&
									hpf_admin_object.import_settings.mappings.lettings_status.hasOwnProperty(i)
								)
								{
									if ( hpf_admin_object.import_settings.mappings.lettings_status[i] == j )
									{
										selected_status = true;
									}
								}
								if ( !selected_status )
								{
									// TO DO: set by default if match found
								}
								row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.statuses[j] + '</option>';
							}
						}
						row_html_dropdown += '</select>';

						var row_html = '';
						row_html += '<tr>';
						row_html += '<td style="padding-left:0"><input type="text" name="custom_mapping[lettings_status][' + i + ']" value="' + i + '"></td>';
						row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
						row_html += '</tr>';

						jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_lettings_status').append(row_html);
					}
				}
			}
		}

		// Property type taxonomy mapping
		if ( Object.keys(taxonomy_values_property_type).length > 0 )
		{
			jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_property_type').show();

			for ( var i in taxonomy_values_property_type )
			{
				var row_html_dropdown = '';
				row_html_dropdown += '<select name="taxonomy_mapping[property_type][' + i + ']">';
				row_html_dropdown += '<option value=""></option>';
				if ( Object.keys(hpf_admin_object.property_types).length > 0 )
				{	
					for ( var j in hpf_admin_object.property_types )
					{
						var selected_status = false;
						if ( 
							hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
							hpf_admin_object.import_settings.mappings.hasOwnProperty('property_type') &&
							hpf_admin_object.import_settings.mappings.property_type.hasOwnProperty(i)
						)
						{
							if ( hpf_admin_object.import_settings.mappings.property_type[i] == j )
							{
								selected_status = true;
							}
						}
						if ( !selected_status )
						{
							// TO DO: set by default if match found
						}
						row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.property_types[j] + '</option>';
					}
				}
				row_html_dropdown += '</select>';

				var row_html = '';
				row_html += '<tr>';
				row_html += '<td style="padding-left:0">' + i + ( taxonomy_values_property_type[i] != i ? ' - <span style="color:#999">' + taxonomy_values_property_type[i] + '</span>' : '' )  + '</td>';
				row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
				row_html += '</tr>';

				jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_property_type').append(row_html);
			}

			// add any custom mappings
			if ( Object.keys(hpf_admin_object.import_settings.mappings.property_type).length > 0 )
			{
				for ( var i in hpf_admin_object.import_settings.mappings.property_type )
				{
					var found_in_standard_list = false;
					for ( var j in taxonomy_values_property_type )
					{
						if ( i == j )
						{
							found_in_standard_list = true;
							break;
						}
					}
					if ( !found_in_standard_list )
					{
						var row_html_dropdown = '';
						row_html_dropdown += '<select name="custom_mapping_value[property_type][' + i + ']">';
						row_html_dropdown += '<option value=""></option>';
						if ( Object.keys(hpf_admin_object.property_types).length > 0 )
						{	
							for ( var j in hpf_admin_object.property_types )
							{
								var selected_status = false;
								if ( 
									hpf_admin_object.import_settings.hasOwnProperty('mappings') && 
									hpf_admin_object.import_settings.mappings.hasOwnProperty('property_type') &&
									hpf_admin_object.import_settings.mappings.property_type.hasOwnProperty(i)
								)
								{
									if ( hpf_admin_object.import_settings.mappings.property_type[i] == j )
									{
										selected_status = true;
									}
								}
								if ( !selected_status )
								{
									// TO DO: set by default if match found
								}
								row_html_dropdown += '<option value="' + j + '"' + ( selected_status ? ' selected' : '' ) + '>' + hpf_admin_object.property_types[j] + '</option>';
							}
						}
						row_html_dropdown += '</select>';

						var row_html = '';
						row_html += '<tr>';
						row_html += '<td style="padding-left:0"><input type="text" name="custom_mapping[property_type][' + i + ']" value="' + i + '"></td>';
						row_html += '<td style="padding-left:0">' + row_html_dropdown + '</td>';
						row_html += '</tr>';

						jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_table_property_type').append(row_html);
					}
				}
			}
		}

		// Address taxonomy options
		if ( address_fields.length > 0 )
		{
			jQuery('.hpf-admin-settings-import-settings #property_city_address_field').append('<option value=""></option');
			for ( var i in address_fields )
			{
				var selected_status = false;
				if ( hpf_admin_object.import_settings.hasOwnProperty('property_city_address_field') )
				{
					if ( hpf_admin_object.import_settings.property_city_address_field == address_fields[i] )
					{
						selected_status = true;
					}
				}
				jQuery('.hpf-admin-settings-import-settings #property_city_address_field').append('<option value="' + address_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + address_fields[i] + '</option');
			}

			jQuery('.hpf-admin-settings-import-settings #property_area_address_field').append('<option value=""></option');
			for ( var i in address_fields )
			{
				var selected_status = false;
				if ( hpf_admin_object.import_settings.hasOwnProperty('property_area_address_field') )
				{
					if ( hpf_admin_object.import_settings.property_area_address_field == address_fields[i] )
					{
						selected_status = true;
					}
				}
				jQuery('.hpf-admin-settings-import-settings #property_area_address_field').append('<option value="' + address_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + address_fields[i] + '</option');
			}

			jQuery('.hpf-admin-settings-import-settings #property_state_address_field').append('<option value=""></option');
			for ( var i in address_fields )
			{
				var selected_status = false;
				if ( hpf_admin_object.import_settings.hasOwnProperty('property_state_address_field') )
				{
					if ( hpf_admin_object.import_settings.property_state_address_field == address_fields[i] )
					{
						selected_status = true;
					}
				}
				jQuery('.hpf-admin-settings-import-settings #property_state_address_field').append('<option value="' + address_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + address_fields[i] + '</option');
			}
		}

		if ( Object.keys(contact_information_fields).length > 0 )
		{
			var rules = hpf_admin_object.import_settings.agent_display_option_rules;

			var rule_i = -1;
			jQuery('.hpf-admin-settings-import-settings select[name=\'author_info_rules_field[]\']').each(function()
			{
				jQuery(this).append('<option value=""></option>');

				for ( var i in contact_information_fields )
				{
					var selected_status = false;
					
					var option_value = contact_information_fields[i];

					// get selected field at this rule
					for ( var j in rules )
					{
						if ( j == rule_i )
						{
							if ( option_value == rules[j].field )
							{
								selected_status = true;
							}
						}
					}

					jQuery(this).append('<option value="' + contact_information_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + contact_information_fields[i] + '</option>');
				}

				rule_i = rule_i + 1;
			});

			var rule_i = -1;
			jQuery('.hpf-admin-settings-import-settings select[name=\'agent_info_rules_field[]\']').each(function()
			{
				jQuery(this).append('<option value=""></option>');

				for ( var i in contact_information_fields )
				{
					var selected_status = false;
					
					var option_value = contact_information_fields[i];

					// get selected field at this rule
					for ( var j in rules )
					{
						if ( j == rule_i )
						{
							if ( option_value == rules[j].field )
							{
								selected_status = true;
							}
						}
					}

					jQuery(this).append('<option value="' + contact_information_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + contact_information_fields[i] + '</option>');
				}

				rule_i = rule_i + 1;
			});

			var rule_i = -1;
			jQuery('.hpf-admin-settings-import-settings select[name=\'agency_info_rules_field[]\']').each(function()
			{
				jQuery(this).append('<option value=""></option>');

				for ( var i in contact_information_fields )
				{
					var selected_status = false;
					
					var option_value = contact_information_fields[i];

					// get selected field at this rule
					for ( var j in rules )
					{
						if ( j == rule_i )
						{
							if ( option_value == rules[j].field )
							{
								selected_status = true;
							}
						}
					}
					
					jQuery(this).append('<option value="' + contact_information_fields[i] + '"' + ( selected_status ? ' selected' : '' ) + '>' + contact_information_fields[i] + '</option>');
				}

				rule_i = rule_i + 1;
			});
		}
	}
}

function hpf_show_contact_info_rules()
{
	var selected_agent_display_option = jQuery('.hpf-admin-settings-import-settings .settings-panel input[name=\'agent_display_option\']:checked').val();

	jQuery('.hpf-admin-settings-import-settings .agent-display-option-rules').hide();
	jQuery('#agent_display_option_rules_container_' + selected_agent_display_option).fadeIn('fast');
}