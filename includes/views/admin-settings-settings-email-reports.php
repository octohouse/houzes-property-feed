<h3><?php echo __( 'Email Reports', 'houzezpropertyfeed' ); ?></h3>

<p>With email reports enabled you can have the logs automatically emailed to you each time an import finishes running.</p>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="email_reports"><?php echo __( 'Enable Email Reports', 'houzezpropertyfeed' ); ?></label></th>
			<td style="padding-top:18px;">
				<input type="checkbox" name="email_reports" id="email_reports" value="yes"<?php if ( apply_filters( 'houzez_property_feed_pro_active', false ) !== true ) { echo ' disabled'; } ?>>
				<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/pro-label.php' ); ?>
			</td>
		</tr>
	</tbody>
</table>