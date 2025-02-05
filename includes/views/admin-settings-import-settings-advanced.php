<h3><?php echo __( 'Advanced', 'houzezpropertyfeed' ); ?></h3>

<p><?php echo __( 'Advanced import options', 'houzezpropertyfeed' ); ?>.</p>

<table class="form-table">
	<tbody>
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
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo __( 'To restrict the number of properties imported, enter an amount here. Leave blank for no limit.', 'houzezpropertyfeed' ); ?></div>
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
				<div style="color:#999; font-size:13px; margin-top:5px;"><?php echo __( 'To restrict the number of images imported per property, enter an amount here. Leave blank for no limit.', 'houzezpropertyfeed' ); ?></div>
			</td>
		</tr>
	</tbody>
</table>