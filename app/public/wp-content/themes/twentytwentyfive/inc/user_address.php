<?php

function get_default_user_address($request) {
  global $wpdb;
  $params = $request->get_json_params();
  $user_id = $params['user_id'] ?? 0;

  if (!$user_id) {
      return new WP_Error('missing_user', 'User ID is required.', ['status' => 400]);
  }

  $table_name = $wpdb->prefix . 'custom_user_address';
  $address = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE user_id = %d AND is_default = 1 LIMIT 1",
      $user_id
  ));

  if (!$address) {
      return new WP_Error('no_address', 'No default address found.', ['status' => 404]);
  }

  return new WP_REST_Response($address, 200);
}

function register_user_address_api() {
  register_rest_route('custom/v1', '/default-address/', array(
      'methods'             => 'POST',
      'callback'            => 'get_default_user_address',
      'permission_callback' => '__return_true'
  ));
}

add_action('rest_api_init', 'register_user_address_api');

function get_all_user_addresses($request) {
  global $wpdb;
  $params = $request->get_json_params();
  $user_id = $params['user_id'] ?? 0;

  if (!$user_id) {
      return new WP_Error('missing_user', 'User ID is required.', ['status' => 400]);
  }

  $table_name = $wpdb->prefix . 'custom_user_address';
  $addresses = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $table_name WHERE user_id = %d",
      $user_id
  ));

  if (!$addresses) {
      return new WP_Error('no_addresses', 'No addresses found.', ['status' => 404]);
  }

  return new WP_REST_Response($addresses, 200);
}

function register_all_user_addresses_api() {
  register_rest_route('custom/v1', '/all-addresses/', [
      'methods'             => 'POST',
      'callback'            => 'get_all_user_addresses',
      'permission_callback' => '__return_true',
  ]);
}

add_action('rest_api_init', 'register_all_user_addresses_api');

function get_user_address_count(WP_REST_Request $request) {
    global $wpdb;
    $params = $request->get_json_params();
    $user_id = $params['user_id'] ?? 0;

    if (!$user_id) {
        return new WP_Error('missing_user_id', 'User ID is required', ['status' => 400]);
    }

    // Đếm số lượng địa chỉ của user trong bảng wp_custom_user_address
    $table_name = $wpdb->prefix . 'custom_user_address';
    $address_count = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d", $user_id)
    );

    return rest_ensure_response(intval($address_count));
}

