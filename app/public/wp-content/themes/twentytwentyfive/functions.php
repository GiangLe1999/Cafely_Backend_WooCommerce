<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

require_once get_template_directory() . '/send_mail.php';

// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

add_filter('woocommerce_rest_check_permissions', '__return_true');

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;


add_action('rest_api_init', function () {
	register_rest_route('custom/v1', '/search', array(
			'methods' => 'GET',
			'callback' => 'custom_search',
			'permission_callback' => '__return_true', // Hoặc định nghĩa quyền nếu cần
	));
});

function custom_search($request) {
	$keyword = $request->get_param('keyword');

	if (empty($keyword)) {
			return new WP_Error('no_keyword', 'You must provide a search keyword', array('status' => 400));
	}

	// Tìm kiếm sản phẩm WooCommerce chỉ trong tiêu đề
	$query = new WP_Query(array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			's' => $keyword, // Tìm kiếm
			'fields' => 'ids', // Chỉ trả về ID
	));

	$products = array_map(function ($product_id) {
			$product = wc_get_product($product_id);
			return array(
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'slug' => $product->get_slug(),
					'image' => wp_get_attachment_image_url($product->get_image_id(), 'full'),
					'regular_price' => $product->get_regular_price(),
					'sale_price' => $product->get_sale_price(),
					'average_rating' => number_format((float) $product->get_average_rating(), 2),
					'rating_count' => $product->get_rating_count(),
			);
	}, $query->posts);

	// Tìm kiếm bài viết WordPress
	$posts = get_posts(array(
			's' => $keyword, // Tìm kiếm trong tiêu đề bài viết
			'post_type' => 'post',
			'posts_per_page' => -1,
			'post_status' => 'publish',
	));

	$post_results = array_map(function ($post) {
    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'excerpt' => wp_trim_words($post->post_content, 30),
        'slug' => $post->post_name,
        'thumbnail' => get_the_post_thumbnail_url($post->ID, 'full')
    );
}, $posts);


	return array(
			'products' => $products,
			'posts' => $post_results,
	);
}

function log_search_query($query) {
	if ($query->is_search && !is_admin()) {
			global $wpdb;

			// Lấy từ khóa tìm kiếm
			$search_query = get_search_query();

			if (!empty($search_query)) {
					// Lưu vào bảng custom trong database
					$wpdb->insert(
							$wpdb->prefix . 'search_log', // Tên bảng
							array(
									'keyword' => $search_query,
									'search_count' => 1, // Mặc định là 1 lần
									'last_searched' => current_time('mysql') // Thời gian hiện tại
							),
							array('%s', '%d', '%s') // Định dạng
					);
			}
	}
}
add_action('pre_get_posts', 'log_search_query');

function create_custom_tables() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	// 1. Bảng users (đã có)
	$user_table = $wpdb->prefix . 'custom_user';
	$user_sql = "CREATE TABLE $user_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
      first_name varchar(50) DEFAULT NULL,
      last_name varchar(50) DEFAULT NULL,
			email varchar(100) NOT NULL,
			password varchar(255) NOT NULL,
 			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
	) $charset_collate;";
	dbDelta($user_sql);
	
	// 2. Bảng địa chỉ
	$address_table = $wpdb->prefix . 'custom_user_address';
	$address_sql = "CREATE TABLE $address_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			address_type varchar(20) DEFAULT 'shipping', /* shipping hoặc billing */
			is_default tinyint(1) DEFAULT 0,
			first_name varchar(50) DEFAULT '',
			last_name varchar(50) DEFAULT '',
			company varchar(100) DEFAULT '',
			address_line1 varchar(255) NOT NULL,
			address_line2 varchar(255) DEFAULT '',
			city varchar(100) NOT NULL,
			state varchar(100) DEFAULT '',
			postcode varchar(20) DEFAULT '',
			country varchar(100) DEFAULT '',
			phone varchar(20) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			CONSTRAINT fk_address_user FOREIGN KEY (user_id) REFERENCES {$user_table} (id) ON DELETE CASCADE
	) $charset_collate;";
	dbDelta($address_sql);
	
	// 3. Bảng liên kết với WooCommerce orders
	$link_table = $wpdb->prefix . 'custom_user_wc_orders';
	$link_sql = "CREATE TABLE $link_table (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id mediumint(9) NOT NULL,
			order_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_order (user_id, order_id),
			KEY user_id (user_id),
			KEY order_id (order_id),
			CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES {$user_table} (id) ON DELETE CASCADE
	) $charset_collate;";
	dbDelta($link_sql);

	// 4. Bảng lưu reset password token
	$reset_table = $wpdb->prefix . 'custom_password_reset';
	$reset_sql = "CREATE TABLE $reset_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id mediumint(9) NOT NULL,
		token varchar(64) NOT NULL,
		expires_at datetime NOT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		UNIQUE KEY token (token),
		UNIQUE KEY user_id (user_id),
		CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES {$user_table} (id) ON DELETE CASCADE
) $charset_collate;";
dbDelta($reset_sql);
}

