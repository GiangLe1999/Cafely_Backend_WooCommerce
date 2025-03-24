<?php

/**
 * Tạo token reset password
 *
 * @param int $user_id ID của người dùng
 * @return string Token được tạo
 */
function generate_reset_token($user_id) {
	global $wpdb;
	$reset_table = $wpdb->prefix . 'custom_password_reset';
	$user_table = $wpdb->prefix . 'custom_user';
	
	// Kiểm tra user_id có tồn tại
	$user_exists = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $user_table WHERE id = %d",
			$user_id
	));
	
	if (!$user_exists) {
			return false;
	}
	
	// Tạo token ngẫu nhiên
	$token = bin2hex(random_bytes(32));
	
	// Tính thời gian hết hạn (24 giờ)
	$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

	// Xóa token cũ nếu có
	$wpdb->delete(
		$reset_table,
		['user_id' => $user_id],
		['%d']
	);
	
	// Lưu token mới
	$wpdb->insert(
			$reset_table,
			[
					'user_id' => $user_id,
					'token' => $token,
					'expires_at' => $expires_at
			],
			['%d', '%s', '%s']
	);
	
	if ($wpdb->last_error) {
			error_log('DB Error: ' . $wpdb->last_error);
			return false;
	}
	
	return $token;
}

/**
* Xác thực token reset password
*
* @param string $token Token cần xác thực
* @return int|false ID người dùng nếu token hợp lệ, false nếu không
*/
function verify_reset_token($token) {
	global $wpdb;
	$reset_table = $wpdb->prefix . 'custom_password_reset';
	
	$reset_record = $wpdb->get_row($wpdb->prepare(
			"SELECT user_id, expires_at FROM $reset_table WHERE token = %s",
			$token
	));
	
	// Nếu không tìm thấy token hoặc token đã hết hạn
	if (!$reset_record || strtotime($reset_record->expires_at) < time()) {
			return false;
	}
	
	return $reset_record->user_id;
}

/**
* Xóa token reset password sau khi sử dụng
*
* @param string $token Token cần xóa
* @return bool Kết quả xóa
*/
function delete_reset_token($token) {
	global $wpdb;
	$reset_table = $wpdb->prefix . 'custom_password_reset';
	
	$result = $wpdb->delete(
			$reset_table,
			['token' => $token],
			['%s']
	);
	
	return $result !== false;
}

/**
* Cập nhật mật khẩu
*
* @param int $user_id ID người dùng
* @param string $new_password Mật khẩu mới
* @return bool Kết quả cập nhật
*/
function update_user_password($user_id, $new_password) {
	global $wpdb;
	$user_table = $wpdb->prefix . 'custom_user';
	
	// Hash mật khẩu
	$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
	
	$result = $wpdb->update(
			$user_table,
			['password' => $hashed_password],
			['id' => $user_id],
			['%s'],
			['%d']
	);
	
	return $result !== false;
}

// Đăng ký REST API endpoints
add_action('rest_api_init', 'register_reset_password_routes');

function register_reset_password_routes() {
    // Endpoint yêu cầu reset password
    register_rest_route('custom/v1', '/forgot-password', [
        'methods' => 'POST',
        'callback' => 'handle_forgot_password_request',
        'permission_callback' => '__return_true',
    ]);
    
    // Endpoint cập nhật mật khẩu mới
    register_rest_route('custom/v1', '/reset-password', [
        'methods' => 'POST',
        'callback' => 'handle_reset_password_request',
        'permission_callback' => '__return_true',
    ]);
}

/**
 * Xử lý yêu cầu quên mật khẩu
 */
