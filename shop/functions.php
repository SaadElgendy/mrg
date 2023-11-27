<?php
// add custom urls for product and products templates
if ( ! function_exists( 'shop_templates_url_init' )) {
    function shop_templates_url_init() {
        add_rewrite_tag('%product-id%','([^/]+)');
        add_rewrite_rule('^product-id/([^/]+)/?','index.php?product-id=$matches[1]', 'top');
        add_rewrite_tag('%products-list%','([^/]+)');
        add_rewrite_rule('^products-list/([^/]+)/?','index.php?products-list=$matches[1]', 'top');
        add_rewrite_tag('%thank-you%','([^/]+)');
        add_rewrite_rule('^thank-you/([^/]+)/?','index.php?thank-you=$matches[1]', 'top');
    }
    add_action('init', 'shop_templates_url_init');
}
// require product and products templates
if ( ! function_exists( 'shop_custom_templates' )) {
    function shop_custom_templates($template) {
        $path = false;
        if ( get_query_var('product-id', null) !== null ) {
            $path = '/shop/product.php';
        }
        if ( get_query_var('products-list', null) !== null ) {
            $path = '/shop/products.php';
        }
        if ( get_query_var('thank-you', null) !== null ) {
            $path = '/shop/thank-you.php';
        }
        if ($path) {
            $template = get_template_directory() . $path;
        }
        return $template;
    }
    add_filter('template_include', 'shop_custom_templates', 50);
}

if ( ! function_exists( 'get_product_data' )) {
    function get_product_data($product, $productId) {
        $productData = array();
        $productData['title'] = isset($product['title']) ? $product['title'] : '';
        $productData['desc'] = isset($product['description']) ? $product['description'] : '';
        $productData['price'] = isset($product['fullPrice']) ? str_replace('$', '_dollar_symbol_', $product['fullPrice']) : '';
        $productData['price_old'] = isset($product['fullPriceOld']) ? str_replace('$', '_dollar_symbol_', $product['fullPriceOld']) : '';
        $productData['images'] = isset($product['images']) ? $product['images'] : array();
        $productData['image_url'] = isset($product['images']) && count($product['images']) > 0 ? array_shift($product['images'])['url'] : '';
        $productData['productUrl'] = $productId ? home_url('?product-id=' . $productId) : '#';
        $productData['add_to_cart_text'] = 'Add to Cart';
        $productData['product-is-new'] = getProductIsNew($product);
        $productData['product-sale'] = getProductSale($product);
        $productData['categories'] = getProductCategories($product);
        return $productData;
    }
}

if ( ! function_exists( 'getProductIsNew' )) {
    /**
     * Product is new
     */
    function getProductIsNew($product) {
        $currentDate = (int) (microtime(true) * 1000);
        if (isset($product['created'])) {
            $createdDate = (int) $product['created'];
        } else {
            $createdDate = $currentDate;
        }
        $milliseconds30Days = 30 * (60 * 60 * 24 * 1000); // 30 days in milliseconds
        if (($currentDate - $createdDate) <= $milliseconds30Days) {
            return true;
        }
        return false;
    }
}

if ( ! function_exists( 'getProductSale' )) {
    /**
     * Sale for product
     */
    function getProductSale($product) {
        $price = 0;
        if ( isset($product['price']) ) {
            $price = extractNumber($product['price']);
        }
        $oldPrice = 0;
        if ( isset($product['fullPriceOld']) ) {
            $oldPrice = extractNumber($product['fullPriceOld']);
        }
        $sale = '';
        if ( $price && $oldPrice && $price < $oldPrice ) {
            $sale = '-' . (int) ( 100 - ( $price * 100 / $oldPrice ) ) . '%';
        }
        return $sale;
    }
}

if ( ! function_exists( 'extractNumber' )) {
    function extractNumber($string) {
        preg_match('/[\d\.]+/', $string, $matches);
        return isset($matches[0]) ? (float) $matches[0] : 0;
    }
}

if ( ! function_exists( 'getProductCategories' )) {
    /**
     * Get product categories
     *
     * @return array $categories
     */
    function getProductCategories($product) {
        $categories = array(
            0 => array(
                'id' => 0,
                'title' => 'Uncategorized',
                'link' => '#',
            )
        );
        $data = array();
        if (file_exists(get_template_directory() . '/shop/products.json')) {
            $data = file_get_contents(get_template_directory() . '/shop/products.json');
            $data = json_decode($data, true);
        }
        if (!$data) {
            return $categories;
        }
        $all_categories = isset($data['categories']) ? $data['categories'] : array();
        $product_categories = isset($product['categories']) ? $product['categories'] : array();
        if ($product_categories) {
            $categories = array();
            foreach ($product_categories as $id) {
                $category = findElementById($all_categories, $id);
                if ($category) {
                    array_push(
                        $categories,
                        array(
                            'id'    => $id,
                            'title' => isset($category['title']) ? $category['title'] : 'Uncategorized',
                            'link'  => home_url('?products-list&category_id=' . $id),
                        )
                    );
                }
            }
        }
        return $categories;
    }
}

if ( ! function_exists( 'findElementById' )) {
    function findElementById($all_categories, $cat_id) {
        foreach ($all_categories as $element) {
            if ($element['id'] == $cat_id) {
                return $element;
            }
        }
        return null;
    }
}

if ( ! function_exists( 'filterProductsByCategory' )) {
    function filterProductsByCategory($products, $category_id) {
        $productsData = array();
        foreach ($products as $product) {
            foreach ($product['categories'] as $category) {
                if ($category == $category_id) {
                    array_push($productsData, $product);
                    break;
                }
            }
        }
        return $productsData;
    }
}