function create_custom_tables_if_not_exists() {
	global $wpdb;
	
	// Define all tables that need to be checked
	$tables = array(
			$wpdb->prefix . 'custom_user',
			$wpdb->prefix . 'custom_user_address',
			$wpdb->prefix . 'custom_user_wc_orders',
			$wpdb->prefix . 'custom_password_reset'
	);
	
	// Check if all tables exist
	$all_tables_exist = true;
	foreach ($tables as $table_name) {
			if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
					$all_tables_exist = false;
					break;
			}
	}
	
	// If any table is missing, create all tables
	if (!$all_tables_exist) {
			create_custom_tables();
	}
}

add_action('after_setup_theme', 'create_custom_tables_if_not_exists');

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
            'Email không được để trống',
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
        return [
            'success' => true,
            'message' => 'Nếu email tồn tại, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu'
        ];
    }
    
    // Tạo token
    $token = generate_reset_token($user->id);
    
    if (!$token) {
        return new WP_Error(
            'token_generation_failed',
            'Không thể tạo token reset password',
            ['status' => 500]
        );
    }
    
    // URL frontend
    $frontend_url = get_option('custom_frontend_url', 'http://localhost:3000');
    $reset_url = trailingslashit($frontend_url) . "reset-password?token=$token";

    // Gửi email
    $name = !empty($user->first_name) ? $user->first_name : 'Bạn';
    $subject = 'Đặt lại mật khẩu cho tài khoản của bạn';
    
    $message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Đặt lại mật khẩu</h2>
        <p>Xin chào $name,</p>
        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
        <p>Vui lòng nhấp vào liên kết dưới đây để đặt lại mật khẩu:</p>
        <p>
            <a 
                href='$reset_url' 
                style='display: inline-block; background-color: #0070f3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'
            >
                Đặt lại mật khẩu
            </a>
        </p>
        <p>Liên kết này sẽ hết hạn sau 24 giờ.</p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <p>Trân trọng,</p>
        <p>Đội ngũ hỗ trợ</p>
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
            'Token và mật khẩu mới không được để trống',
            ['status' => 400]
        );
    }
    
    // Xác thực token
    $user_id = verify_reset_token($token);
    
    if (!$user_id) {
        return new WP_Error(
            'invalid_token',
            'Token không hợp lệ hoặc đã hết hạn',
            ['status' => 400]
        );
    }
    
    // Kiểm tra độ mạnh của mật khẩu
    if (strlen($new_password) < 6) {
        return new WP_Error(
            'weak_password',
            'Mật khẩu phải có ít nhất 6 ký tự',
            ['status' => 400]
        );
    }
    
    // Cập nhật mật khẩu
    $updated = update_user_password($user_id, $new_password);
    
    if (!$updated) {
        return new WP_Error(
            'update_failed',
            'Không thể cập nhật mật khẩu',
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

// Hook để liên kết đơn hàng WooCommerce với custom user
function link_wc_order_to_custom_user($order_id) {
	// Chỉ chạy cho đơn hàng mới
	if (!$order_id) return;
	
	$order = wc_get_order($order_id);
	$email = $order->get_billing_email();
	
	global $wpdb;
	$user_table = $wpdb->prefix . 'custom_user';
	$link_table = $wpdb->prefix . 'custom_user_wc_orders';
	
	// Tìm custom user bằng email
	$user_id = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $user_table WHERE email = %s",
			$email
	));
	
	// Nếu tìm thấy user, liên kết với đơn hàng
	if ($user_id) {
			$wpdb->insert(
					$link_table,
					array(
							'user_id' => $user_id,
							'order_id' => $order_id
					)
			);
	}
}
add_action('woocommerce_new_order', 'link_wc_order_to_custom_user');

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

// Hàm để lấy đơn hàng của user
function get_custom_user_orders($user_id) {
	global $wpdb;
	$link_table = $wpdb->prefix . 'custom_user_wc_orders';
	
	// Lấy tất cả order ID của user này
	$order_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT order_id FROM $link_table WHERE user_id = %d",
			$user_id
	));
	
	$orders = array();
	if (!empty($order_ids)) {
			foreach ($order_ids as $order_id) {
					$orders[] = wc_get_order($order_id);
			}
	}
	
	return $orders;
}

// Đăng ký API endpoint để lấy đơn hàng
function register_custom_user_orders_api() {
	register_rest_route('custom/v1', '/users/(?P<id>\d+)/orders', array(
			'methods' => 'GET',
			'callback' => 'get_custom_user_orders_api',
			'permission_callback' => function() {
					return true; // Hoặc kiểm tra auth token của bạn
			}
	));
}
add_action('rest_api_init', 'register_custom_user_orders_api');

function get_custom_user_orders_api($request) {
	$user_id = $request['id'];
	
	global $wpdb;
	$user_table = $wpdb->prefix . 'custom_user';
	
	// Kiểm tra user có tồn tại không
	$user = $wpdb->get_row($wpdb->prepare(
			"SELECT id FROM $user_table WHERE id = %d",
			$user_id
	));
	
	if (!$user) {
			return new WP_Error('user_not_found', 'Người dùng không tồn tại', array('status' => 404));
	}
	
	// Lấy đơn hàng
	$orders = get_custom_user_orders($user_id);
	$order_data = array();
	
	foreach ($orders as $order) {
			$order_data[] = array(
					'id' => $order->get_id(),
					'status' => $order->get_status(),
					'total' => $order->get_total(),
					'currency' => $order->get_currency(),
					'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
					'items_count' => count($order->get_items()),
					'payment_method' => $order->get_payment_method_title()
			);
	}
	
	return $order_data;
}
