<?php

// Retrieve GitHub Access Token from Settings
function grfw_get_github_access_token()
{
  return trim(get_option('grfw_github_access_token', ''));
}

// Loop into the http request hook and add the GitHub necessary headers to the requests
function grfw_http_request_args($args, $url)
{
  if (strpos($url, 'api.github.com/repos') !== false || strpos($url, 'github.com') !== false) {
    $access_token = grfw_get_github_access_token();
    if (!empty($access_token)) {
      $args['headers']['Authorization'] = 'token ' . $access_token;
      $args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url');
      if (strpos($url, 'releases/assets')) {
        $args['headers']['Accept'] = 'application/octet-stream';
      } else {
        $args['headers']['Accept'] = 'application/vnd.github.v3.raw';
      }
    }
  }
  return $args;
}
add_filter('http_request_args', 'grfw_http_request_args', 10, 2);