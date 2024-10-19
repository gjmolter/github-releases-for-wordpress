<?php
/*
Plugin Name: GitHub Releases for WordPress
Plugin URI:  https://gabrielmolter.com/wordpress/github-releases-for-wordpress/
Description: Theme and Plugin version management via GitHub repo releases. Updates appear in the WordPress dashboard when a new release is published on GitHub. Requires a GitHub Access Token for private repositories (also recommended for public ones).
Version:     0.9.0
Requires PHP: 7.2
Requires at least: 5.6
Author:      Gabriel Molter
Author URI:  https://gabrielmolter.com/
Update URI:  gjmolter/github-releases-for-wordpress
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

global $gm_github_update_errors;
$gm_github_update_errors = array();

// PLUGIN FUNCTIONALITY
require_once plugin_dir_path(__FILE__) . 'helpers/github-api.php';
require_once plugin_dir_path(__FILE__) . 'helpers/update-plugins.php';
require_once plugin_dir_path(__FILE__) . 'helpers/update-themes.php';
require_once plugin_dir_path(__FILE__) . 'helpers/post-update.php';
require_once plugin_dir_path(__FILE__) . 'helpers/notices.php';
require_once plugin_dir_path(__FILE__) . 'helpers/force-update.php';

// PLUGIN OPTION PAGES
require_once plugin_dir_path(__FILE__) . 'option-pages/settings.php';
