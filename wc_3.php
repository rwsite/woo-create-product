<?php
/**
 * @version 1.0.0
 * @package woo-to-iiko
 * @author Aleksey Tikhomirov
 * @copyright 25.05.2019
 */


/** Custom function for product creation (For Woocommerce 3+ only) **/
function create_product($args)
{
    global $woocommerce;

    if (!function_exists('wc_get_product_object_type') && !function_exists('wc_prepare_product_attributes')) {
        return false;
    }

    // Create product by types
    if (false === $product = wc_get_product_object_type($args)) {
        return false;
    }

    //console_log('2 step');
    $id = $product->save();
    // Product name (Title) and slug
    $product->set_name('Variable product id#'.$id); // Name (title).
    if (isset($args['slug'])) {
        $product->set_name($args['slug']);
    }

    // Description and short description:
    $product->set_description($args['description']);
    $product->set_short_description($args['short_description']);

    // Status ('publish', 'pending', 'draft' or 'trash')
    $product->set_status(isset($args['status']) ? $args['status'] : 'publish');

    // Visibility ('hidden', 'visible', 'search' or 'catalog')
    $product->set_catalog_visibility(isset($args['visibility']) ? $args['visibility'] : 'visible');

    // Featured (boolean)
    $product->set_featured(isset($args['featured']) ? $args['featured'] : false);

    // Virtual (boolean)
    $product->set_virtual(isset($args['virtual']) ? $args['virtual'] : false);

    // Prices
    $product->set_regular_price($args['regular_price']);
    $product->set_sale_price(isset($args['sale_price']) ? $args['sale_price'] : '');
    $product->set_price(isset($args['sale_price']) ? $args['sale_price'] : $args['regular_price']);
    if (isset($args['sale_price'])) {
        $product->set_date_on_sale_from(isset($args['sale_from']) ? $args['sale_from'] : '');
        $product->set_date_on_sale_to(isset($args['sale_to']) ? $args['sale_to'] : '');
    }

    // Downloadable (boolean)
    $product->set_downloadable(isset($args['downloadable']) ? $args['downloadable'] : false);
    if (isset($args['downloadable']) && $args['downloadable']) {
        $product->set_downloads(isset($args['downloads']) ? $args['downloads'] : array());
        $product->set_download_limit(isset($args['download_limit']) ? $args['download_limit'] : '-1');
        $product->set_download_expiry(isset($args['download_expiry']) ? $args['download_expiry'] : '-1');
    }

    // Taxes
    if (get_option('woocommerce_calc_taxes') === 'yes') {
        $product->set_tax_status(isset($args['tax_status']) ? $args['tax_status'] : 'taxable');
        $product->set_tax_class(isset($args['tax_class']) ? $args['tax_class'] : '');
    }

    // SKU and Stock (Not a virtual product)
    if (isset($args['virtual']) && !$args['virtual']) {
        $product->set_sku(isset($args['sku']) ? $args['sku'] : '');
        $product->set_manage_stock(isset($args['manage_stock']) ? $args['manage_stock'] : false);
        $product->set_stock_status(isset($args['stock_status']) ? $args['stock_status'] : 'instock');
        if (isset($args['manage_stock']) && $args['manage_stock']) {
            $product->set_stock_status($args['stock_qty']);
            $product->set_backorders(isset($args['backorders']) ? $args['backorders'] : 'no'); // 'yes', 'no' or 'notify'
        }
    }

    // Sold Individually
    $product->set_sold_individually(isset($args['sold_individually']) ? $args['sold_individually'] : false);

    // Weight, dimensions and shipping class
    $product->set_weight(isset($args['weight']) ? $args['weight'] : '');
    $product->set_length(isset($args['length']) ? $args['length'] : '');
    $product->set_width(isset($args['width']) ? $args['width'] : '');
    $product->set_height(isset($args['height']) ? $args['height'] : '');
    if (isset($args['shipping_class_id'])) {
        $product->set_shipping_class_id($args['shipping_class_id']);
    }

    // Upsell and Cross sell (IDs)
    $product->set_upsell_ids(isset($args['upsells']) ? $args['upsells'] : '');
    $product->set_cross_sell_ids(isset($args['cross_sells']) ? $args['upsells'] : '');

    // Attributes et default attributes
    if (isset($args['attributes'])) {
        //set_attr($product, $args);
    }

    // default_attributes
    if (isset($args['default_attributes'])) {
        // Save default attributes for variable products.
        if ($product->is_type('variable')) {
            //$product = save_default_attributes($product, $args['default_attributes']);
        }
    }

    if ($product->is_type('variable')) {
        $_POST['post_id'] = $product->get_id();
        //WC_AJAX::link_all_variations();
        //save_variations_data($product, $args, $single_variation = false);
        variables_and_variations($product, $args);
    }

    // Reviews, purchase note and menu order
    $product->set_reviews_allowed($args['reviews'] ?? false);
    $product->set_purchase_note($args['note'] ?? '');
    if (isset($args['menu_order'])) {
        $product->set_menu_order($args['menu_order']);
    }

    // Product categories and Tags
    if (isset($args['category_ids'])) {
        $product->set_category_ids('category_ids');
    }
    if (isset($args['tag_ids'])) {
        $product->set_tag_ids('tag_ids');
    }


    // Images and Gallery
    $product->set_image_id(isset($args['image_id']) ? $args['image_id'] : "");
    $product->set_gallery_image_ids(isset($args['gallery_ids']) ? $args['gallery_ids'] : array());

    ## --- SAVE PRODUCT --- ##
    $product_id = $product->save();

    //console_log('End save product: ' . $product_id);

    return $product_id;
}

