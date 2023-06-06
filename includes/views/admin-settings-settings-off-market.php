<h3><?php echo __( 'Removing Properties', 'houzezpropertyfeed' ); ?></h3>

<p>Here you can control what happens when a property is removed from the CRM feed.</p>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="remove_action"><?php echo __( 'When properties are removed from imports', 'houzezpropertyfeed' ); ?></label></th>
			<td style="padding-top:20px;">

				<div style="padding:3px 0"><label><input type="radio" name="remove_action" value="" checked> Remove from my website</label></div>

				<div style="padding:3px 0">
					<label><input type="radio" name="remove_action" value="remove_all_media"<?php if ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ) { echo ' disabled'; } ?>> Remove from my website and delete media to free up disk space <?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/pro-label.php' ); ?></label>
				</div>

			</td>
		</tr>
	</tbody>
</table>