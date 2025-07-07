<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<h3><?php echo esc_html(__( 'Import', 'houzezpropertyfeed' )); ?></h3>

<p><?php echo esc_html(__(' Here you can import Houzez Property Feed imports that have been previously exported using this same tool.', 'houzezpropertyfeed' )); ?></p>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="import_file"><?php echo esc_html(__( 'Import file', 'houzezpropertyfeed' )); ?></label></th>
			<td style="padding-top:20px;">

				<input type="file" name="import_file" id="import_file">
				<br>
				<small><em>Accepted file types: json</em></small>
				<br><br>
				<a href="#" id="hpf-import-import" class="button button-primary">Import</a>

			</td>
		</tr>
	</tbody>
</table>

<h3><?php echo esc_html(__( 'Export', 'houzezpropertyfeed' )); ?></h3>

<p><?php echo esc_html(__(' Here you can export Houzez Property Feed imports as a backup or to migrate to another Houzez Property Feed installation.', 'houzezpropertyfeed' )); ?></p>

<table class="form-table">
	<tbody>
		<tr>
			<th><label for="export_import_id"><?php echo esc_html(__( 'Property Import to Export', 'houzezpropertyfeed' )); ?></label></th>
			<td style="padding-top:20px;">

				<?php
					$options = get_option( 'houzez_property_feed', array() );
					$imports = ( isset($options['imports']) && is_array($options['imports']) && !empty($options['imports']) ) ? $options['imports'] : array();

					// Remove deleted imports
					foreach ( $imports as $key => $import ) {
					    if ( isset($import['deleted']) && $import['deleted'] === true ) {
					        unset( $imports[$key] );
					    }
					}

					if ( empty($imports) ) {
					    echo 'No imports found';
					} else {
					    // Build sortable array
					    $sortable = array();
					    foreach ( $imports as $import_id => $import ) {
					        $format = houzez_property_feed_get_import_format( $import['format'] );
					        $label = ( isset($format['name']) ? $format['name'] : '-' );
					        if ( isset($import['custom_name']) && !empty($import['custom_name']) ) {
					            $label .= ' (' . $import['custom_name'] . ')';
					        }
					        $sortable[$import_id] = $label;
					    }

					    // Sort alphabetically by label
					    asort($sortable, SORT_NATURAL | SORT_FLAG_CASE);

					    // Output dropdown
					    echo '<select name="export_import_id" id="export_import_id">';
					    echo '<option value=""></option>';
					    foreach ( $sortable as $import_id => $label ) {
					        echo '<option value="' . (int)$import_id . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
					    }
					    echo '</select>';
					}
				?>
				<br><br>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=houzez-property-feed-import&tab=settings'), 'export-import' ) ); ?>" id="hpf-export-import" class="button button-primary">Export</a>

			</td>
		</tr>
	</tbody>
</table>