// Đăng ký REST API endpoint
function register_custom_user_address_count_api() {
  register_rest_route('custom/v1', '/address-count', [
      'methods'  => 'POST',
      'callback' => 'get_user_address_count',
      'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'register_custom_user_address_count_api');

// Thêm địa chỉ cho một user
function add_user_address(WP_REST_Request $request) {
  global $wpdb;
  $params = $request->get_json_params();
  
  // Kiểm tra user_id
  $user_id = $params['user_id'] ?? 0;
  if (!$user_id) {
      return new WP_Error('missing_user', 'User ID is required.', ['status' => 400]);
  }
  
  // Kiểm tra user tồn tại trong bảng custom_user
	$user_table_name = $wpdb->prefix . 'custom_user';
  $existing_user = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM $user_table_name WHERE id = %d",
    $user_id
  ));

  if (!$existing_user) {
      return new WP_Error('invalid_user', 'User does not exist.', ['status' => 404]);
  }
  
  // Validate dữ liệu đầu vào
  $required_fields = ['first_name', 'last_name', 'address_1', 'city', 'country_region', 'state_province', 'postal_zip_code', 'phone'];
  foreach ($required_fields as $field) {
      if (empty($params[$field])) {
          return new WP_Error('missing_field', "Field {$field} is required.", ['status' => 400]);
      }
  }
  
  // Xử lý is_default
  $is_default = isset($params['is_default']) ? (bool)$params['is_default'] : false;
  
  // Nếu là địa chỉ mặc định, hủy đặt mặc định cho tất cả địa chỉ khác
  if ($is_default) {
      $table_name = $wpdb->prefix . 'custom_user_address';
      $wpdb->update(
          $table_name,
          ['is_default' => 0],
          ['user_id' => $user_id],
          ['%d'],
          ['%d']
      );
  }
  
  // Chuẩn bị dữ liệu để insert
  $data = [
      'user_id' => $user_id,
      'first_name' => sanitize_text_field($params['first_name']),
      'last_name' => sanitize_text_field($params['last_name']),
      'company' => sanitize_text_field($params['company'] ?? ''),
      'address_line1' => sanitize_text_field($params['address_1']),
      'address_line2' => sanitize_text_field($params['address_2'] ?? ''),
      'city' => sanitize_text_field($params['city']),
      'state' => sanitize_text_field($params['state_province']),
      'country' => sanitize_text_field($params['country_region']),
      'postcode' => sanitize_text_field($params['postal_zip_code']),
      'phone' => sanitize_text_field($params['phone']),
      'is_default' => $is_default ? 1 : 0,
      'created_at' => current_time('mysql')
  ];
  
  // Format dữ liệu
  $format = [
      '%d', // user_id
      '%s', // first_name
      '%s', // last_name
      '%s', // company
      '%s', // address_1
      '%s', // address_2
      '%s', // city
      '%s', // state_province
      '%s', // country_region
      '%s', // postal_zip_code
      '%s', // phone
      '%d', // is_default
      '%s'  // created_at
  ];
  
  // Insert vào database
  $table_name = $wpdb->prefix . 'custom_user_address';
  $result = $wpdb->insert($table_name, $data, $format);
  
  if (!$result) {
      return new WP_Error('insert_failed', 'Failed to add address: ' . $wpdb->last_error, ['status' => 500]);
  }
  
  $address_id = $wpdb->insert_id;
  $data['id'] = $address_id;
  
  return new WP_REST_Response([
      'success' => true,
      'message' => 'Address added successfully',
      'address' => $data
  ], 201);
}

// Đăng ký REST API endpoint
function register_add_user_address_api() {
  register_rest_route('custom/v1', '/add-address', [
      'methods'  => 'POST',
      'callback' => 'add_user_address',
      'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'register_add_user_address_api');

function delete_user_address(WP_REST_Request $request) {
  global $wpdb;
  $params = $request->get_json_params();
  
  // Kiểm tra các tham số bắt buộc
  $address_id = $params['address_id'] ?? 0;
  $user_id = $params['user_id'] ?? 0;
  
  if (!$address_id) {
      return new WP_Error('missing_address_id', 'Address ID is required.', ['status' => 400]);
  }
  
  if (!$user_id) {
      return new WP_Error('missing_user_id', 'User ID is required.', ['status' => 400]);
  }
  
  // Xác minh địa chỉ thuộc về user để tránh xóa địa chỉ của người khác
  $table_name = $wpdb->prefix . 'custom_user_address';
  $address = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
      $address_id, $user_id
  ));
  
  if (!$address) {
      return new WP_Error('address_not_found', 'Address not found or does not belong to this user.', ['status' => 404]);
  }
  
  // Kiểm tra xem địa chỉ này có phải là địa chỉ mặc định không
  $is_default = $address->is_default;
  
  // Xóa địa chỉ
  $result = $wpdb->delete(
      $table_name,
      [
          'id' => $address_id,
          'user_id' => $user_id
      ],
      [
          '%d',
          '%d'
      ]
  );
  
  if ($result === false) {
      return new WP_Error('delete_failed', 'Failed to delete address: ' . $wpdb->last_error, ['status' => 500]);
  }
  
  // Nếu địa chỉ vừa xóa là địa chỉ mặc định, thiết lập địa chỉ mặc định mới (nếu còn địa chỉ khác)
  if ($is_default) {
      $remaining_address = $wpdb->get_row($wpdb->prepare(
          "SELECT id FROM $table_name WHERE user_id = %d ORDER BY id ASC LIMIT 1",
          $user_id
      ));
      
      if ($remaining_address) {
          $wpdb->update(
              $table_name,
              ['is_default' => 1],
              ['id' => $remaining_address->id],
              ['%d'],
              ['%d']
          );
      }
  }
  
  return new WP_REST_Response([
      'success' => true,
      'message' => 'Address deleted successfully',
      'deleted_address_id' => $address_id
  ], 200);
}

// Đăng ký REST API endpoint
function register_delete_user_address_api() {
  register_rest_route('custom/v1', '/delete-address', [
      'methods'  => 'POST',
      'callback' => 'delete_user_address',
      'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'register_delete_user_address_api');

// Update a user's address
function update_user_address(WP_REST_Request $request) {
  global $wpdb;
  $params = $request->get_json_params();
  
  // Check required parameters
  $address_id = $params['address_id'] ?? 0;
  $user_id = $params['user_id'] ?? 0;
  
  if (!$address_id) {
      return new WP_Error('missing_address_id', 'Address ID is required.', ['status' => 400]);
  }
  
  if (!$user_id) {
      return new WP_Error('missing_user_id', 'User ID is required.', ['status' => 400]);
  }
  
  // Verify address belongs to the user to prevent updating someone else's address
  $table_name = $wpdb->prefix . 'custom_user_address';
  $existing_address = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
      $address_id, $user_id
  ));
  
  if (!$existing_address) {
      return new WP_Error('address_not_found', 'Address not found or does not belong to this user.', ['status' => 404]);
  }
  
  // Handle is_default flag
  $is_default = isset($params['is_default']) ? (bool)$params['is_default'] : false;
  
  // If setting as default, unset other addresses as default
  if ($is_default) {
      $wpdb->update(
          $table_name,
          ['is_default' => 0],
          ['user_id' => $user_id],
          ['%d'],
          ['%d']
      );
  }
  
  // Prepare data for update
  $data = [];
  $format = [];
  
  // Only update fields that are provided in the request
  $updateable_fields = [
      'first_name' => '%s',
      'last_name' => '%s',
      'company' => '%s',
      'address_line1' => '%s',
      'address_line2' => '%s',
      'city' => '%s',
      'state' => '%s',
      'country' => '%s',
      'postcode' => '%s',
      'phone' => '%s',
      'is_default' => '%d'
  ];
  
  $field_mapping = [
      'address_1' => 'address_line1',
      'address_2' => 'address_line2',
      'state_province' => 'state',
      'country_region' => 'country',
      'postal_zip_code' => 'postcode'
  ];
  
  foreach ($updateable_fields as $field => $format_type) {
      // Check direct match first
      if (isset($params[$field])) {
          $data[$field] = sanitize_text_field($params[$field]);
          $format[] = $format_type;
      } 
      // Check mapped fields
      else {
          $mapped_field = array_search($field, $field_mapping);
          if ($mapped_field && isset($params[$mapped_field])) {
              $data[$field] = sanitize_text_field($params[$mapped_field]);
              $format[] = $format_type;
          }
      }
  }
  
  // Handle is_default separately since it's a boolean
  if (isset($params['is_default'])) {
      $data['is_default'] = $is_default ? 1 : 0;
      $format[] = '%d';
  }
  
  // Add updated_at timestamp
  $data['updated_at'] = current_time('mysql');
  $format[] = '%s';
  
  // If no data to update, return an error
  if (empty($data)) {
      return new WP_Error('no_fields', 'No fields to update were provided.', ['status' => 400]);
  }
  
  // Update the database
  $result = $wpdb->update(
      $table_name,
      $data,
      ['id' => $address_id, 'user_id' => $user_id],
      $format,
      ['%d', '%d']
  );
  
  if ($result === false) {
      return new WP_Error('update_failed', 'Failed to update address: ' . $wpdb->last_error, ['status' => 500]);
  }
  
  return new WP_REST_Response([
      'success' => true,
      'message' => 'Address updated successfully',
  ], 200);
}

// Register the REST API endpoint
function register_update_user_address_api() {
  register_rest_route('custom/v1', '/update-address', [
      'methods'  => 'POST',
      'callback' => 'update_user_address',
      'permission_callback' => '__return_true',
  ]);
}
add_action('rest_api_init', 'register_update_user_address_api');