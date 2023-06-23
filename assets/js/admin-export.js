jQuery(document).ready(function()
{
	jQuery('.hpf-admin-settings-import-settings .settings-panel #format').select2({ allowClear: true, placeholder:"Select..." });
	
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

	hpf_show_format_settings();
});

function hpf_show_format_settings()
{
	var selected_format = jQuery('.hpf-admin-settings-import-settings .settings-panel #format').val();

	jQuery('.hpf-admin-settings-import-settings .settings-panel .import-settings-format').hide();
	jQuery('#export_settings_' + selected_format).fadeIn('fast');

	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_property_type').hide();
	jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_status').hide();

	jQuery('#export_name_row').hide();

	jQuery('.no-format-notice').hide();
	jQuery('.hpf-export-format-name').html('');

	jQuery('#export_setting_tab_frequency').hide();

	if ( selected_format == '' )
	{
		jQuery('.no-format-notice').show();
	}
	else
	{
		jQuery('#export_name_row').show();

		// set name
		if ( jQuery('#export_name').val() == '' )
		{
			jQuery('#export_name').val( jQuery('.hpf-admin-settings-import-settings .settings-panel #format').find(':selected').text() );
		}

		var has_taxonomy_values_status = false;
		var taxonomy_values_status = new Array();
		
		var has_taxonomy_values_property_type = false;
		var taxonomy_values_property_type = new Array();

		for ( var i in hpf_admin_object.formats )
		{
			if ( i == selected_format )
			{
				if ( hpf_admin_object.formats[i].method == 'cron' )
				{
					jQuery('#export_setting_tab_frequency').show();
				}

				if ( hpf_admin_object.formats[i].taxonomy_values.hasOwnProperty('status') )
				{
					has_taxonomy_values_status = true;
					if ( Object.keys(hpf_admin_object.formats[i].taxonomy_values.status).length > 0 ) 
					{ 
						taxonomy_values_status = hpf_admin_object.formats[i].taxonomy_values.status; 
					}
				}
				
				if ( hpf_admin_object.formats[i].taxonomy_values.hasOwnProperty('property_type') )
				{
					has_taxonomy_values_property_type = true;
					if (Object.keys(hpf_admin_object.formats[i].taxonomy_values.property_type).length > 0 ) 
					{ 
						taxonomy_values_property_type = hpf_admin_object.formats[i].taxonomy_values.property_type; 
					}	
				} 

				jQuery('.hpf-export-format-name').html(hpf_admin_object.formats[i].name);
				break;
			}
		}

		// Status taxonomy mapping
		if ( has_taxonomy_values_status )
		{
			jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_status').show();

			jQuery('select[name^=\'taxonomy_mapping[property_status]\']').each(function()
			{
				jQuery(this).empty();

				jQuery(this).append( '<option value=""></option>' );

				var term_id = jQuery(this).attr('name').replace("taxonomy_mapping[property_status][", "");
				term_id = term_id.replace("]", "");

				if ( Object.keys(taxonomy_values_status).length > 0 )
				{
					for ( var i in taxonomy_values_status )
					{
						selected_status = false;
						if ( 
							hpf_admin_object.export_settings.hasOwnProperty('mappings') && 
							hpf_admin_object.export_settings.mappings.hasOwnProperty('property_status') &&
							hpf_admin_object.export_settings.mappings.property_status.hasOwnProperty(term_id)
						)
						{
							if ( hpf_admin_object.export_settings.mappings.property_status[term_id] == i )
							{
								selected_status = true;
							}
						}
						if ( !selected_status )
						{
							// TO DO: set by default if match found
						}
						jQuery(this).append( '<option value="' + i + '"' + ( selected_status ? ' selected' : '' ) + '>' + taxonomy_values_status[i] + '</option>' );

					}
				}
			});
		}

		// Property type taxonomy mapping
		if ( has_taxonomy_values_property_type )
		{
			jQuery('.hpf-admin-settings-import-settings #taxonomy_mapping_property_type').show();

			jQuery('select[name^=\'taxonomy_mapping[property_type]\']').each(function()
			{
				jQuery(this).empty();

				jQuery(this).append( '<option value=""></option>' );

				var term_id = jQuery(this).attr('name').replace("taxonomy_mapping[property_type][", "");
				term_id = term_id.replace("]", "");

				if ( Object.keys(taxonomy_values_property_type).length > 0 )
				{
					for ( var i in taxonomy_values_property_type )
					{
						selected_status = false;
						if ( 
							hpf_admin_object.export_settings.hasOwnProperty('mappings') && 
							hpf_admin_object.export_settings.mappings.hasOwnProperty('property_type') &&
							hpf_admin_object.export_settings.mappings.property_type.hasOwnProperty(term_id)
						)
						{
							if ( hpf_admin_object.export_settings.mappings.property_type[term_id] == i )
							{
								selected_status = true;
							}
						}
						if ( !selected_status )
						{
							// TO DO: set by default if match found
						}
						jQuery(this).append( '<option value="' + i + '"' + ( selected_status ? ' selected' : '' ) + '>' + taxonomy_values_property_type[i] + '</option>' );

					}
				}
			});
		}
	}
}