function handle_forgot_password_request($request) {
    $params = $request->get_params();
    $email = sanitize_email($params['email'] ?? '');
    
    if (empty($email)) {
        return new WP_Error(
            'missing_email',
            'Email cannot be empty.',
            ['status' => 400]
        );
    }
    
    global $wpdb;
    $user_table = $wpdb->prefix . 'custom_user';
    
    // Tìm user với email
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT id, email, first_name FROM $user_table WHERE email = %s",
        $email
    ));
    
    // Nếu không tìm thấy user, trả về thông báo thành công để tránh lộ email
    if (!$user) {
        return new WP_Error(
            'user_not_found',
            'User does not exist.',
            ['status' => 400]
		);
    }
    
    // Tạo token
    $token = generate_reset_token($user->id);
    
    if (!$token) {
        return new WP_Error(
            'token_generation_failed',
            'Cannot generate password reset token.',
            ['status' => 500]
        );
    }
    
    // URL frontend
    $frontend_url = get_option('custom_frontend_url', 'http://localhost:3000');
    $reset_url = trailingslashit($frontend_url) . "account/reset-password?token=$token";

    // Gửi email
    $name = !empty($user->first_name) ? $user->first_name : 'Bạn';
    $subject = 'Customer account password reset';
    
    $message = "
		<div style='font-family:-apple-system,BlinkMacSystemFont,&quot;Segoe UI&quot;,&quot;Roboto&quot;,&quot;Oxygen&quot;,&quot;Ubuntu&quot;,&quot;Cantarell&quot;,&quot;Fira Sans&quot;,&quot;Droid Sans&quot;,&quot;Helvetica Neue&quot;,sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; text-align: center;'>
    <h2 style='color: #222222; font-size: 24px;' font-weight: 400>Reset your password</h2>

    <p style='font-size: 16px; line-height: 1.5; color: #777;'>
        Follow this link to reset your customer account password at 
        <strong style='color: #FF4A11;'>CAFELY</strong>. If you didn't request a new password, you can safely delete this email.
    </p>

    <p style='text-align: center; margin: 20px 0;'>
        <a href='$reset_url' 
           style='display: inline-block; background-color: #FF4A11; color: white; 
                  padding: 14px 24px; text-decoration: none; border-radius: 4px; 
                  font-size: 14px; font-weight: bold;'>
            Reset your password
        </a>
    </p>

    <p style='text-align: center; font-size: 14px;'>
        or <a href='https://yourstore.com' style='color: #FF4A11; text-decoration: none; font-weight: bold;'>Visit our store</a>
    </p>
		</div>";
    
		// Gọi hàm gửi email
		$email_sent = send_custom_email($user->email, $subject, $message);
    
    if (!$email_sent) {
        error_log('Failed to send reset password email to: ' . $user->email);
    }
    
    return [
        'success' => true,
        'message' => 'Nếu email tồn tại, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu'
    ];
}

/**
 * Xử lý yêu cầu đặt lại mật khẩu
 */
function handle_reset_password_request($request) {
    $params = $request->get_params();
    $token = sanitize_text_field($params['token'] ?? '');
    $new_password = $params['newPassword'] ?? '';
    
    if (empty($token) || empty($new_password)) {
        return new WP_Error(
            'missing_data',
            'Token and new password cannot be empty.',
            ['status' => 400]
        );
    }
    
    // Xác thực token
    $user_id = verify_reset_token($token);
    
    if (!$user_id) {
        return new WP_Error(
            'invalid_token',
            'Token is invalid or has expired.',
            ['status' => 400]
        );
    }
    
    // Kiểm tra độ mạnh của mật khẩu
    if (strlen($new_password) < 6) {
        return new WP_Error(
            'weak_password',
            'Password must be at least 6 characters long.',
            ['status' => 400]
        );
    }
    
    // Cập nhật mật khẩu
    $updated = update_user_password($user_id, $new_password);
    
    if (!$updated) {
        return new WP_Error(
            'update_failed',
            'Unable to update password.',
            ['status' => 500]
        );
    }
    
    // Xóa token sau khi sử dụng
    delete_reset_token($token);
    
    return [
        'success' => true,
        'message' => 'Mật khẩu đã được đặt lại thành công'
    ];
}