<?php

// Show WP Admin Notices For Github Update Errors
function grfw_display_update_errors()
{
  global $grfw_github_update_errors;

  $access_token = grfw_get_github_access_token();
  if (empty($access_token)) {
    $grfw_github_update_errors[] = 'GitHub access token is not defined. This will prevent the plugin from checking for updates for private repositories, and without it even public repositories may have rate limiting issues with the GitHub API that could prevent updates.';
  }

  // Remove duplicates
  $grfw_github_update_errors = array_unique($grfw_github_update_errors);

  if (!empty($grfw_github_update_errors)) {
    echo '<div class="notice notice-error is-dismissible">';
    foreach ($grfw_github_update_errors as $error) {
      echo '<p>' . esc_html($error) . '</p>';
    }
    echo '</div>';
  }
}
add_action('admin_notices', 'grfw_display_update_errors');
