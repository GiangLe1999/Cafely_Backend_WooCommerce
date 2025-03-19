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

function create_custom_user_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'custom_user';
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			first_name varchar(50) NOT NULL,
			last_name varchar(50) NOT NULL,
			email varchar(100) NOT NULL,
			password varchar(255) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
	) $charset_collate;";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

// Kích hoạt function khi plugin được kích hoạt hoặc khi theme được khởi tạo
add_action('init', 'create_custom_user_table');

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
	if (empty($params['first_name']) || empty($params['last_name']) || 
			empty($params['email']) || empty($params['password'])) {
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