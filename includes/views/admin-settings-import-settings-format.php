<h3><?php echo __( 'Import Format', 'houzezpropertyfeed' ); ?></h3>

<p>Select the CRM or format that you want to import using below:</p>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="format"><?php echo __( 'Choose Format', 'houzezpropertyfeed' ); ?></label></th>
			<td>
				<select name="format" id="format">
					<option value=""></option>
					<?php
						foreach ( $formats as $key => $format )
						{
							echo '<option value="' . $key . '"';
							echo ( ( isset($import_settings['format']) && $import_settings['format'] == $key ) ? ' selected' : '' );
							echo '>' . esc_html($format['name']) . '</option>';
						}
					?>
				</select>
			</td>
		</tr>
	</tbody>
</table>

<?php
	foreach ( $formats as $key => $format )
	{
?>

<div id="import_settings_<?php echo esc_attr($key); ?>" class="import-settings-format" style="display:none">

<h3><?php echo esc_html($format['name']) . ' ' . __( 'Settings', 'houzezpropertyfeed' ); ?></h3>

<table class="form-table">
	<tbody>
		<?php
			foreach ( $format['fields'] as $field )
			{
		?>
			<tr>
				<th><?php echo esc_html($field['label']); ?></th>
				<td><?php
					switch ($field['type'])
					{
						case "text":
						case "number":
						{
							echo '<input 
								type="' . esc_attr($field['type']) . '" 
								name="' . esc_attr($key . '_' . $field['id']) . '" 
								value="' . ( ( isset($import_settings[$field['id']]) ) ? esc_attr($import_settings[$field['id']]) : ( isset($field['default']) ? esc_attr($field['default']) : '' ) ) . '" 
								placeholder="' . ( isset($field['placeholder']) ? esc_attr($field['placeholder']) : '' ) . '">';
							break;
						}
					}
				?></td>
			</tr>
		<?php
			}
		?>
	</tbody>
</table>

<?php
	if ( isset($format['warning']) && !empty($format['warning']) )
	{
		echo '<div class="notice notice-error inline"><p>' . esc_html($format['warning']) . '</p></div>';
	}
?>

<?php
	if ( isset($format['help_url']) && !empty($format['help_url']) )
	{
		echo '<p style="color:#999"><span class="dashicons dashicons-editor-help"></span> <strong>Need help?</strong> Read our documentation for instructions on <a href="' . esc_attr($format['help_url']) . '" target="_blank">setting up an import from ' . esc_html($format['name']) . '</a></p>';
	}
?>

</div>

<?php
	}
?>