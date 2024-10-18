<?php
/*
Plugin Name: WordPress GitHub Updates (BA)
Plugin URI:  https://github.com/ballistic-arts/ba-wordpress-github-updates-plugin
Description: A simple WP plugin to allow for automatic updates from a GitHub repository.
Version:     0.6.2.3
Author:      Gabriel Molter @ Ballistic Arts
Author URI:  https://ballisticarts.com
Update URI:  gjmolter/ba-wordpress-github-updates-plugin/main
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

global $github_update_errors;
$github_update_errors = array();

// UPDATE THEMES
function ba_theme_update($transient)
{
  global $github_update_errors;
  $themes = wp_get_themes();
  $access_token = ba_get_github_access_token();

  if (empty($access_token)) {
    $github_update_errors[] = 'GitHub access token is not defined.';
    return $transient;
  }

  foreach ($themes as $theme_slug => $theme) {
    $updateURI = $theme->get('UpdateURI');

    if (!empty($updateURI)) {
      $parts = explode('/', $updateURI);
      if (count($parts) >= 3) {
        $user = $parts[0];
        $repo = $parts[1];
        $branch = $parts[2]; // Currently not used but available if needed
        $current_version = $theme->get('Version');

        $url = "https://api.github.com/repos/{$user}/{$repo}/releases/latest";
        $args = array(
          'headers' => array(
            'Authorization' => 'token ' . $access_token,
            'User-Agent'    => 'WordPress GitHub Updater',
            'Accept'        => 'application/vnd.github.v3+json',
          ),
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
          $github_update_errors[] = 'GitHub API error: ' . $response->get_error_message();
          return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['tag_name'])) {
          $github_update_errors[] = 'GitHub API error: No tag_name found in release data for ' . $user . '/' . $repo;
          return false;
        }

        $download_url = "https://api.github.com/repos/{$user}/{$repo}/zipball/{$data['tag_name']}";

        $version = preg_replace('/^v/', '', $data['tag_name']);

        $remote_info = array(
          'version'      => $version,
          'download_url' => $download_url,
        );

        if ($remote_info && version_compare($current_version, $remote_info['version'], '<')) {
          if (!$transient) {
            $transient = new stdClass();
          }
          $transient->response[$theme->get_stylesheet()] = array(
            'theme'       => $theme->get_stylesheet(),
            'new_version' => $remote_info['version'],
            'url'         => $theme->get('ThemeURI'),
            'package'     => $remote_info['download_url'],
          );
        } elseif (!$remote_info) {
          $github_update_errors[] = 'Could not retrieve update info for theme: ' . $theme->get('Name');
        }
      } else {
        $github_update_errors[] = 'Invalid Update URI format for theme: ' . $theme->get('Name');
      }
    }
  }
  return $transient;
}

// UPDATE PLUGINS
function ba_plugin_update($transient)
{
  global $github_update_errors;
  $plugins = get_plugins();
  $access_token = ba_get_github_access_token();

  if (empty($access_token)) {
    $github_update_errors[] = 'GitHub access token is not defined.';
    return $transient;
  }

  foreach ($plugins as $plugin_slug => $plugin) {
    $updateURI = isset($plugin['UpdateURI']) ? $plugin['UpdateURI'] : '';

    if (!empty($updateURI)) {
      $parts = explode('/', $updateURI);
      //there should be between 2 and 3 parts user, repo and (optional) branch
      if (count($parts) == 2 || count($parts) == 3) {
        $user = $parts[0];
        $repo = $parts[1];
        $branch = $parts[2]; // Currently not used but available if needed
        $current_version = $plugin['Version'];

        $github_update_errors[] = 'Plugin: ' . $plugin['Name'] . ' ' . $plugin['Version'];

        $github_update_errors[] = 'Checking for updates for ' . $user . '/' . $repo;
        $url = "https://api.github.com/repos/{$user}/{$repo}/releases/latest";
        $args = array(
          'headers' => array(
            'Authorization' => 'token ' . $access_token,
            'User-Agent'    => 'WordPress GitHub Updater',
            'Accept'        => 'application/vnd.github.v3+json',
          ),
        );

        $github_update_errors[] = 'Request URL: ' . $url;

        $response = wp_remote_get($url, $args);

        $github_update_errors[] = 'Response: ' . print_r($response, true);

        if (is_wp_error($response)) {
          error_log('GitHub API error: ' . $response->get_error_message());
          return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['tag_name']) || empty($data['assets'])) {
          error_log('GitHub API error: No tag_name or assets found in release data for ' . $user . '/' . $repo);
          return false;
        }

        // Find the ZIP asset
        $download_url = '';
        foreach ($data['assets'] as $asset) {
          if (strpos($asset['name'], '.zip') !== false) {
            $download_url = $asset['browser_download_url'];
            break;
          }
        }

        if (empty($download_url)) {
          error_log('No ZIP asset found in release for ' . $user . '/' . $repo);
          return false;
        }

        $version = preg_replace('/^v/', '', $data['tag_name']);

        $remote_info = array(
          'version'      => $version,
          'download_url' => $download_url,
        );


        if ($remote_info && version_compare($current_version, $remote_info['version'], '<')) {
          if (!isset($transient->response)) {
            $transient->response = array();
          }
          $transient->response[$plugin_slug] = (object) array(
            'slug'        => dirname($plugin_slug),
            'plugin'      => $plugin_slug,
            'new_version' => $remote_info['version'],
            'url'         => $plugin['PluginURI'],
            'package'     => $remote_info['download_url'],
          );
        } elseif (!$remote_info) {
          $github_update_errors[] = 'Could not retrieve update info for plugin: ' . $plugin['Name'];
        }
      } else {
        //invalid update uri format. most likely not a github plugin only uncomment when needed
        //$github_update_errors[] = 'Invalid Update URI format for plugin: ' . $plugin['Name'];
      }
    }
  }
  return $transient;
}

add_filter('pre_set_site_transient_update_themes', 'ba_theme_update');
add_filter('pre_set_site_transient_update_plugins', 'ba_plugin_update');


// FORCE UPDATES WITH ?force-git-update QUERY PARAMETER
function ba_force_check_updates()
{
  if (isset($_GET['force-git-update'])) {
    global $github_update_errors;
    $github_update_errors = array();
    set_site_transient('update_themes', null);
    set_site_transient('update_plugins', null);
    do_action('wp_update_themes');
    do_action('wp_update_plugins');
  }
}
add_action('admin_init', 'ba_force_check_updates');

// ADD HTTP REQUEST ARGUMENTS TO INCLUDE THE ACCESS TOKEN WHEN DOWNLOADING PACKAGES.
function ba_http_request_args($args, $url)
{
  if (strpos($url, 'api.github.com/repos') !== false || strpos($url, 'github.com') !== false) {
    $access_token = ba_get_github_access_token();
    if (!empty($access_token)) {
      $args['headers']['Authorization'] = 'token ' . $access_token;
      $args['headers']['Accept'] = 'application/vnd.github.v3.raw';
    }
  }
  return $args;
}
add_filter('http_request_args', 'ba_http_request_args', 10, 2);

// DISPLAY UPDATE ERRORS IN THE ADMIN DASHBOARD
function ba_display_update_errors()
{
  global $github_update_errors;
  $github_update_errors = array_unique($github_update_errors);
  if (!empty($github_update_errors)) {
    echo '<div class="notice notice-error is-dismissible">';
    foreach ($github_update_errors as $error) {
      echo '<p>' . esc_html($error) . '</p>';
    }
    echo '</div>';
  }
}
add_action('admin_notices', 'ba_display_update_errors');

// Rename the extracted theme or plugin folder after installation.
function ba_rename_extracted_folder($response, $hook_extra, $result)
{
  if (! function_exists('WP_Filesystem')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }

  WP_Filesystem();
  global $wp_filesystem;

  if (isset($hook_extra['theme'])) {

    $theme_slug = $hook_extra['theme']; // e.g., 'ba-wordpress-base-theme'
    $expected_folder_name = $theme_slug; // The folder name WordPress expects.

    // Access 'destination' based on the type of $result
    if (is_object($result) && isset($result->destination)) {
      $temp_dir = trailingslashit($result->destination);
    } elseif (is_array($result) && isset($result['destination'])) {
      $temp_dir = trailingslashit($result['destination']);
    } else {
      return new WP_Error('invalid_result', __('Invalid result structure.', 'ba-github-updater'));
    }

    // Get the list of folders in the temporary directory.
    $extracted_folders = $wp_filesystem->dirlist($temp_dir);

    if (empty($extracted_folders)) {
      return new WP_Error('no_extracted_folder', __('No folder found after extracting the ZIP package.', 'ba-github-updater'));
    }

    // Assume the first folder is the extracted one.
    $extracted_folder = trailingslashit($temp_dir . $extracted_folders[0]['name']);

    // Define the correct destination path for the theme.
    $theme_directory = get_theme_root() . '/' . $expected_folder_name;

    // Check if the expected theme directory already exists.
    if ($wp_filesystem->is_dir($theme_directory)) {
      // Remove the existing theme directory before renaming.
      $wp_filesystem->delete($theme_directory, true); // Recursive delete.
    }

    // Rename the extracted folder to the expected theme folder name.
    $renamed = $wp_filesystem->move($extracted_folder, $theme_directory);

    if (! $renamed) {
      return new WP_Error('rename_failed', __('Failed to rename the extracted theme folder.', 'ba-github-updater'));
    }

    // Update the installation result to point to the correct theme directory.
    if (is_object($result)) {
      $result->destination = $theme_directory;
    } elseif (is_array($result)) {
      $result['destination'] = $theme_directory;
    }
  } elseif (isset($hook_extra['plugin'])) {
    // **Handling Plugin Installation/Update**

    $plugin_slug = $hook_extra['plugin']; // e.g., 'ba-wordpress-base-plugin/ba-wordpress-base-plugin.php'
    $plugin_basename = dirname($plugin_slug); // e.g., 'ba-wordpress-base-plugin'
    $expected_folder_name = $plugin_basename; // The folder name WordPress expects.

    // Access 'destination' based on the type of $result
    if (is_object($result) && isset($result->destination)) {
      $temp_dir = trailingslashit($result->destination);
    } elseif (is_array($result) && isset($result['destination'])) {
      $temp_dir = trailingslashit($result['destination']);
    } else {
      return new WP_Error('invalid_result', __('Invalid result structure.', 'ba-github-updater'));
    }

    // Get the list of folders in the temporary directory.
    $extracted_folders = $wp_filesystem->dirlist($temp_dir);

    if (empty($extracted_folders)) {
      return new WP_Error('no_extracted_folder', __('No folder found after extracting the ZIP package.', 'ba-github-updater'));
    }

    // Assume the first folder is the extracted one.
    $extracted_folder = trailingslashit($temp_dir . $extracted_folders[0]['name']);

    // Define the correct destination path for the plugin.
    $plugin_directory = WP_PLUGIN_DIR . '/' . $expected_folder_name;

    // Check if the expected plugin directory already exists.
    if ($wp_filesystem->is_dir($plugin_directory)) {
      // Remove the existing plugin directory before renaming.
      $wp_filesystem->delete($plugin_directory, true); // Recursive delete.
    }

    // Rename the extracted folder to the expected plugin folder name.
    $renamed = $wp_filesystem->move($extracted_folder, $plugin_directory);

    if (! $renamed) {
      return new WP_Error('rename_failed', __('Failed to rename the extracted plugin folder.', 'ba-github-updater'));
    }

    // Update the installation result to point to the correct plugin directory.
    if (is_object($result)) {
      $result->destination = $plugin_directory;
    } elseif (is_array($result)) {
      $result['destination'] = $plugin_directory;
    }
  }

  return $response;
}
add_filter('upgrader_post_install', 'ba_rename_extracted_folder', 10, 3);

require_once './settings.php';
