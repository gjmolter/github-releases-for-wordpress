A simple WordPress plugin that enables automatic updates for your themes and plugins directly from GitHub repositories. 

## Installation

1. **Download the Plugin:**
   - Download the Plugin code as .zip file from [GitHub](https://github.com/gjmolter/wp-github-updater).

2. **Install and Activate on your WordPress site:**
   - Go to **Plugins > Add New > Upload Plugin**.
   - Choose the downloaded ZIP file and click **Install Now**.
   - After installation, activate the plugin from the **Plugins** page.

3. **Set up GitHub Access Token:**
   - Go to **Settings > GitHub Updater**, add your token and click **Save Settings**.

## Plugin and Repository Configs 

In order to get a theme or plugin to update from GitHub, simply have the repo set up (if private, make sure that the configured Access Token has access to it) and add `Update URI: [username]/[repo]` to the plugin/theme headers. That will pick up the repo's latest release. 

(Example: `Update URI: gjmolter/wp-github-updater`)

> &nbsp; 
> **This plugin works with releases, not branches.** 
> 
> Simply pushing changes to a branch (`main` or any other) will **not** trigger an update. You can automate releases using GitHub Actions if you want. 
>
> Otherwise, you can create the releases manually from GitHub's interface.
> &nbsp;

If you want to specify a release, just add `/release` to the end of the Update URI, so it becomes `Update URI: [username]/[repo]/[release_tag_name]`. 

(Example: `Update URI: gjmolter/wp-github-updater/1.0.0`)

## Features

- **Automatic Updates:** Seamlessly update your WordPress themes and plugins from GitHub releases. Set up autom
- **Public and Private Repos:** The plugin works with both public and private repos (as long as the GitHub Access Token provided has access to the private repo)
- **Optional Release Specifier:** By default it will get the latest release, but you can pass the release name as part of the UpdateURI too
- **Fully Integrated to WordPress:** The plugin hooks into the default WordPress updater, so the process of updating themes and plugins is exactly the same as it would be for other plugin/themes.


## Configuration

(If you're using public repos, this step is not necessary)

1. **Navigate to Settings:**
   - In the WordPress admin dashboard, go to **Settings > GitHub Updater**.

2. **Enter GitHub Access Token:**
   - In the **GitHub Access Token** field, enter your personal access token. This token is used to authenticate API requests for checking updates and downloading packages.
   - **Note:** Keep your access token secure. Do not share it publicly.

3. **Save Settings:**
   - Click the **Save Settings** button to store your configuration.

## How It Works

The **WordPress GitHub Updates (BA)** plugin automates the process of updating your themes and plugins from GitHub repositories. Here's a breakdown of its functionality:

1. **UpdateURI Field:**
   - Each theme or plugin you wish to update via GitHub must include an `UpdateURI` field in its header. This field should follow the format: `username/repository/release`.
   
   **Example for a Plugin Header (index.php):**
   ```php
   /*
   Plugin Name: My Plugin
   Update URI: username/repository/release
   Version: 1.0.0
   */
   ```

   **Example for a Theme Header (style.css):**
   ```php
   /*
    Theme Name: My Theme
    Update URI: username/repository/release
    Version: 1.0.0
    */
   ```

   The plugin will only consider updating to a new version when the GitHub release it finds has a Version Tag higher than the version on the local plugin/theme.
   Adding a GitHub Action to generate a new release when the Version changes works great for automatically keeping your assets updated.

## Performing an Update

1. **Check for Updates:**
- Navigate to **Dashboard > Updates** in your WordPress admin area.
- The plugin will list available updates for your themes and plugins sourced from GitHub.

2. **Update Now:**
- Click the **Update Now** button next to the theme or plugin you wish to update.
- WordPress will handle the download and installation process automatically.

3. **Automatic Background Updates:**
- Optionally, you can configure WordPress to perform automatic updates just like you would for any other plugin.

## Forcing Update Checks

If you need to force a manual update check to fetch the latest release from GitHub by hitting any WordPress Admin page with the query parameter: ?force-git-update.
