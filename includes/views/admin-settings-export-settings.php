<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<form method="POST" action="" enctype="multipart/form-data">

	<input type="hidden" name="export_id" value="<?php echo ( $export_id !== FALSE ? (int)$export_id : '' ); ?>">
	<input type="hidden" name="save_export_settings" value="yes">
	<?php wp_nonce_field( 'save-export-settings' ); ?>

	<div class="hpf-admin-settings-body wrap">

		<div class="hpf-admin-settings-import-settings">

			<h1><?php echo ( $export_id !== false ) ? esc_html(__( 'Edit Export', 'houzezpropertyfeed' )) : esc_html(__( 'Create Export', 'houzezpropertyfeed' )); ?></h1>

			<div class="settings-area">

				<div class="left-tabs">
					<ul>
						<li id="export_setting_tab_format" class="active"><a href="#format"><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html(__( 'Export Format', 'houzezpropertyfeed' )); ?></a></li>
						<li id="export_setting_tab_frequency" style="display:none"><a href="#frequency"><span class="dashicons dashicons-clock"></span> <?php echo esc_html(__( 'Frequency', 'houzezpropertyfeed' )); ?></a></li>
						<li id="export_setting_tab_taxonomies"><a href="#taxonomies"><span class="dashicons dashicons-tag"></span> <?php echo esc_html(__( 'Taxonomies', 'houzezpropertyfeed' )); ?></a></li>
						<li id="export_setting_tab_fieldmapping"><a href="#fieldmapping"><span class="dashicons dashicons-admin-links"></span></span> <?php echo esc_html(__( 'Field Mapping', 'houzezpropertyfeed' )); ?><span id="field_mapping_warning" style="color:#999; display:none">&nbsp;&nbsp;<span class="dashicons dashicons-warning"></span></span></a></li>
					</ul>
				</div>

				<div class="right-settings">

					<div class="buttons">

						<div class="running-status-toggle">
							
							Export Active

							<label class="hpf-switch">
							  	<input type="checkbox" name="running" value="yes"<?php if ( isset($export_settings['running']) && $export_settings['running'] === true ) { echo ' checked'; } ?>>
							  	<span class="hpf-slider"></span>
							</label>

						</div>

						<input type="submit" value="<?php echo esc_attr(__( 'Save changes', 'houzezpropertyfeed' )); ?>" class="button button-primary">&nbsp;
						<a href="<?php echo esc_url(admin_url('admin.php?page=houzez-property-feed-export')); ?>" class="button">Cancel</a>

					</div>

					<div class="settings-panels">

						<div class="settings-panel" id="format">
							<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/admin-settings-export-settings-format.php' ); ?>
						</div>

						<div class="settings-panel" id="frequency" style="display:none">
							<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/admin-settings-export-settings-frequency.php' ); ?>
						</div>

						<div class="settings-panel" id="taxonomies" style="display:none">
							<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/admin-settings-export-settings-taxonomies.php' ); ?>
						</div>

						<div class="settings-panel" id="fieldmapping" style="display:none">
							<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/admin-settings-export-settings-field-mapping.php' ); ?>
						</div>

					</div>

					<div class="buttons bottom">

						<input type="submit" value="<?php echo esc_attr(__( 'Save changes', 'houzezpropertyfeed' )); ?>" class="button button-primary">&nbsp;
						<a href="<?php echo esc_url(admin_url('admin.php?page=houzez-property-feed-export')); ?>" class="button">Cancel</a>

					</div>

				</div>

			</div>

		</div>

	</div>

</form>