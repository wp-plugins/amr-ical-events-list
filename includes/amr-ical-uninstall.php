<?php
/* This is the amr ical uninstall file */
	function amr_ical_uninstall(){	
	if (function_exists ('delete_option')) {  			
		if ( $del1 = delete_option('amr-ical-events-list')) {
			echo '<p>'.__('AmR iCal Options deleted from Database', 'amr-ical-events-list').'</p>';
		};
		if (($del2 = delete_option("amricalWidget")) or ($del2 = delete_option("amr-ical-widget"))){
			echo '<p>'.__('AmR iCal Widget Options deleted from Database', 'amr-ical-events-list').'</p>';
		}	
		return ($del1 and $del2);	 
	}
	else {
		echo '<p>Wordpress Function delete_option does not exist.</p>';
		return (false);	
		}
					
	}
/* -------------------------------------------------------------------------------------------------------------*/
	
	function amr_ical_check_uninstall()
	{	
		?><div class="wrap" id="amrical"> 
		<h2><?php _e('Uninstall AmR iCal Events List Options', 'amr-ical-events-list'); ?></h2>
		<p><?php _e('Note this function removes the options from the database.  To completely uninstall, one should continue on to use the standard wordpress functions to deactivate the plugin and delete the files.  It is not necessary to run this separately as the uninstall will also run as part of the wordpress delete plug-in files.', 'amr-ical-events-list');?></p>
		<p><?php _e('The function is provided here as an aid to someone who has perhaps got their wordpress install in a knot and wishes to temporarily remove the options from the database as part of their debugging or cleanup.  Consider also the use of the RESET.', 'amr-ical-events-list');?></p>
		<?php
		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce, 'amr-ical-events-list')) die ("Cancelled due to failed security check");

		if (isset ($_POST['reallyuninstall']))  { 
				amr_ical_uninstall();
					echo '<p>'
					.__('Note: Navigating to "Manage AmR ICal Settings" will RELOAD default options - negating the uninstall.', 'amr-ical-events-list')
					.'</p>';
					echo '<a href="'.'../wp-admin/plugins.php">'.__('Continue to Plugin list to delete files as well','amr-ical-events-list').'</a>'; 
				}
		
		if (isset ($_POST['uninstall'])) {
			$nonce = wp_create_nonce('amr-ical-events-list'); /* used for security to verify that any action request comes from this plugin's forms */
			?><form method="post" action="<?php  ?>">
			<?php  wp_nonce_field('amr-ical-events-list'); /* outputs hidden field */
			?><fieldset id="submit">
					<input type="hidden" name="action" value="uninstalloptions" />
					<input type="submit" name="cancel" value="<?php _e('Cancel', 'amr-ical-events-list') ?>" />
					<input type="submit" name="reallyuninstall" value="<?php _e('Really Uninstall Options?', 'amr-ical-events-list') ?>" />		
				</fieldset>
			</form>
			</div><?php 
		}
	}
	register_uninstall_hook(__FILE__,'amr_ical_uninstall');
?>