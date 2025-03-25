<?php

function register_subscribers_routes() {
  register_rest_route('custom/v1', '/subscribe', array(
      'methods' => 'POST',
      'callback' => 'handle_email_subscription',
      'permission_callback' => '__return_true'
  ));

  register_rest_route('custom/v1', '/unsubscribe', array(
      'methods' => 'POST',
      'callback' => 'handle_email_unsubscription',
      'permission_callback' => '__return_true'
  ));
}
add_action('rest_api_init', 'register_subscribers_routes');

function handle_email_subscription(WP_REST_Request $request) {
  global $wpdb;
  $email = sanitize_email($request->get_param('email'));

  // Validate email
  if (!is_email($email)) {
      return new WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
  }

  // Check if email already exists
  $existing_subscriber = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$wpdb->prefix}custom_email_subscribers WHERE email = %s", 
      $email
  ));

  if ($existing_subscriber) {
      return new WP_Error('email_exists', 'Email already subscribed', array('status' => 409));
  }

  // Generate unique token
  $unique_token = wp_generate_password(64, false);

  // Insert new subscriber
  $insert_result = $wpdb->insert(
      $wpdb->prefix . 'custom_email_subscribers', 
      array(
          'email' => $email,
          'status' => 'active',
          'subscription_date' => current_time('mysql'),
          'unique_token' => $unique_token
      ),
      array('%s', '%s', '%s', '%s')
  );

  if ($insert_result === false) {
      return new WP_Error('insert_failed', 'Failed to add subscriber', array('status' => 500));
  }

  // Optional: Send confirmation email
  // You would implement your email sending logic here

  return rest_ensure_response(array(
      'message' => 'Subscription successful',
      'email' => $email,
      'token' => $unique_token
  ));
}

function handle_email_unsubscription(WP_REST_Request $request) {
  global $wpdb;
  $token = sanitize_text_field($request->get_param('token'));

  // Tìm subscriber bằng token
  $subscriber = $wpdb->get_row($wpdb->prepare(
      "SELECT email FROM {$wpdb->prefix}custom_email_subscribers 
      WHERE unique_token = %s", 
      $token
  ));

  if (!$subscriber) {
      return new WP_Error('not_found', 'Invalid unsubscribe link', array('status' => 404));
  }

  // Update status to unsubscribed
  $update_result = $wpdb->update(
      $wpdb->prefix . 'custom_email_subscribers',
      array('status' => 'unsubscribed'),
      array('unique_token' => $token),
      array('%s'),
      array('%s')
  );

  if ($update_result === false) {
      return new WP_Error('update_failed', 'Failed to unsubscribe', array('status' => 500));
  }

  return rest_ensure_response(array(
      'message' => 'Unsubscription successful',
      'email' => $subscriber->email
  ));
}