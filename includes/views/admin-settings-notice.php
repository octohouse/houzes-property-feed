<?php
	if ( isset($_GET['hpfsuccessmessage']) && !empty($_GET['hpfsuccessmessage']) )
	{
		echo '<div class="notice notice-success inline"><p>' . esc_html(sanitize_text_field($_GET['hpfsuccessmessage'])) . '</p></div>';
	}
	if ( isset($_GET['hpferrormessage']) && !empty($_GET['hpferrormessage']) )
	{
		echo '<div class="notice notice-error inline"><p>' . esc_html(sanitize_text_field($_GET['hpferrormessage'])) . '</p></div>';
	}
?>