<?php
/*
Plugin Name: GitHub Releases for WordPress
Plugin URI:  https://gabrielmolter.com/wordpress/github-releases-for-wordpress/
Description: Theme and Plugin version management via GitHub repo releases. Updates appear in the WordPress dashboard when a new release is published on GitHub. Requires a GitHub Access Token for private repositories (also recommended for public ones).
Version:     0.9.6
Requires PHP: 7.2
Requires at least: 5.6
Author:      Gabriel Molter
Author URI:  https://gabrielmolter.com/
Update URI:  gjmolter/github-releases-for-wordpress
*/

//TODO: Add logic so that if there's a specific release on the UpdateURI, after downloading the update, update the style.css or plugin file to add /{version_tag} to the end of the UpdateURI. That way the plugin won't keep checking for new updates once you set it to a specific release.

//TODO: Add a form to install themes/plugins. Form should contain a text input for the GitHub repo URL, a text input for the release (empty for latest), a text field for a specific GH token for that project and a submit button.

//TODO: Do a round of checks to guarantee the error when updating themes (github adds strings to the zip file) is fixed.

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

global $grfw_github_update_errors;
$grfw_github_update_errors = array();

// PLUGIN FUNCTIONALITY
require_once plugin_dir_path(__FILE__) . 'helpers/github-api.php';
require_once plugin_dir_path(__FILE__) . 'helpers/update-plugins.php';
require_once plugin_dir_path(__FILE__) . 'helpers/update-themes.php';
require_once plugin_dir_path(__FILE__) . 'helpers/post-update.php';
require_once plugin_dir_path(__FILE__) . 'helpers/notices.php';

// PLUGIN OPTION PAGES
require_once plugin_dir_path(__FILE__) . 'option-pages/settings.php';

// PLUGIN DEACTIVATION
register_deactivation_hook(__FILE__, 'grfw_deactivate');
function grfw_deactivate()
{
  delete_option('grfw_github_access_token');
}
