<?php
/*
  Plugin Name: #1 WooCommerce Add Simple and Variable Product
  Plugin URI:  https://rwsite.ru
  Description: Example of creating woocommerce products programmatically without API. Cyrillic support.
  Version:     1.0
  WC requires at least: 3.0
  WC tested up to: 3.6
  Author:      Aleksey Tikhomirov
  Text Domain: wc-add
  Domain Path: /languages
  Copyright: © 2015-2019 Aleksey Tikhomirov
  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if(!defined('ABSPATH')){
  exit;
}

require_once 'wc_3.php';

class WC_Add_Product {

    public function __construct()
    {
        add_shortcode('add_simple_product', [$this, 'add_simple_product']);
        add_shortcode('add_variable_product', [$this, 'create_variable_product']);

        add_action( 'wp_dashboard_setup', [$this, 'widget_add_products'] );
        add_action( 'admin_print_footer_scripts', [$this, 'add_javascript'], 99);
        add_action( 'wp_ajax_php_callback', [$this, 'php_callback'] );
    }

    /**
     * Example of create simple product
     */
    public function add_simple_product()
    {
      $objProduct = new WC_Product();
      $objProduct->set_name("Product Title");
      $objProduct->set_status("publish");  // can be publish,draft or any wordpress post status
      $objProduct->set_description("Product Description");
      $objProduct->set_price(10.55); // set product price
      $objProduct->set_regular_price(10.55); // set product regular price
      $objProduct->set_manage_stock(true); // true or false
      $objProduct->set_stock_quantity(10);
      $objProduct->set_stock_status('instock'); // in stock or out of stock value
      $objProduct->set_backorders('no');
      $objProduct->set_reviews_allowed(true);
      $objProduct->set_sold_individually(false);

      $product_id = $objProduct->save(); // it will save the product and return the generated product id
    }


    /**
     * Example of create variable product
     */
    public function create_variable_product(){
        $product_id = create_product( array(
            'type'               => 'variable',
            'name'               => __('The variable product title ' . rand(0, 99999), 'woocommerce'),
            'description'        => __("The product description…", "woocommerce"),
            'short_description'  => __("The product short description…", "woocommerce"),
            // 'sku'                => '',
            'regular_price'      => '5.00', // product price
            // 'sale_price'         => '',
            'reviews_allowed'    => true,
            'attributes'         => [
                // Taxonomy and term name values
                [
                    'name'  => 'Это длинное название атрибута Цвет',
                    //'slug' => wc_sanitize_taxonomy_name('Цвет'),
                    'options' => ['Красный', 'Синий', 'Зеленый', 'Серо-Буро-Малиновый в крапинку'],
                    'visible' => true,
                    'variation' => true,
                    //id,
                    //position
                ],
                [
                    'name'  => 'Размер',
                    //'slug' => sanitize_title( 'Размер'),
                    'options' => ['S', 'M', 'L', 'XXL'],
                    'visible' => true,
                    'variation' => false,
                ],
                [
                    'name'  => 'Состояние',
                    //'slug' => sanitize_title( 'Состояние'),
                    'options' => ['Нормальное', 'Удовлетворительное', 'Странное'],
                    'visible' => true,
                    'variation' => false,
                ],
            ],
            'default_attributes' => [
                [
                    'name'  => 'Цвет',
                    'option' => 'Red',
                ],
            ],
            'variations' => [
                    [
                            //'id'            => '',
                            'visible'         => true,
                            //'sku'           => '',
                            //'image'         => '',
                            //'virtual'       => '',
                            //'manage_stock'  =>'',
                            'in_stock'      => 'instock',
                            //'backorders'    => '',
                            //'stock_quantity'=> '',
                            //'inventory_delta'=> '',
                            'regular_price' => '10',
                            //'sale_price'    => '',
                            //'date_on_sale_from' => '',
                           // 'date_on_sale_to' => '',
                           // 'tax_class' => '',
                            'description' => 'Desc text',
                            'attributes' => array(
                                'name'      => 'Это длинное название атрибута Цвет',
                                'option'    => 'Красный'
                            )

                    ],
                    [
                        //'id'            => '',
                        'visible'         => true,
                        //'sku'           => '',
                        //'image'         => '',
                        //'virtual'       => '',
                        //'manage_stock'  =>'',
                        'in_stock'      => 'instock',
                        //'backorders'    => '',
                        //'stock_quantity'=> '',
                        //'inventory_delta'=> '',
                        'regular_price' => '20',
                        //'sale_price'    => '',
                        //'date_on_sale_from' => '',
                        // 'date_on_sale_to' => '',
                        // 'tax_class' => '',
                        'description' => 'text',
                        'attributes' => array(
                                'name'      => 'Это длинное название атрибута Цвет', //'attribute_' . sanitize_title('Цвет'),
                                'option'    => 'Синий'
                        )

                    ],
                    [
                        //'id'            => '',
                        'visible'         => true,
                        //'sku'           => '',
                        //'image'         => '',
                        //'virtual'       => '',
                        //'manage_stock'  =>'',
                        'in_stock'      => 'instock',
                        //'backorders'    => '',
                        //'stock_quantity'=> '',
                        //'inventory_delta'=> '',
                        'regular_price' => '30',
                        //'sale_price'    => '',
                        //'date_on_sale_from' => '',
                        // 'date_on_sale_to' => '',
                        // 'tax_class' => '',
                        'description' => 'text',
                        'attributes' => array(
                            'name'      => 'Это длинное название атрибута Цвет', //'attribute_' . sanitize_title('Цвет'),
                            'option'    => 'Зеленый'
                        )

                    ],
                [
                    //'id'            => '',
                    'visible'         => true,
                    //'sku'           => '',
                    //'image'         => '',
                    //'virtual'       => '',
                    //'manage_stock'  =>'',
                    'in_stock'      => 'instock',
                    //'backorders'    => '',
                    //'stock_quantity'=> '',
                    //'inventory_delta'=> '',
                    'regular_price' => '40',
                    //'sale_price'    => '',
                    //'date_on_sale_from' => '',
                    // 'date_on_sale_to' => '',
                    // 'tax_class' => '',
                    'description' => 'text',
                    'attributes' => array(
                        'name'      => 'Это длинное название атрибута Цвет', //'attribute_' . sanitize_title('Цвет'),
                        'option'    => 'Серо-Буро-Малиновый в крапинку'
                    )

                ],
            ]

        ) );
        echo $product_id;
    }

    /**
     * Add a widget to the dashboard.
     *
     * This function is hooked into the 'wp_dashboard_setup' action below.
     */
    public function widget_add_products() {
        wp_add_dashboard_widget(
            'ws_add_product',   // Widget slug.
            'Add products',  // Title.
            [$this,'show_panel']       // Display function.
        );
    }

    /**
     * Create the function to output the contents of our Dashboard Widget.
     */
    public function show_panel() {
        ?>
        <div class="row">
            <button id="add_simple_products" value="simple" name="add_simple" class="button button-primary"><?php _e('Add a simple product', 'wc-add')?></button>
            <button id="add_variable_products" value="variable" name="add_variable" class="button button-primary"><?php _e('Add variable product', 'wc-add')?></button>
        </div>
        <?php
    }

    public function add_javascript() {
        ?>
        <script>
          jQuery(document).ready(function($) {
            var data = {
              action: 'php_callback', // php function name
            };

            $('#add_simple_products').bind('click', function(){
              data.name = 'simple';
              ajax(data);
            });

            $('#add_variable_products').bind('click', function(){
              data.name = 'variable';
              ajax(data);
            });

            function ajax(data) {
              // с версии 2.8 'ajaxurl' всегда определен в админке
              jQuery.post(ajaxurl, data, function (response) {
                console.log(data);
                alert(response);
              });
            }
          });
        </script>
        <?php
    }

    public function php_callback() {
        if(!isset($_POST['name']))
          return;

        if($_POST['name'] === 'simple') {
            echo do_shortcode('[add_simple_product]');
            echo esc_html__('Product added!');
        }

        if($_POST['name'] === 'variable'){
            echo do_shortcode('[add_variable_product]');
            echo esc_html__('Variable product added!');
        }

        wp_die();
    }

}

add_action('plugins_loaded', function (){
    new WC_Add_Product();
});