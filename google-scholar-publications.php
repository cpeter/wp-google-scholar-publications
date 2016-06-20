<?php

/**
 * Plugin Name: Google Scholar Publications
 * Plugin URI: http://wordpress.org/extend/plugins/google-scholar-publications/
 * Description: I'm your publication management plugin for WordPress. Manages publications and exposes metadata in order to be indexed by Google Scholar.
 * Author: Csaba Peter
 * Version: 1.0.0
 * Author URI: http://2pmc.net
 * Text Domain: google-scholar-publications-by-2pmc
 *
 * @package WordPress
 * @subpackage Google_Scholar_Publications
 * @author Csaba Peter
 * @since 1.0.0
 */

require_once('classes/class-google-scholar-publications.php');
require_once('classes/class-google-scholar-publications-taxonomy.php');
global $google_scholar_publications;
$google_scholar_publications = new Google_Scholar_Publications( __FILE__ );
$google_scholar_publications->version = '1.0.0';


?>