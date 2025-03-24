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
require_once get_template_directory() . '/inc/create-tables.php';
require_once get_template_directory() . '/inc/add-cors-header.php';
require_once get_template_directory() . '/inc/register.php';
require_once get_template_directory() . '/inc/login.php';
require_once get_template_directory() . '/inc/forgot-password.php';
require_once get_template_directory() . '/inc/search.php';
require_once get_template_directory() . '/inc/send_mail.php';
require_once get_template_directory() . '/inc/user_address.php';

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
