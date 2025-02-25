<?php
function grfw_theme_update($transient)
{
  // Initialize the global errors array (will be picked up by the notices.php file)
  global $grfw_github_update_errors;

  // Get all the themes, so we can check if they have a valid UpdateURI
  $themes = wp_get_themes();

  foreach ($themes as $theme_slug => $theme) {
    $updateURI = $theme->get('UpdateURI');

    if (!empty($updateURI)) {
      $parts = explode('/', $updateURI);
      // Check if the update URI is in the correct format (user/repo or user/repo/release)
      if (count($parts) == 2 || count($parts) == 3) {
        $user = $parts[0];
        $repo = $parts[1];
        $release = isset($parts[2]) ? $parts[2] : '';
        $current_version = $theme['Version'];

        // If the release is empty, get the latest release
        if ($release == '') $release = 'latest';
        else $release = 'tags/' . $release;

        // Get the release information from the GitHub API
        $url = "https://api.github.com/repos/{$user}/{$repo}/releases/{$release}";
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
          $grfw_github_update_errors[] = 'GitHub API error: ' . $response->get_error_message();
          continue;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // If no tag_name is found in the release data, skip this theme
        if (!isset($data['tag_name'])) {
          $grfw_github_update_errors[] = 'GitHub API error: No tag_name found in release data for ' . $user . '/' . $repo;
          continue;
        }

        // Theme's zipball download URL
        $download_url = "https://api.github.com/repos/{$user}/{$repo}/zipball/{$data['tag_name']}";

        // If you add more assets to the GH release, they will be added to this array.
        // Then, they'll be downloaded to the theme's folder on the after-update hook and unzipped. (only supports zip files)
        $extra_assets = array();
        if (isset($data['assets']) && is_array($data['assets'])) {
          foreach ($data['assets'] as $asset) {
            if (isset($asset['url'])) {
              $extra_assets[] = $asset['url'];
            }
          }
        }


        // Get the version number from the tag name (remove the 'v' from the beginning, if it exists)
        $version = preg_replace('/^v/', '', $data['tag_name']);

        $remote_info = array(
          'version' => $version,
          'download_url' => $download_url,
        );

        if ($release != 'latest') {
          //if the current version is not the same as the release, add the update info to the transient
          if ($remote_info && $remote_info['version'] != $current_version) {
            // Save the extra asset URLs to a transient, so we can use them on the after-update hook
            set_transient('grfw_github_extra_assets_theme_' . $theme_slug, $extra_assets, HOUR_IN_SECONDS * 24 * 365);
            // If there's no transient, create an empty one

            //Check if this is the active theme
            $active_theme = wp_get_theme();
            if ($active_theme->get_stylesheet() == $theme->get_stylesheet()) {
              set_transient('grfw_udpated_active_theme', array(
                'slug' => $theme_slug,
                'stylesheet_path' => $theme->get_stylesheet()
              ), HOUR_IN_SECONDS * 24 * 365);
            }

            if (!$transient) $transient = new stdClass();
            // Add our update info to the transient so WP can show the update notification.
            $transient->response[$theme->get_stylesheet()] = array(
              'theme' => $theme->get_stylesheet(),
              'new_version' => $remote_info['version'],
              'url' => $theme->get('ThemeURI'),
              'package' => $remote_info['download_url'],
            );
          } elseif (!$remote_info) {
            $grfw_github_update_errors[] = 'Could not retrieve update info for theme: ' . $theme->get('Name');
            continue;
          } elseif ($remote_info['version'] == $current_version) {
            // If the theme is up to date, remove it from the transient
            if (isset($transient->response[$theme->get_stylesheet()])) {
              unset($transient->response[$theme->get_stylesheet()]);
            }
          }
        } else {
          // If the theme is outdated, add the update info to the transient
          if ($remote_info && version_compare($current_version, $remote_info['version'], '<')) {
            // Save the extra asset URLs to a transient, so we can use them on the after-update hook
            set_transient('grfw_github_extra_assets_theme_' . $theme_slug, $extra_assets, HOUR_IN_SECONDS * 24 * 365);

            //Check if this is the active theme
            $active_theme = wp_get_theme();
            if ($active_theme->get_stylesheet() == $theme->get_stylesheet()) {
              set_transient('grfw_udpated_active_theme', array(
                'slug' => $theme_slug,
                'stylesheet_path' => $theme->get_stylesheet()
              ), HOUR_IN_SECONDS * 24 * 365);
            }

            // If there's no transient, create an empty one
            if (!$transient) $transient = new stdClass();
            // Add our update info to the transient so WP can show the update notification.
            $transient->response[$theme->get_stylesheet()] = array(
              'theme' => $theme->get_stylesheet(),
              'new_version' => $remote_info['version'],
              'url' => $theme->get('ThemeURI'),
              'package' => $remote_info['download_url'],
            );
          } elseif (!$remote_info) {
            $grfw_github_update_errors[] = 'Could not retrieve update info for theme: ' . $theme->get('Name');
            continue;
          }
        }
      } else {
        // Invalid UpdateURI format. 
        // Most likely a theme that's not compatible with this plugin.
      }
    }
  }
  return $transient;
}

// Hook into the WP regular theme update check
add_filter('pre_set_site_transient_update_themes', 'grfw_theme_update');