<?php

// When downloading from GitHub, the contents of the ZIP file are extrated to a folder with name:
// [username]-[repo]-[extra_string], and we would want it to be just [repo].
// Therefore, this function is here to rename the extracted folder into the expected folder name (and remove the leftover).
function grfw_rename_extracted_folder($response, $hook_extra, $result)
{
  //Initialize the global errors array (will be picked up by the notices.php file)
  global $grfw_github_update_errors;

  // Ensure the WordPress filesystem is available.
  if (! function_exists('WP_Filesystem')) require_once ABSPATH . 'wp-admin/includes/file.php';
  WP_Filesystem();
  global $wp_filesystem;

  // Check weather we're dealing with a theme or a plugin.
  if (isset($hook_extra['theme'])) {
    $theme_slug = $hook_extra['theme'];
    // This will return something like: array('[theme-name]', 'user-[theme-name]-string', ...other_files_in_themes_folder) 
    $themes_folder = $wp_filesystem->dirlist(dirname($result['destination']));

    // The folder that was just extracted from the GH zipball will have the theme slug in its name, but it will not be the theme slug exactly.
    $extracted_folder = array();
    foreach ($themes_folder as $folder) {
      if (strpos($folder['name'], $theme_slug) !== false) {
        //if the folder name matches the theme slug exactly, it is not the extracted folder.
        if ($folder['name'] !== $theme_slug) {
          $extracted_folder[] = $folder['name'];
        }
      }
    }

    // There may be more files in the themes folder that include the theme slug in their name, so we need to
    // make sure we're dealing with the extracted folder only.
    // Because this hook is called right after the folder is extracted, the extracted folder will be the most recent one.
    if (count($extracted_folder) !== 1) {
      // more than one, use the most recent
      $most_recent_folder = '';
      $most_recent_folder_time = 0;
      foreach ($extracted_folder as $folder) {
        $folder_time = filemtime($result['destination'] . '/' . $folder);
        if ($folder_time > $most_recent_folder_time) {
          $most_recent_folder = $folder;
          $most_recent_folder_time = $folder_time;
        }
      }
      $extracted_folder = $most_recent_folder;
    } else {
      // only one, sure it's the extracted folder
      $extracted_folder = $extracted_folder[0];
    }

    // Define the correct destination path for the theme.
    $theme_directory = get_theme_root() . '/' . $theme_slug;

    // Check if the expected theme directory already exists, if so, remove it.
    if ($wp_filesystem->is_dir($theme_directory)) {
      $wp_filesystem->delete($theme_directory, true);
    }

    // Rename the extracted folder to the expected theme folder name.
    $renamed = $wp_filesystem->move(dirname($result['destination']) . '/' . $extracted_folder, $theme_directory);
    if (!$renamed) {
      $grfw_github_update_errors[] = 'Failed to rename the extracted theme folder.';
      return;
    }

    // Update the installation result to point to the correct theme directory.
    if (is_object($result)) {
      $result->destination = $theme_directory;
    } elseif (is_array($result)) {
      $result['destination'] = $theme_directory;
    }
  } elseif (isset($hook_extra['plugin'])) {
    $plugin_slug = $hook_extra['plugin'];
    $plugin_basename = dirname($plugin_slug);

    // This will return something like: array('[plugin-name]', 'user-[plugin-name]-string', ...other_files_in_plugins_folder)
    $plugins_folder = $wp_filesystem->dirlist(dirname($result['destination']));
    $extracted_folder = array();
    foreach ($plugins_folder as $folder) {
      if (strpos($folder['name'], $plugin_basename) !== false) {
        if ($folder['name'] !== $plugin_basename) {
          $extracted_folder[] = $folder['name'];
        }
      }
    }

    // There may be more files in the plugins folder that include the plugin basename in their name, so we need to
    // make sure we're dealing with the extracted folder only.
    // Because this hook is called right after the folder is extracted, the extracted folder will be the most recent one.
    if (count($extracted_folder) !== 1) {
      // more than one, keep the most recent
      $most_recent_folder = '';
      $most_recent_folder_time = 0;
      foreach ($extracted_folder as $folder) {
        $folder_time = filemtime($result['destination'] . '/' . $folder);
        if ($folder_time > $most_recent_folder_time) {
          $most_recent_folder = $folder;
          $most_recent_folder_time = $folder_time;
        }
      }
      $extracted_folder = $most_recent_folder;
    } else {
      // only one, sure it's the extracted folder
      $extracted_folder = $extracted_folder[0];
    }

    // Define the correct destination path for the plugin.
    $plugin_directory = WP_PLUGIN_DIR . '/' . $plugin_basename;

    // Check if the expected plugin directory already exists, if so, remove it.
    if ($wp_filesystem->is_dir($plugin_directory)) {
      $wp_filesystem->delete($plugin_directory, true);
    }

    // Rename the extracted folder to the expected plugin folder name.
    $renamed = $wp_filesystem->move(dirname($result['destination']) . '/' . $extracted_folder, $plugin_directory);
    if (! $renamed) {
      $grfw_github_update_errors[] = 'Failed to rename the extracted plugin folder.';
      return;
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

// Once regular updates are complete, check for extra assets to download. 
// (If there's any, they have been stored in a transient during the GitHub API check.)
function handle_extra_assets_after_update()
{
  // Check themes
  $themes = wp_get_themes();
  foreach ($themes as $theme_slug => $theme) {
    $extra_assets = get_transient('grfw_github_extra_assets_theme_' . $theme_slug);

    if ($extra_assets) {
      $theme_directory = get_theme_root() . '/' . $theme_slug;
      download_and_save_extra_assets($extra_assets, $theme_directory, $theme_slug);
      delete_transient('grfw_github_extra_assets_theme_' . $theme_slug);
    }
  }
  // Check plugins
  $plugins = get_plugins();
  foreach ($plugins as $plugin_slug => $plugin) {
    $extra_assets = get_transient('grfw_github_extra_assets_plugin_' . $plugin_slug);

    if ($extra_assets) {
      $plugin_directory = WP_PLUGIN_DIR . '/' . dirname($plugin_slug);
      download_and_save_extra_assets($extra_assets, $plugin_directory, $plugin_slug);
      delete_transient('grfw_github_extra_assets_plugin_' . $plugin_slug);
    }
  }
}

// Download and save extra assets to the destination directory (and extract ZIP files)
function download_and_save_extra_assets($assets, $destination_dir, $slug)
{
  // Ensure the destination directory exists, if not, try to create it
  if (!is_dir($destination_dir)) {
    if (!wp_mkdir_p($destination_dir)) {
      $grfw_github_update_errors[] = 'Failed to create destination directory: ' . $destination_dir;
      return;
    }
  }

  // Ensure the destination directory is writable
  if (!is_writable($destination_dir)) {
    $grfw_github_update_errors[] = 'Destination directory is not writable: ' . $destination_dir;
    return;
  }

  // Download, save and extract each asset
  foreach ($assets as $asset_url) {
    error_log("[$slug] Downloading extra asset: $asset_url");

    // Extract the asset ID from the URL (Ex: https://api.github.com/repos/[user]/[repo]/releases/assets/[asset_id])
    $asset_id = basename($asset_url);
    if (!is_numeric($asset_id)) {
      $grfw_github_update_errors[] = 'Invalid asset ID extracted from URL: ' . $asset_url;
      continue;
    }

    // Fetch the asset using the GitHub API
    $response = wp_remote_get($asset_url);

    if (is_wp_error($response)) {
      $grfw_github_update_errors[] = 'Failed to download asset ID ' . $asset_id . ': ' . $response->get_error_message();
      continue;
    }

    // Ensure the response code is 200
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $grfw_github_update_errors[] = 'Failed to download asset ID ' . $asset_id . ': HTTP ' . $response_code;
      continue;
    }

    // Retrieve the asset data
    $asset_data = wp_remote_retrieve_body($response);
    if (empty($asset_data)) {
      $grfw_github_update_errors[] = 'Failed to download asset ID ' . $asset_id . ': Empty response body';
      continue;
    }

    // Attempt to get the filename from the headers, otherwise fallback to using the asset ID (always a ZIP file)
    $filename = basename(wp_remote_retrieve_header($response, 'content-disposition'));
    if (empty($filename)) $filename = $asset_id . '.zip';

    // Define the full path to save the asset
    $file_path = trailingslashit($destination_dir) . $filename;

    // Save the asset data to the file
    $saved = file_put_contents($file_path, $asset_data);
    if ($saved === false) {
      error_log("[$slug] Failed to save asset ID $asset_id to $file_path");
      continue;
    }

    error_log("[$slug] Successfully saved asset to $file_path");

    // If the asset isn't a ZIP file, skip the extraction
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
      error_log("[$slug] Skipping extraction of non-ZIP asset: $filename");
      continue;
    }

    // Initialize the ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($file_path) === true) {
      // Extract the contents to the extraction path
      if ($zip->extractTo(trailingslashit($destination_dir))) {
        error_log("[$slug] Successfully extracted $filename");
      } else {
        $grfw_github_update_errors[] = 'Failed to extract asset ID ' . $asset_id . ' to ' . $destination_dir;
      }
      $zip->close();
    } else {
      $grfw_github_update_errors[] = 'Failed to open ZIP archive for asset ID ' . $asset_id;
    }

    unlink($file_path);
  }
}

// Hook into the post-installation process to rename the extracted folder and handle extra assets
add_filter('upgrader_post_install', 'grfw_rename_extracted_folder', 10, 3);
add_action('upgrader_process_complete', 'handle_extra_assets_after_update', 10, 2);
