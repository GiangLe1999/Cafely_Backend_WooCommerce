<?php

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

	// 5. Bảng đăng ký email (Subscribers)
	$subscribers_table = $wpdb->prefix . 'custom_email_subscribers';
	$subscribers_sql = "CREATE TABLE $subscribers_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		email varchar(100) NOT NULL,
		status enum('active', 'unsubscribed', 'bounced') DEFAULT 'active',
		subscription_date datetime DEFAULT CURRENT_TIMESTAMP,
		unique_token varchar(64) NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY email (email),
		UNIQUE KEY unique_token (unique_token)
	) $charset_collate;";
	dbDelta($subscribers_sql);
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