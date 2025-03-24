<?php

/**
 * Thêm CORS headers cho API
 */
function add_cors_headers() {
  $frontend_url = get_option('custom_frontend_url', 'http://localhost:3000');
  
  header('Access-Control-Allow-Origin: ' . $frontend_url);
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
  
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      status_header(200);
      exit();
  }
}

add_action('rest_api_init', function() {
  add_action('rest_pre_serve_request', 'add_cors_headers');
}, 15);