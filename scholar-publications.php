<?php

/**
 * Plugin Name: Scholar Publications
 * Plugin URI: http://wordpress.org/extend/plugins/scholar-publications/
 * Description: I'm your publication management plugin for WordPress. Manages publications and exposes metadata in order to be indexed by Scholar.
 * Author: Csaba Peter
 * Version: 1.0.0
 * Author URI: http://2pmc.net
 * Text Domain: scholar-publications-by-2pmc
 *
 * @package WordPress
 * @subpackage Scholar_Publications
 * @author Csaba Peter
 * @since 1.0.0
 */

require_once('classes/class-scholar-publications.php');
require_once('classes/class-scholar-publications-taxonomy.php');
global $scholar_publications;
$scholar_publications = new Scholar_Publications( __FILE__ );
$scholar_publications->version = '1.0.0';


?>