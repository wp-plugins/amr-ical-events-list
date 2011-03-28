<?php
/**
 * Uninstall functionality for amr iCal Events List plugin.
 * 
 * Removes the plugin cleanly in WP 2.7 and up
 */
require_once('amr-ical-uninstall.php');

// first, check to make sure that we are indeed uninstalling
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

// delete the option that the plugin added
amr_ical_uninstall();

