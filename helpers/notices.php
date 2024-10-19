<?php

// Show WP Admin Notices For Github Update Errors
function gm_display_update_errors()
{
  global $gm_github_update_errors;

  $access_token = gm_get_github_access_token();
  if (empty($access_token)) {
    $gm_github_update_errors[] = 'GitHub access token is not defined. This will prevent the plugin from checking for updates for private repositories, and without it even public repositories may have rate limiting issues with the GitHub API that could prevent updates.';
  }

  // Remove duplicates
  $gm_github_update_errors = array_unique($gm_github_update_errors);

  if (!empty($gm_github_update_errors)) {
    echo '<div class="notice notice-error is-dismissible">';
    foreach ($gm_github_update_errors as $error) {
      echo '<p>' . esc_html($error) . '</p>';
    }
    echo '</div>';
  }
}
add_action('admin_notices', 'gm_display_update_errors');