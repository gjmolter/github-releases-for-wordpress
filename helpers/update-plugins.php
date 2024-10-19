<?php
function gm_plugin_update($transient)
{
  // Initialize the global errors array (will be picked up by the notices.php file)
  global $gm_github_update_errors;

  // Get all the plugins, so we can check if they have a valid UpdateURI
  $plugins = get_plugins();

  foreach ($plugins as $plugin_slug => $plugin) {
    $updateURI = isset($plugin['UpdateURI']);

    if (!empty($updateURI)) {
      $parts = explode('/', $updateURI);
      // Check if the update URI is in the correct format (user/repo or user/repo/release)
      if (count($parts) == 2 || count($parts) == 3) {
        $user = $parts[0];
        $repo = $parts[1];
        $release = isset($parts[2]) ? $parts[2] : '';
        $current_version = $plugin['Version'];

        // If the release is empty, get the latest release
        if ($release == '') $release = 'latest';
        else $release = 'tags/' . $release;

        // Get the release information from the GitHub API
        $url = "https://api.github.com/repos/{$user}/{$repo}/releases/{$release}";
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
          $gm_github_update_errors[] = 'GitHub API error: ' . $response->get_error_message();
          continue;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // If no tag_name is found in the release data, skip this theme
        if (!isset($data['tag_name'])) {
          $gm_github_update_errors[] = 'GitHub API error: No tag_name found in release data for ' . $user . '/' . $repo;
          continue;
        }

        // Theme's zipball download URL
        $download_url = "https://api.github.com/repos/{$user}/{$repo}/zipball/{$data['tag_name']}";

        // If you add more assets to the GH release, they will be added to this array.
        // Then, they'll be downloaded to the plugin's folder on the after-update hook and unzipped. (only supports zip files)
        $extra_assets = array();
        if (isset($data['assets']) && is_array($data['assets'])) {
          foreach ($data['assets'] as $asset) {
            if (isset($asset['url'])) {
              $extra_assets[] = $asset['url'];
            }
          }
        }
        // Save the extra asset URLs to a transient, so we can use them on the after-update hook
        set_transient('gm_github_extra_assets_plugin_' . $plugin_slug, $extra_assets, HOUR_IN_SECONDS * 24 * 365);

        // Get the version number from the tag name (remove the 'v' from the beginning, if it exists)
        $version = preg_replace('/^v/', '', $data['tag_name']);

        $remote_info = array(
          'version'      => $version,
          'download_url' => $download_url,
        );

        // If the theme is outdated, add the update info to the transient
        if ($remote_info && version_compare($current_version, $remote_info['version'], '<')) {
          // If there's no transient, create an empty one
          if (!$transient) $transient = new stdClass();
          // Add our update info to the transient so WP can show the update notification.
          $transient->response[$plugin_slug] = (object) array(
            'slug'        => dirname($plugin_slug),
            'plugin'      => $plugin_slug,
            'new_version' => $remote_info['version'],
            'url'         => $plugin['PluginURI'],
            'package'     => $remote_info['download_url'],
          );
        } elseif (!$remote_info) {
          $gm_github_update_errors[] = 'Could not retrieve update info for plugin: ' . $plugin['Name'];
        }
      } else {
        // Invalid UpdateURI format. 
        // Most likely a plugin that's not compatible with this plugin.
      }
    }
  }
  return $transient;
}

// Hook into the WP regular plugin update check
add_filter('pre_set_site_transient_update_plugins', 'gm_plugin_update');
