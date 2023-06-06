<div class="hpf-admin-settings-header">

	<div class="add-import-button">
		<a href="<?php echo admin_url('admin.php?page=houzez-property-feed&action=addimport'); ?>" class="button button-primary button-hero"><span class="dashicons dashicons-plus-alt2"></span> <?php echo __( 'Create New Import', 'houzezpropertyfeed' ); ?></a>
	</div>

	<div class="logo">
		<a href="<?php echo admin_url('admin.php?page=houzez-property-feed'); ?>"><img src="<?php echo untrailingslashit( plugins_url( '/', HOUZEZ_PROPERTY_FEED_PLUGIN_FILE ) ); ?>/assets/images/houzez-property-feed-logo.png" alt=""></a>
	</div>

	<div class="buttons">
		<?php if ( !class_exists('Houzez_Property_Feed_Pro') ) { ?><a href="https://houzezpropertyfeed.com/#pricing" class="button button-primary" target="_blank"><?php echo __( 'Upgrade To PRO', 'houzezpropertyfeed' ); ?></a> &nbsp;<?php } ?>
		<a href="https://houzezpropertyfeed.com/documentation/" class="button" target="_blank"><?php echo __( 'Documentation', 'houzezpropertyfeed' ); ?></a>
	</div>

	<div class="clear"></div>

</div>
