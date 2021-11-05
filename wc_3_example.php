<?php

// create variable parent product
$attributes = array('size'=>'sm','color'=>'red');
$data = array(
    'author' => 1, // optional
    'title' => 'Product Name',
    'content' => 'Description here',
    'regular_price' => 0, // product regular price
    'sale_price' => '', // product sale price (optional)
    'stock' => 0, // Set a minimal stock quantity
    'sku' => 'product sku', // optional
    'tax_class' => '', // optional
    'weight' => 0, // optional
// For NEW attributes/values use NAMES (not slugs)
    'attributes' => $attributes
);

$post_id = create_parent_product_variation($data);
wp_set_object_terms($post_id, $categoryArray, 'product_cat'); // set category
wp_set_object_terms($post_id, $productTagArray, 'product_tag'); // set tags
//Set Image
$imageUrl = "http://www.testimageurl.com/test.jpg";
getImage($post_id, $imageUrl, 'Gallery Description');
$variation_data = array(
    'attributes' => $attributes,
    'sku' => 'variable child sku',
    'regular_price' => 10, // enter variant price
    'sale_price' => '',
    'stock_qty' => 50, // enter stock qty
    'weight' => ''
);
$variation_id = create_product_variation($post_id, $variation_data);
$imageVariantUrl = "http://www.testimageurl.com/testVariant1.jpg";
getImage($variation_id, $imageVariantUrl, 'Gallery Description');
/**
 * Create a new variable product (with new attributes if they are).
 * (Needed functions:
 * @since 3.0.0
 * @param array $data | The data to insert in the product.
 */
function create_parent_product_variation($data) {
    if (!function_exists('save_product_attribute_from_name'))
        return;

    $postname = sanitize_title($data['title']);
    $author = empty($data['author']) ? '1' : $data['author'];
    $post_data = array(
        'post_author' => $author,
        'post_name' => $postname,
        'post_title' => $data['title'],
        'post_content' => $data['content'],
        'post_status' => 'publish',
        'ping_status' => 'closed',
        'post_type' => 'product',
        'guid' => home_url('/product/' . $postname . '/'),
    );

// Creating the product (post data)
    $product_id = wp_insert_post($post_data);
// Get an instance of the WC_Product_Variable object and save it
    $product = new WC_Product_Variable($product_id);
    $product->save();
## -- Other optional data -- ##
## (see WC_Product and WC_Product_Variable setters methods)
// Prices (No prices yet as we need to create product variations)
// IMAGES GALLERY
    if (!empty($data['gallery_ids']) && count($data['gallery_ids']) > 0)
        $product->set_gallery_image_ids($data['gallery_ids']);
// SKU
    if (!empty($data['sku']))
        $product->set_sku($data['sku']);
// STOCK (stock will be managed in variations)
    $product->set_stock_quantity($data['stock']); // Set a minimal stock quantity
    $product->set_manage_stock(true);
    $product->set_stock_status('');
// Tax class
    if (empty($data['tax_class']))
        $product->set_tax_class($data['tax_class']);
// WEIGHT
    if (!empty($data['weight']))
        $product->set_weight(''); // weight (reseting)
    else
        $product->set_weight($data['weight']);
    $product->validate_props(); // Check validation
## ---------------------- VARIATION ATTRIBUTES ---------------------- ##
    $product_attributes = array();
    foreach ($data['attributes'] as $key => $terms) {
        $taxonomy = wc_attribute_taxonomy_name($key); // The taxonomy slug
        $attr_label = ucfirst($key); // attribute label name
        $attr_name = ( wc_sanitize_taxonomy_name($key)); // attribute slug
// NEW Attributes: Register and save them
        if (!taxonomy_exists($taxonomy))
            save_product_attribute_from_name($attr_name, $attr_label);
        $product_attributes[$taxonomy] = array(
            'name' => $taxonomy,
            'value' => '',
            'position' => '',
            'is_visible' => 0,
            'is_variation' => 1,
            'is_taxonomy' => 1
        );
        if (is_array($terms) || is_object($terms)) {
            foreach ($terms as $value) {
                $term_name = ucfirst($value);
                $term_slug = sanitize_title($value);
// Check if the Term name exist and if not we create it.
                if (!term_exists($value, $taxonomy))
                    wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug)); // Create the term
// Set attribute values
                wp_set_post_terms($product_id, $term_name, $taxonomy, true);
            }
        }
    }
    update_post_meta($product_id, '_product_attributes', $product_attributes);
    $product->save(); // Save the data
    return $product_id;
}
/**
 * Create a product variation for a defined variable product ID.
 *
 * @since 3.0.0
 * @param int $product_id | Post ID of the product parent variable product.
 * @param array $variation_data | The data to insert in the product.
 */

