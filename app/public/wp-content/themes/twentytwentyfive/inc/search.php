<?php

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