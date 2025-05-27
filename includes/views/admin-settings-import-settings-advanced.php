<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<h3><?php echo __( 'Advanced', 'houzezpropertyfeed' ); ?></h3>

<p><?php echo __( 'Advanced import options', 'houzezpropertyfeed' ); ?>.</p>

<table class="form-table">
	<tbody>
		<tr id="row_custom_name">
			<th><label for="custom_name"><?php echo __( 'Import Name', 'houzezpropertyfeed' ); ?></label></th>
			<td style="padding-top:20px;">
				<input type="text" name="custom_name" id="custom_name" value="<?php echo isset($import_settings['custom_name']) ? esc_attr($import_settings['custom_name']) : '' ; ?>">
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo esc_html(__( 'Used to distinguish between imports when you have multiple running at once. Leave blank to use the format type as the name.', 'houzezpropertyfeed' )); ?></div>
			</td>
		</tr>
		<tr>
			<th><label for="limit"><?php echo __( 'Limit Number of Properties Imported', 'houzezpropertyfeed' ); ?></label></th>
			<td style="padding-top:20px;">
				<input type="number" name="limit" id="limit" min="1" value="<?php echo ( apply_filters( 'houzez_property_feed_pro_active', false ) === true && isset($import_settings['limit']) ) ? esc_attr($import_settings['limit']) : '' ; ?>"<?php echo ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ? ' disabled' : '' ); ?>>
				<?php
					if ( isset($frequency['pro']) && $frequency['pro'] === true )
					{
						include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/pro-label.php' );
					}
				?>
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo esc_html(__( 'To restrict the number of properties imported, enter an amount here. Leave blank for no limit.', 'houzezpropertyfeed' )); ?></div>
			</td>
		</tr>
		<tr>
			<th><label for="limit_images"><?php echo __( 'Limit Number of Images Imported Per Property', 'houzezpropertyfeed' ); ?></label></th>
			<td style="padding-top:20px;">
				<input type="number" name="limit_images" id="limit_images" min="1" value="<?php echo ( apply_filters( 'houzez_property_feed_pro_active', false ) === true && isset($import_settings['limit_images']) ) ? esc_attr($import_settings['limit_images']) : '' ; ?>"<?php echo ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ? ' disabled' : '' ); ?>>
				<?php
					if ( isset($frequency['pro']) && $frequency['pro'] === true )
					{
						include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/pro-label.php' );
					}
				?>
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo esc_html(__( 'To restrict the number of images imported per property, enter an amount here. Leave blank for no limit.', 'houzezpropertyfeed' )); ?></div>
			</td>
		</tr>
		<tr id="row_background_mode">
			<th><label for="background_mode"><?php echo esc_html(__( 'Background Mode', 'houzezpropertyfeed' )); ?></label></th>
			<td style="padding-top:20px;">

				<?php 
					$disabled = false;
					if ( function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))) )
					{
						$output = [];
					    $result = null;
					    exec("which wget", $output, $result);
					    if ($result === 0)
					    {

					    }
					    else
					    {
					    	$disabled = true;
					    	$error = __( 'It looks like the functionality needed, called wget, to perform background mode isn\'t enabled on this server so you won\'t be able to utilise this feature.', 'houzezpropertyfeed' );
					    }
					}
					else
					{
						$disabled = true;
						$error = __( 'It looks like the functionality needed, called exec(), to perform background mode isn\'t enabled on this server so you won\'t be able to utilise this feature.', 'houzezpropertyfeed' );
					}
				?>

				<select name="background_mode" id="background_mode"<?php echo ( ( $disabled === true || apply_filters( 'houzez_property_feed_pro_active', false ) !== true ) ? ' disabled' : '' ); ?>>
					<option value="">No</option>
					<option value="yes"<?php echo ( apply_filters( 'houzez_property_feed_pro_active', false ) === true && isset($import_settings['background_mode']) && $import_settings['background_mode'] == 'yes' ) ? ' selected' : '' ; ?>>Yes</option>
				</select>
				<?php
					if ( isset($frequency['pro']) && $frequency['pro'] === true )
					{
						include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/pro-label.php' );
					}
				?>
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo esc_html(__( 'If you\'re struggling to get properties imported due to timeout limits or similar, enabling this mode might help. With this enabled we\'ll get all properties and queue them, and then process them in the background in batches.', 'houzezpropertyfeed' )); ?></div>
				<?php 
					if ( $disabled === true && $error != '' )
					{
				?>
				<div style="color:#900; font-weight:700; font-size:13px; margin-top:5px;"><?php echo esc_html($error); ?></div>
				<?php
					}
				?>
			</td>
		</tr>
	</tbody>
</table>