/**
 * Utility function that returns the correct product object instance
 *
 * @param $args
 *
 * @return bool|WC_Product_External|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable
 */
function wc_get_product_object_type($args)
{
    // console_log(__FUNCTION__ . 'is start');
    // Get an instance of the WC_Product object (depending on his type)
    if (isset($args['type']) && $args['type'] === 'variable') {
        $product = new WC_Product_Variable();
    } elseif (isset($args['type']) && $args['type'] === 'grouped') {
        $product = new WC_Product_Grouped();
    } elseif (isset($args['type']) && $args['type'] === 'external') {
        $product = new WC_Product_External();
    } else {
        $product = new WC_Product_Simple(); // "simple" By default
    }

    if ($product instanceof WC_Product) {
        // console_log(__FUNCTION__ . 'be end success!' . $product);
        return $product;
    } else {
        // console_log(__FUNCTION__ . 'be end Error!' . $product);
        return false;
    }
}


/**
 * @param  WC_Product  $product
 * @param $data array - Данные о товаре
 *
 * @return array|WP_Error
 * @throws WC_Data_Exception
 */
function variables_and_variations(WC_Product $product, $data)
{
    global $wp_taxonomies;
    global $wc_product_attributes;


    $attributes = [];
    foreach ($data['attributes'] as $taxonomy) {

        $tax_name = trim($taxonomy['name']);
        $taxonomy_name = wc_attribute_taxonomy_name(mb_strimwidth($taxonomy['name'], 0,
            20)); // 'pa_' . sanitize_title( 'Цвет');
        $attribute_slug = wc_attribute_taxonomy_slug($taxonomy_name); // without pa_
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_slug);

        if (0 === $attribute_id) { // ! $real_tax_name !== $iiko_tax

            $attribute_id = wc_create_attribute([
                'name'         => $tax_name,
                'slug'         => $attribute_slug,
                'type'         => 'select',
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
            if (is_wp_error($attribute_id)) {
                echo 'create attr error: '.$attribute_id->get_error_message();
            }

            // Register as taxonomy while importing.
            $wp_tax = register_taxonomy(
                $taxonomy_name,
                apply_filters('woocommerce_taxonomy_objects_'.$taxonomy_name, array('product')),
                apply_filters(
                    'woocommerce_taxonomy_args_'.$taxonomy_name,
                    [
                        'labels'       => [
                            'name' => $tax_name,
                        ],
                        'hierarchical' => true,
                        'show_ui'      => false,
                        'query_var'    => true,
                        'rewrite'      => false,
                    ]
                )
            );

            if (is_wp_error($wp_tax)) {
                echo 'register_taxonomy error:'.$wp_tax->get_error_message();
            }

            $wc_product_attributes = [];
            foreach (wc_get_attribute_taxonomies() as $tax) {
                $wc_product_attributes[$taxonomy_name] = $tax;
            }

            // Запишем в глобальный регистр. иначе taxonomy_exist сработает неправильно!
            $wp_taxonomies[$taxonomy_name] = new WP_Taxonomy($taxonomy_name, 'product', []);
        }


        $attribute = new WC_Product_Attribute();
        $attribute->set_id($attribute_id);
        $attribute->set_name($taxonomy_name);
        $attribute->set_options($taxonomy['options']);// explode(WC_DELIMITER, 'Green | Red')
        $attribute->set_visible($taxonomy['visible']);
        $attribute->set_variation($taxonomy['variation']);
        $attributes[] = $attribute;
        $product->set_attributes($attributes);
        $product->save();

        // create terms
        foreach ($taxonomy['options'] as $term_value) {
            $term_slug = sanitize_title($term_value);
            // Check if the term exist and if not it create it (and get the term ID).
            if (!term_exists($term_slug, $taxonomy_name)) { // is term !exists. создаем новый
                $term_data = wp_insert_term($term_value, $taxonomy_name, ['slug' => sanitize_title($term_value)]);
                if (is_wp_error($term_data)) {
                    echo 'wp_insert_term: '.$term_data->get_error_message();
                }
                $term_id = $term_data['term_id'];
                $term = get_term($term_id, $taxonomy_name);
            } else {
                $term = get_term_by('slug', $term_slug, $taxonomy_name);
            }
            if ($term instanceof WP_Error) {
                echo 'get_term error: '.$term->get_error_message();
            }

            if (taxonomy_exists($taxonomy_name)) {
                $result = wp_set_object_terms($product->get_id(), [$term->term_id], $taxonomy_name,
                    true); // после установки аттрибута
                if ($result instanceof WP_Error) {
                    echo 'wp_set_post_terms: '.$term->get_error_message();
                }
            } else {
                echo 'taxonomy not found';
            }

        }
    }


    /** Создание вариаций */
    $i = 0;
    unset($tax_name);
    foreach ($data['variations'] as $var) {

        $name = 'Вариация '.$i++.' для товара '.$product->get_title();
        $tax_name = wc_attribute_taxonomy_name(mb_strimwidth(trim($var['attributes']['name']), 0, 20));//

        $term = get_term_by('name', $var['attributes']['option'], $tax_name); // @codingStandardsIgnoreLine
        if ($term && !is_wp_error($term)) {
            $attribute_value = $term->slug;
        } else {
            $attribute_value = sanitize_title($var['attributes']['option']);
        }

        $variation = new WC_Product_Variation();
        $variation->set_name($name);
        $variation->set_parent_id($product->get_id());
        //$variation->set_price($var['regular_price']);
        $variation->set_regular_price($var['regular_price']);
        //$variation->set_sku( rand(0,99999) );
        $variation->set_manage_stock('no');
        $variation->set_downloadable('no');
        $variation->set_virtual('no');
        $variation->set_stock_status($var['in_stock'] ?? 'instock');
        $variation->set_attributes([
            $tax_name => $attribute_value, // ['attributes']['name'] require prefix "pa_" => attr slug
        ]);
        $variation->save();
        //test_logger($variation->get_variation_attributes());
    }

}
