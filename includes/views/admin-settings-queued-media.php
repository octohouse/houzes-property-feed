<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php 
	$extra_query_string = ( isset($_GET['paged']) ? '&paged=' . (int)$_GET['paged'] : '' );
	$extra_query_string .= ( isset($_GET['orderby']) ? '&orderby=' . sanitize_text_field($_GET['orderby']) : '' );
	$extra_query_string .= ( isset($_GET['order']) ? '&order=' . sanitize_text_field($_GET['order']) : '' );
?>

<div class="hpf-admin-settings-body wrap">

	<div class="hpf-admin-settings-logs">

		<?php include( dirname(HOUZEZ_PROPERTY_FEED_PLUGIN_FILE) . '/includes/views/admin-settings-notice.php' ); ?>

		<h1><?php echo esc_html(__( 'Queued Media', 'houzezpropertyfeed' )); ?></h1>

		<div class="log-buttons log-buttons-top">
			<a href="<?php echo esc_url(admin_url('admin.php?page=houzez-property-feed-import' ) . $extra_query_string ); ?>" class="button">Back</a>
		</div>

		<?php 
			echo '<div class="logs-table">';
				echo $queued_media_table->display(); 
			echo '</div>';
		?>

		<div class="log-buttons log-buttons-bottom">
			<a href="<?php echo esc_url(admin_url('admin.php?page=houzez-property-feed-import' ) . $extra_query_string ); ?>" class="button">Back</a>
		</div>

	</div>

</div>