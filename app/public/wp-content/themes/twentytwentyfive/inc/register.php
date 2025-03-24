<?php

// Đăng ký REST API endpoint
function register_custom_user_api() {
	register_rest_route('custom/v1', '/users', array(
			'methods' => 'POST',
			'callback' => 'create_custom_user',
			'permission_callback' => function() {
					// Kiểm tra quyền truy cập - có thể thay đổi theo yêu cầu
					return true;
					// Hoặc return true; nếu bạn muốn API public
			}
	));
}
add_action('rest_api_init', 'register_custom_user_api');

// Hàm xử lý tạo user
function create_custom_user($request) {
	// Lấy dữ liệu từ request
	$params = $request->get_params();
	
	// Kiểm tra các trường bắt buộc
	if (empty($params['email']) || empty($params['password'])) {
			return new WP_Error('missing_fields', 'Vui lòng cung cấp đầy đủ thông tin', array('status' => 400));
	}
	
	// Kiểm tra email hợp lệ
	if (!is_email($params['email'])) {
			return new WP_Error('invalid_email', 'Email không hợp lệ', array('status' => 400));
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'custom_user';
	
	// Kiểm tra email đã tồn tại chưa
	$existing_user = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $table_name WHERE email = %s",
			$params['email']
	));
	
	if ($existing_user) {
			return new WP_Error('email_exists', 'Email đã tồn tại', array('status' => 400));
	}
	
	// Mã hóa mật khẩu
	$hashed_password = wp_hash_password($params['password']);
	
	// Thêm user mới
	$result = $wpdb->insert(
			$table_name,
			array(
					'first_name' => sanitize_text_field($params['first_name']),
					'last_name' => sanitize_text_field($params['last_name']),
					'email' => sanitize_email($params['email']),
					'password' => $hashed_password
			)
	);
	
	if ($result === false) {
			return new WP_Error('db_error', 'Không thể tạo user', array('status' => 500));
	}
	
	// Trả về thông tin user mới (không bao gồm mật khẩu)
	return array(
			'id' => $wpdb->insert_id,
			'first_name' => $params['first_name'],
			'last_name' => $params['last_name'],
			'email' => $params['email'],
			'created_at' => current_time('mysql')
	);
}