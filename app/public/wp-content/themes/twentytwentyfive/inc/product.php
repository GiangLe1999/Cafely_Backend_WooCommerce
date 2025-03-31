<?php
// Register the custom REST API route
add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/home-products', array(
        'methods' => 'GET',
        'callback' => 'get_home_products',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Custom API callback function
 * 
 * @return WP_REST_Response
 */
function get_home_products() {
    // Get bestselling products
    $bestsellers = get_bestseller_products(4);
    
    // Get products in "Whole Bean Coffee" category
    $bean_coffee_products = get_products_by_category_name('Whole Bean Coffee');
    
    // Get products in "Whole Instant Coffee" category
    $instant_coffee_products = get_products_by_category_name('Instant Coffee');
    
    // Prepare the response
    $response = array(
        'bestsellers' => $bestsellers,
        'whole_bean_coffee' => $bean_coffee_products,
        'whole_instant_coffee' => $instant_coffee_products
    );
    
    return rest_ensure_response($response);
}

/**
 * Get bestseller products based on total sales
 * 
 * @param int $limit Number of products to return
 * @return array Array of product data
 */
function get_bestseller_products($limit = 4) {
    $args = array(
        'post_type' => 'product',
        'meta_key' => 'total_sales',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'posts_per_page' => $limit,
        'status' => 'publish',
    );
    
    $products = get_products_data($args);
    
    return $products;
}

/**
 * Get products by category name
 * 
 * @param string $category_name The name of the category
 * @return array Array of product data
 */
function get_products_by_category_name($category_name) {
    // Get category ID by name
    $category = get_term_by('name', $category_name, 'product_cat');
    
    if (!$category) {
        return array();
    }
    
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1, // Get all products
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category->term_id,
                'include_children' => false
            )
        ),
        'status' => 'publish',
    );
    
    $products = get_products_data($args);
    
    return $products;
}

// Thêm hàm trợ giúp để lấy hình ảnh đầu tiên từ gallery
function get_first_gallery_image($product) {
    $attachment_ids = $product->get_gallery_image_ids();
    
    if (!empty($attachment_ids)) {
        return wp_get_attachment_image_url($attachment_ids[0], 'full');
    }
    
    return '';
}

/**
 * Helper function to get formatted product data
 * 
 * @param array $args WP_Query arguments
 * @return array Array of formatted product data
 */
function get_products_data($args) {
    $query = new WP_Query($args);
    $products = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            
            // Skip if not a valid product
            if (!$product) {
                continue;
            }
            
            // Get product categories
            $categories = array();
            $terms = get_the_terms($product_id, 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }

            // Get product attributes
            $attributes = array();
            $product_attributes = $product->get_attributes();

            if (!empty($product_attributes)) {
                foreach ($product_attributes as $attribute_name => $attribute) {
                    $attribute_values = array();
                    
                    // For taxonomy-based attributes
                    if ($attribute->is_taxonomy()) {
                        $attribute_taxonomy = $attribute->get_taxonomy_object();
                        $attribute_values = wp_get_post_terms($product_id, $attribute->get_name(), array('fields' => 'names'));
                        $attribute_label = $attribute_taxonomy->attribute_label;
                    } else {
                        // For custom product attributes
                        $attribute_values = $attribute->get_options();
                        $attribute_label = $attribute->get_name();
                    }
                    
                    $attributes[] = array(
                        'name' => $attribute_label,
                        'slug' => $attribute->get_name(),
                        'position' => $attribute->get_position(),
                        'visible' => $attribute->get_visible(),
                        'variation' => $attribute->get_variation(),
                        'options' => $attribute_values
                    );
                }
            }
            
            // Format product data
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'slug' => $product->get_slug(),
                'price' => $product->get_price(),
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'short_description' => $product->get_short_description(),
                'average_rating' => $product->get_average_rating(),
                'review_count' => $product->get_review_count(),
                'thumbnail' => wp_get_attachment_image_url($product->get_image_id(), 'medium_large'),
                'first_gallery_image' => get_first_gallery_image($product),
                'attributes' => $attributes
            );
        }
        wp_reset_postdata();
    }
    
    return $products;
}
?>