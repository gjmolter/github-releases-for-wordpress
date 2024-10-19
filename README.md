# Github Releases for WordPress

A simple WordPress plugin that enables automatic updates for your themes and plugins directly from GitHub repositories. 

## Installation

1. **Download the Plugin:**
   - Download the Plugin code as .zip file from [GitHub](https://github.com/gjmolter/github-releases-for-wordpress).

2. **Install and Activate on your WordPress site:**
   - Go to **Plugins > Add New > Upload Plugin**.
   - Choose the downloaded ZIP file and click **Install Now**.
   - After installation, activate the plugin from the **Plugins** page.

3. **Set up GitHub Access Token:**
   - Go to **Settings > GitHub Updater**, add your token and click **Save Settings**.

## Plugin and Repository Configs 

In order to get a theme or plugin to update from GitHub, simply have the repo set up (if private, make sure that the configured Access Token has access to it) and add `Update URI: [username]/[repo]` to the plugin/theme headers. That will pick up the repo's latest release. 

(Example: `Update URI: gjmolter/github-releases-for-wordpress`)

> &nbsp; 
> **This plugin works with releases, not branches.** 
> 
> Simply pushing changes to a branch (`main` or any other) will **not** trigger an update. You can automate releases using GitHub Actions if you want. 
>
> Otherwise, you can create the releases manually from GitHub's interface.
> &nbsp;

If you want to specify a release, just add `/release` to the end of the Update URI, so it becomes `Update URI: [username]/[repo]/[release_tag_name]`. 

(Example: `Update URI: gjmolter/github-releases-for-wordpress/1.0.0`)

## Features

- **Fully integrated with WordPress:** Seamlessly update your WordPress themes and plugins from GitHub releases like you would with any other source.
- **Public and Private Repos:** The plugin works with both public and private repos (as long as the GitHub Access Token provided has access to the private repo).
- **Optional Release Specifier:** By default it will get the latest release, but you can pass the release name as part of the `Update URI` too.

## Configuration

1. **Navigate to Settings:**
   In the WordPress admin dashboard, go to **Settings > GitHub Updater**.

2. **Enter GitHub Access Token:**
   In the **GitHub Access Token** field, enter your personal access token. This token is used to authenticate API requests for checking updates and downloading packages.
   > **Note:** Keep your access token secure. Do not share it publicly.

3. **Save Settings:**
   Click the **Save Settings** button to store your configuration.

## How It Works

1. **Set up repository:**
   Upload your theme/plugin to GitHub. Then create a GitHub release with `tag_name` as the same version as you've set in your theme/plugin headers.

2. **Update URI Field:**
   - Each theme or plugin you wish to update via GitHub must include an `Update URI` field in its header. This field should follow the format: `user/repo` or `user/repo/release`
   
   **Example for a Plugin Header (currently only supports index.php):**
   ```php
   /*
   Plugin Name: My Plugin
   Update URI: gjmolter/github-releases-for-wordpress
   Version: 1.0.0
   */
   ```

   **Example for a Theme Header (style.css):**
   ```php
   /*
    Theme Name: My Theme
    Update URI: gjmolter/my-theme
    Version: 1.0.0
    */
   ```

   The plugin will only consider updating to a new version when the GitHub release it finds has a Version Tag higher than the version on the local plugin/theme.

   ### GitHub Actions
   Adding a GitHub Action to generate new releases automatically works great. 
   This very plugin uses one, so if you want an example, just check `.github/workflows/deploy.yml`.
   The plugin also handles the download and extraction of extra .zip assets added to the GitHub release, meaning you can use a GitHub Action to process files and generate a dist.zip file that gets downloaded and extracted in your plugin/theme folder. Useful for build scripts.

## Performing an Update

1. **Check for Updates:**
- Navigate to **Dashboard > Updates** in your WordPress admin area.
- The plugin will list available updates for your themes and plugins sourced from GitHub.

2. **Update Now:**
- Click the **Update Now** button next to the theme or plugin you wish to update.
- WordPress will handle the download and installation process automatically.
