<?php

// Đăng ký REST API endpoint cho đăng nhập
function register_custom_user_login_api() {
	register_rest_route('custom/v1', '/login', array(
			'methods' => 'POST',
			'callback' => 'custom_user_login',
			'permission_callback' => function() {
					return true; // API đăng nhập nên là public
			}
	));
}
add_action('rest_api_init', 'register_custom_user_login_api');

// Hàm xử lý đăng nhập
function custom_user_login($request) {
	// Lấy dữ liệu từ request
	$params = $request->get_params();
	
	// Kiểm tra các trường bắt buộc
	if (empty($params['email']) || empty($params['password'])) {
			return new WP_Error('missing_fields', 'Vui lòng cung cấp email và mật khẩu', array('status' => 400));
	}
	
	// Kiểm tra email hợp lệ
	if (!is_email($params['email'])) {
			return new WP_Error('invalid_email', 'Email không hợp lệ', array('status' => 400));
	}
	
	global $wpdb;
	$table_name = $wpdb->prefix . 'custom_user';
	
	// Lấy thông tin user từ email
	$user = $wpdb->get_row($wpdb->prepare(
			"SELECT id, first_name, last_name, email, password FROM $table_name WHERE email = %s",
			$params['email']
	));
	
	// Kiểm tra xem user có tồn tại không
	if (!$user) {
			return new WP_Error('user_not_found', 'Người dùng không tồn tại', array('status' => 404));
	}
	
	// Kiểm tra mật khẩu
	if (!wp_check_password($params['password'], $user->password)) {
			return new WP_Error('incorrect_password', 'Mật khẩu không chính xác', array('status' => 401));
	}
	
	// Trả về thông tin user (không bao gồm mật khẩu)
	return array(
			'id' => $user->id,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'email' => $user->email,
	);
}