function create_product_variation($product_id, $variation_data) {
// Get the Variable product object (parent)
    $product = wc_get_product($product_id);
    $variation_post = array(
        'post_title' => $product->get_title(),
        'post_name' => 'product-' . $product_id . '-variation',
        'post_status' => 'publish',
        'post_parent' => $product_id,
        'post_type' => 'product_variation',
        'guid' => $product->get_permalink()
    );
// Creating the product variation
    $variation_id = wp_insert_post($variation_post);
// Get an instance of the WC_Product_Variation object
    $variation = new WC_Product_Variation($variation_id);
// Iterating through the variations attributes
    foreach ($variation_data['attributes'] as $attribute => $term_name) {
        $taxonomy = 'pa_' . $attribute; // The attribute taxonomy
// Check if the Term name exist and if not we create it.
        if (!term_exists($term_name, $taxonomy))
            wp_insert_term($term_name, $taxonomy); // Create the term
        $term_slug = get_term_by('name', $term_name, $taxonomy)->slug; // Get the term slug
// Get the post Terms names from the parent variable product.
        $post_term_names = wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));
// Check if the post term exist and if not we set it in the parent variable product.
        if (!in_array($term_name, $post_term_names))
            wp_set_post_terms($product_id, $term_name, $taxonomy, true);
// Set/save the attribute data in the product variation
        update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
    }
## Set/save all other data
// SKU
    if (!empty($variation_data['sku']))
        $variation->set_sku($variation_data['sku']);
// Prices
    if (empty($variation_data['sale_price'])) {
        $variation->set_price($variation_data['regular_price']);
    } else {
        $variation->set_price($variation_data['sale_price']);
        $variation->set_sale_price($variation_data['sale_price']);
    }
    $variation->set_regular_price($variation_data['regular_price']);
// Stock
    if (!empty($variation_data['stock_qty'])) {
        $variation->set_stock_quantity($variation_data['stock_qty']);
        $variation->set_manage_stock(true);
        $variation->set_stock_status('');
    } else {
        $variation->set_manage_stock(false);
    }
    $variation->set_weight($variation_data['weight']); // weight (reseting)
    $variation->save(); // Save the data
    return $variation_id;
}

/**
 * Save a new product attribute from his name (slug).
 *
 * @since 3.0.0
 * @param string $name | The product attribute name (slug).
 * @param string $label | The product attribute label (name).
 */
function save_product_attribute_from_name($name, $label = '', $set = true) {
    if (!function_exists('get_attribute_id_from_name'))
        return;
    global $wpdb;
    $label = $label == '' ? ucfirst($name) : $label;
    $attribute_id = get_attribute_id_from_name($name);
    if (empty($attribute_id)) {
        $attribute_id = NULL;
    } else {
        $set = false;
    }
    $args = array(
        'attribute_id' => $attribute_id,
        'attribute_name' => $name,
        'attribute_label' => $label,
        'attribute_type' => 'select',
        'attribute_orderby' => 'menu_order',
        'attribute_public' => 0,
    );
    if (empty($attribute_id))
        $wpdb->insert("{$wpdb->prefix}woocommerce_attribute_taxonomies", $args);
    if ($set) {
        $attributes = wc_get_attribute_taxonomies();
        $args['attribute_id'] = get_attribute_id_from_name($name);
        $attributes[] = (object) $args;
//print_pr($attributes);
        set_transient('wc_attribute_taxonomies', $attributes);
    } else {
        return;
    }
}
/**
 * Get the product attribute ID from the name.
 *
 * @since 3.0.0
 * @param string $name | The name (slug).
 */
function get_attribute_id_from_name($name) {
    global $wpdb;
    $attribute_id = $wpdb->get_col("SELECT attribute_id
FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
WHERE attribute_name LIKE '$name'");
    return reset($attribute_id);
}
// add these to work add image function
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
function getImage($postId,$thumb_url,$imageDescription){
    $tmp = download_url($thumb_url);
    preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
    $file_array['name'] = basename($matches[0]);
    $file_array['tmp_name'] = $tmp;
// If error storing temporarily, unlink
    $logtxt = '';
    if (is_wp_error($tmp)) {
        @unlink($file_array['tmp_name']);
        $file_array['tmp_name'] = '';
        return;
    }else{
        $logtxt .= "download_url: $tmp\n";
    }
//use media_handle_sideload to upload img:
    $thumbid = media_handle_sideload( $file_array, $postId, $imageName ); //'gallery desc'
// If error storing permanently, unlink
    if (is_wp_error($thumbid)) {
        @unlink($file_array['tmp_name']);
        $thumbid = (string)$thumbid;
        $logtxt .= "Error: media_handle_sideload error - $thumbid\n";
    }else{
        $logtxt .= "ThumbID: $thumbid\n";
    }
    set_post_thumbnail($postId, $thumbid);
}