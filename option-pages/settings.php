<?php

// Add Settings Page to the Admin Menu
function gm_github_updater_add_settings_page()
{
  add_options_page(
    'GitHub Updater Settings', // Page title
    'GitHub Updater', // Menu title
    'manage_options', // Capability
    'gm-github-updater', // Menu slug
    'gm_github_updater_render_settings' // Callback function
  );
}
add_action('admin_menu', 'gm_github_updater_add_settings_page');

// Register Settings
function gm_github_updater_register_settings()
{
  register_setting(
    'gm_github_updater_settings_group', // Option group
    'gm_github_access_token', // Option name
    array(
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
      'default' => '',
    )
  );
}
add_action('admin_init', 'gm_github_updater_register_settings');

// Render the Settings Page
function gm_github_updater_render_settings()
{
  // Check user capabilities
  if (!current_user_can('manage_options')) {
    return;
  }

  // Show error/update messages
  settings_errors('gm_github_updater_messages');
?>
<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
  <form action="options.php" method="post">
    <?php
      settings_fields('gm_github_updater_settings_group');
      do_settings_sections('gm_github_updater_settings_group');
      ?>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="gm_github_access_token">GitHub Access Token</label></th>
        <td>
          <input type="password" id="gm_github_access_token" name="gm_github_access_token"
            value="<?php echo esc_attr(get_option('gm_github_access_token')); ?>" class="regular-text" />
          <p class="description">
            Enter your GitHub Access Token here. This token is used to authenticate API requests for checking updates
            and downloading packages.
            <br /><strong>Note:</strong> Keep your access token secure and do not share it publicly.
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row">Force Check for Updates</th>
        <td>
          <a href="<?php echo admin_url('update-core.php?force-check=1'); ?>" class="button button-secondary">Check for
            Updates Now</a>
          <p class="description">
            Clicking this button will force the plugin to check for updates for all themes and plugins from GitHub
            repositories. Otherwise, updates will be checked as part of WordPress's regular update checks.
          </p>
        </td>
      </tr>
      <tr>
        <th scope="row">How It Works</th>
        <td>
          <p>
            This plugin allows automatic updates of your themes and plugins from GitHub repositories. To enable this
            functionality:
          </p>
          <ol>
            <li>Ensure your themes and plugins have the <code>Update URI</code> field correctly set in their headers,
              pointing to the GitHub repository in the format <code>username/repository/branch</code>.</li>
            <li>That is enough for public GitHub repositories, but if your repo is private, you'll need to create a <a
                href="https://github.com/settings/tokens" target="_blank">GitHub Access Token</a> with
              appropriate permissions and enter it in the field above.</li>
            <li>Create releases in your GitHub repository with proper <code>tag_name</code> following semantic
              versioning (e.g., <code>v1.0.0</code>).</li>
          </ol>
          <p>
            When a new release is published on GitHub, this plugin will automatically check for updates and prompt you
            to update the theme or plugin within the WordPress admin dashboard.
          </p>
        </td>
      </tr>
    </table>
    <?php submit_button('Save Settings'); ?>
  </form>
</div>
<?php
}