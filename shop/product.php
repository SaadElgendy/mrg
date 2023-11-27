<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$language = isset($_GET['lang']) ? $_GET['lang'] : '';
add_action(
    'theme_content_styles',
    function () {
        $path = "/shop/template-parts/product-styles.php";
        if (file_exists(get_template_directory() . $path)) {
            require get_template_directory() . $path;
        }
    }
);

function shop_product_single_body_class_filter($classes) {
    $classes[] = 'u-body u-xl-mode';
    return $classes;
}
add_filter('body_class', 'shop_product_single_body_class_filter');

function shop_product_single_body_style_attribute() {
    return "";
}
add_filter('add_body_style_attribute', 'shop_product_single_body_style_attribute');

function shop_product_single_body_back_to_top() {
    ob_start(); ?>
    
    <?php
    return ob_get_clean();
}
add_filter('add_back_to_top', 'shop_product_single_body_back_to_top');


function shop_product_single_get_local_fonts() {
    return '';
}
add_filter('get_local_fonts', 'shop_product_single_get_local_fonts');

get_header();  ?>

<?php
ob_start();
$path = '/shop/template-parts/product-content.php';
if (file_exists(get_template_directory() . $path)) {
    require get_template_directory() . $path;
}
$content = ob_get_clean();

$json_path = '/shop/products.json';
if (file_exists(get_template_directory() . $json_path)) {
    $data = file_get_contents(get_template_directory() . $json_path);
    $data = json_decode($data, true);
}
if (!isset($data) || !is_array($data)) {
    $data = array();
}
if ($data && isset($data['products']) && isset($_GET['product-id']) && $_GET['product-id']) {
    require_once dirname(__FILE__) . '/class-data-replacer.php';
    $content = ShopDataReplacer::process($content, $data['products'], $_GET['product-id']);
}

if (function_exists('renderTemplate')) {
    renderTemplate($content, '', 'echo', 'custom');
} else {
    echo $content;
} ?>

<?php get_footer();
remove_action('theme_content_styles', 'theme_product_content_styles');
remove_filter('body_class', 'shop_product_single_body_class_filter');

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
