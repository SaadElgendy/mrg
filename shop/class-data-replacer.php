<?php
defined('ABSPATH') or die;

class ShopDataReplacer {

    public static $images = array();
    public static $products = array();
    public static $allProducts = array();
    public static $productId = 0;
    public static $productData = array();

    /**
     * ShopDataReplacer process.
     *
     * @param string $content
     *
     * @return string $content
     */
    public static function process($content, $products, $productId) {
        if (count($products) < 1) {
            return '';
        }
        if ($productId) {
            self::$productId = $productId;
        }
        self::$products = array_combine(array_column($products, 'id'), $products);
        self::$allProducts = self::$products;
        $content = self::_processProducts($content);
        return $content;
    }

    /**
     * Process products
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _processProducts($content) {
        $content = self::_processProductsListTemplate($content);
        $content = self::_processProductTemplate($content);
        $content = str_replace('_dollar_symbol_', '$', $content);
        return $content;
    }

    public static $typeControl;

    /**
     * Process Product template
     *
     * @param string $content Template content
     *
     * @return string|string[]|null
     */
    private static function _processProductTemplate($content) {
        return preg_replace_callback(
            '/<\!--product-->([\s\S]+?)<\!--\/product-->/',
            function ($productMatch) {
                $productHtml = $productMatch[1];
                if (count(self::$products) < 1) {
                    return '';
                }
                $product = isset(self::$products[self::$productId]) ? self::$products[self::$productId] : array();
                self::$productData = get_product_data($product, self::$productId);
                self::$typeControl = 'product';
                return self::_replaceProductItemControls($productHtml, true);
            },
            $content
        );
    }

    /**
     * Replace placeholder for product item controls
     *
     * @param string $content
     * @param bool   $single
     *
     * @return string $content
     */
    private static function _replaceProductItemControls($content, $single = false) {
        $content = self::_replaceTitle($content);
        $content = self::_replaceContent($content);
        $content = self::_replacePrice($content);
        $content = self::_replaceImage($content);
        $content = self::_replaceGallery($content);
        $content = self::_replaceButton($content);
        $content = self::_replaceBadge($content);
        $content = self::_replaceCategory($content);
        return $content;
    }

    /**
     * Replace placeholder for product title
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceTitle($content) {
        return preg_replace_callback(
            '/<!--product_title-->([\s\S]+?)<!--\/product_title-->/',
            function ($titleMatch) {
                $titleHtml = $titleMatch[1];
                $titleHtml = self::_replaceTitleUrl($titleHtml);
                $titleHtml = self::_replaceTitleContent($titleHtml);
                return $titleHtml;
            },
            $content
        );
    }

    /**
     * Replace placeholder for product title content
     *
     * @param string $content title html
     *
     * @return string $content
     */
    private static function _replaceTitleContent($content) {
        $productTitle = self::$productData['title'] ?: '';
        if (isset($productTitle) && $productTitle != '') {
            $content = preg_replace('/<!--product_title_content-->([\s\S]+?)<!--\/product_title_content-->/', $productTitle, $content);
        }
        return $content;
    }

    /**
     * Replace placeholder for product title url
     *
     * @param string $content title html
     *
     * @return string $content
     */
    private static function _replaceTitleUrl($content) {
        $productUrl = self::$productData['productUrl'] ?: '#';
        if ($productUrl) {
            $content = preg_replace('/href=[\'|"][\s\S]+?[\'|"]/', 'href="' . $productUrl . '"', $content);
        }
        return $content;
    }

    /**
     * Replace placeholder for product content
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceContent($content) {
        return preg_replace_callback(
            '/<!--product_content-->([\s\S]+?)<!--\/product_content-->/',
            function ($textMatch) {
                $textHtml = $textMatch[1];
                $productContent = self::$productData['desc'];
                if (isset($productContent) && $productContent != '') {
                    $textHtml = preg_replace('/<!--product_content_content-->([\s\S]+?)<!--\/product_content_content-->/', $productContent, $textHtml);
                }
                return $textHtml;
            },
            $content
        );
    }
    /**
     * Replace placeholder for product image
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceImage($content) {
        return preg_replace_callback(
            '/<!--product_image-->([\s\S]+?)<!--\/product_image-->/',
            function ($imageMatch) {
                $imageHtml = $imageMatch[1];
                $url = self::$productData['image_url'];
                if (!$url) {
                    return '<div class="none-post-image" style="display: none;"></div>';
                }
                $url = get_template_directory_uri() . '/' . $url;
                $isBackgroundImage = strpos($imageHtml, '<div') !== false ? true : false;
                $link = self::$productData['productUrl'] ?: '#';
                if ($isBackgroundImage) {
                    $imageHtml = str_replace('<div', '<div data-product-control="' . $link . '"', $imageHtml);
                    if (strpos($imageHtml, 'data-bg') !== false) {
                        $imageHtml = preg_replace('/(data-bg=[\'"])([\s\S]+?)([\'"])/', '$1url(' . $url . ')$3', $imageHtml);
                    } else {
                        $imageHtml = str_replace('<div', '<div' . ' style="background-image:url(' . $url . ')"', $imageHtml);
                    }
                } else {
                    $imageHtml = preg_replace('/(src=[\'"])([\s\S]+?)([\'"])/', '$1' . $url . '$3 style="cursor:pointer;" data-product-control="' . $link . '"', $imageHtml);
                }
                return $imageHtml;
            },
            $content
        );
    }

    /**
     * Replace placeholder for product price
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replacePrice($content) {
        return preg_replace_callback(
            '/<!--product_price-->([\s\S]+?)<!--\/product_price-->/',
            function ($priceHtml) {
                $priceHtml = $priceHtml[1];
                $price = self::$productData['price'] ?: '';
                if (!$price) {
                    return $priceHtml;
                }
                $price_old = self::$productData['price_old'] ?: '';
                if ($price_old == $price) {
                    $price_old = '';
                }
                $addZeroCents = strpos($priceHtml, 'data-add-zero-cents="true"') !== false ? true : false;
                $price = self::priceProcess($price, $addZeroCents);
                $price_old = $price_old ? self::priceProcess($price_old, $addZeroCents) : $price_old;
                $priceHtml = preg_replace('/<!--product_old_price_content-->([\s\S]*?)<!--\/product_old_price_content-->/', $price_old, $priceHtml);
                return preg_replace('/<!--product_regular_price_content-->([\s\S]+?)<!--\/product_regular_price_content-->/', $price, $priceHtml);
            },
            $content
        );
    }

    /**
     * Get price with/without cents
     *
     * @param string $price
     * @param bool   $addZeroCents
     *
     * @return string $price
     */
    public static function priceProcess($price, $addZeroCents) {
        $currentPrice = '0';
        if (preg_match('/\d+(\.\d+)?/', $price, $matches)) {
            $currentPrice = $matches[0];
            $price = str_replace($matches[0], '{currentPrice}', $price);
        }
        $priceParams = explode('.', $currentPrice);
        $cents = isset($priceParams[1]) ? $priceParams[1] : '00';
        if ($cents === '00') {
            $currentPrice = $priceParams[0];
        }
        if ($addZeroCents) {
            $currentPrice = $priceParams[0] . '.' . $cents;
        }
        return str_replace('{currentPrice}', $currentPrice, $price);
    }

    /**
     * Replace placeholder for product gallery
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceGallery($content) {
        return preg_replace_callback(
            '/<!--product_gallery-->([\s\S]+?)<!--\/product_gallery-->/',
            function ($galleryMatch) {
                $galleryHtml = $galleryMatch[1];
                $images = self::$productData['images'];
                if (count($images) < 1) {
                    return $galleryHtml;
                }

                $controlOptions = array();
                if (preg_match('/<\!--options_json--><\!--([\s\S]+?)--><\!--\/options_json-->/', $galleryHtml, $matches)) {
                    $controlOptions = json_decode($matches[1], true);
                    $galleryHtml = str_replace($matches[0], '', $galleryHtml);
                }

                $maxItems = -1;
                if (isset($controlOptions['maxItems']) && $controlOptions['maxItems']) {
                    $maxItems = (int) $controlOptions['maxItems'];
                }

                if ($maxItems !== -1 && count($images) > $maxItems) {
                    $images = array_slice($images, 0, $maxItems);
                }

                $galleryItemRe = '/<\!--product_gallery_item-->([\s\S]+?)<\!--\/product_gallery_item-->/';
                preg_match($galleryItemRe, $galleryHtml, $galleryItemMatch);
                $galleryItemHtml = str_replace('u-active', '', $galleryItemMatch[1]);

                $galleryThumbnailRe = '/<\!--product_gallery_thumbnail-->([\s\S]+?)<\!--\/product_gallery_thumbnail-->/';
                $galleryThumbnailHtml = '';
                if (preg_match($galleryThumbnailRe, $galleryHtml, $galleryThumbnailMatch)) {
                    $galleryThumbnailHtml = $galleryThumbnailMatch[1];
                }

                $newGalleryItemListHtml = '';
                $newThumbnailListHtml = '';
                foreach ($images as $key => $img) {
                    $url = isset($img['url']) ? $img['url'] : '';
                    $url = get_template_directory_uri() . '/' . $url;
                    $newGalleryItemHtml = $key == 0 ? str_replace('u-gallery-item', 'u-gallery-item u-active', $galleryItemHtml) : $galleryItemHtml;
                    $newGalleryItemListHtml .= preg_replace('/(src=[\'"])([\s\S]+?)([\'"])/', '$1' . $url . '$3', $newGalleryItemHtml);
                    if ($galleryThumbnailHtml) {
                        $newThumbnailHtml = preg_replace('/data-u-slide-to=([\'"])([\s\S]+?)([\'"])/', 'data-u-slide-to="' . $key . '"', $galleryThumbnailHtml);
                        $newThumbnailListHtml .= preg_replace('/(src=[\'"])([\s\S]+?)([\'"])/', '$1' . $url . '$3', $newThumbnailHtml);
                    }
                }

                $galleryParts = preg_split($galleryItemRe, $galleryHtml, -1, PREG_SPLIT_NO_EMPTY);
                $newGalleryHtml = $galleryParts[0] . $newGalleryItemListHtml . $galleryParts[1];

                $newGalleryParts = preg_split($galleryThumbnailRe, $newGalleryHtml, -1, PREG_SPLIT_NO_EMPTY);
                return $newGalleryParts[0] . $newThumbnailListHtml . $newGalleryParts[1];
            },
            $content
        );
    }

    /**
     * Replace placeholder for product button add to cart
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceButton($content) {
        return preg_replace_callback(
            '/<!--product_button-->([\s\S]+?)<!--\/product_button-->/',
            function ($buttonMatch) {
                $button_html = $buttonMatch[1];
                $current_product_data = isset(self::$allProducts[self::$productId]) ? self::$allProducts[self::$productId] : array();
                $controlOptions = array();
                if (preg_match('/<\!--options_json--><\!--([\s\S]+?)--><\!--\/options_json-->/', $button_html, $matches)) {
                    $controlOptions = json_decode($matches[1], true);
                    $button_html = str_replace($matches[0], '', $button_html);
                }
                $goToProduct = false;
                if (isset($controlOptions['clickType']) && $controlOptions['clickType'] === 'go-to-page') {
                    $goToProduct = true;
                }
                if ($current_product_data) {
                    $button_html = str_replace('data-product-id=""', 'data-product-id="' . self::$productId  . '"', $button_html);
                    if (file_exists(get_template_directory() . '/shop/products.json')) {
                        $data = file_get_contents(get_template_directory() . '/shop/products.json');
                        $data = json_decode($data, true);
                    }
                    $allCategories = isset($data['categories']) ? $data['categories'] : array();
                    $productInfo = self::$allProducts[self::$productId];
                    $productInfo['categoriesData'] = self::getCategoriesData($productInfo['categories'], $allCategories);
                    $button_html = str_replace('<a', '<a data-product="' . htmlspecialchars(json_encode($productInfo))  . '"', $button_html);
                    if ($goToProduct) {
                        return preg_replace_callback(
                            '/href=[\"\']{1}product-?(\d+)[\"\']{1}/',
                            function ($hrefMatch) {
                                return 'href="' . home_url('?product_id=' . $hrefMatch[1]) . '"';
                            },
                            $button_html
                        );
                    } else {
                        $button_html = str_replace('class="', 'class="u-add-to-cart-link ', $button_html);
                    }
                }
                return $button_html;
            },
            $content
        );
    }

    /**
     * Get CategoriesData
     *
     * @param array $productCategories
     * @param array $allCategories
     *
     * @return array $categoriesData
     */
    public static function getCategoriesData($productCategories, $allCategories) {
        $categoriesData = array();
        foreach ($productCategories as $id) {
            $category = array_filter(
                $allCategories, function ($category) use ($id) {
                return $category['id'] === $id;
            }
            );

            if (empty($category)) {
                continue;
            }
            $category = array_shift($category);
            if ($category) {
                $categoriesData[] = array(
                    'id'    => $category['id'],
                    'title' => isset($category['title']) ? $category['title'] : 'Uncategorized',
                    'link'  => home_url('?products-list&category_id=' . $id),
                );
            }
            if (empty($categoriesData)) {
                $categoriesData[] = array('id' => 0, 'title' => 'Uncategorized');
            }
        }
        return $categoriesData;
    }

    /**
     * Set product badge
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceBadge($content) {
        return preg_replace_callback(
            '/<!--product_badge-->([\s\S]+?)<!--\/product_badge-->/',
            function ($badgeMatch) {
                $badgeHtml = $badgeMatch[1];
                if (preg_match('/data-badge-source="sale"/', $badgeHtml)) {
                    if (self::$productData['product-sale']) {
                        return preg_replace_callback(
                            '/<\!--product_badge_content-->([\s\S]+?)<\!--\/product_badge_content-->/',
                            function ($badgeContentMatch) {
                                return self::$productData['product-sale'];
                            },
                            $badgeHtml
                        );
                    }
                } else {
                    if (self::$productData['product-is-new']) {
                        return $badgeHtml;
                    }
                }
                return str_replace('class="', 'class="u-hidden-block ', $badgeHtml);
            },
            $content
        );
    }

    /**
     * Replace placeholder for category
     *
     * @param string $content
     *
     * @return string $content
     */
    private static function _replaceCategory($content) {
        return preg_replace_callback(
            '/<!--product_category-->([\s\S]+?)<!--\/product_category-->/',
            function ($product_category) {
                $productCategoryHtml = $product_category[1];
                preg_match('/<a.+?>(.+?)<\/a>/', $productCategoryHtml, $productCategoriesLinks);
                $firstCategoryLinkHtml = $productCategoriesLinks[0];
                $productCategoryHtml = preg_replace('/(<div\b[^>]*>).*?(<\/div>)/s', '$1{ProductCategoriesLinks}$2', $productCategoryHtml);
                $categoriesHtml = '';
                foreach (self::$productData['categories'] as $index => $category) {
                    $doubleCategoryLinkHtml = $firstCategoryLinkHtml;
                    $category_title = isset($category['title']) ? $category['title'] : 'Uncategorized';
                    $category_link = isset($category['link']) ? $category['link'] : '#';
                    $doubleCategoryLinkHtml = preg_replace('/href=[\'"][\s\S]+?[\'"]/', 'href="' . $category_link . '"', $doubleCategoryLinkHtml);
                    $doubleCategoryLinkHtml = preg_replace('/(<a\b[^>]*>).*?(<\/a>)/s', '$1' . $category_title . '$2', $doubleCategoryLinkHtml);
                    if ($index > 0) {
                        $categoriesHtml .= ', ';
                    }
                    $categoriesHtml .= $doubleCategoryLinkHtml;
                }
                return str_replace('{ProductCategoriesLinks}', $categoriesHtml, $productCategoryHtml);
            },
            $content
        );
    }

    /**
     * Process Product List Template
     *
     * @param string $content Template content
     *
     * @return string|string[]|null
     */
    private static function _processProductsListTemplate($content) {
        return preg_replace_callback(
            '/<\!--products-->([\s\S]+?)<\!--\/products-->/',
            function ($productsMatch) {
                $productsHtml = $productsMatch[1];
                $productsOptions = array();
                $productsOptionsJson = '{}';
                if (preg_match('/<\!--products_options_json--><\!--([\s\S]+?)--><\!--\/products_options_json-->/', $productsHtml, $matches)) {
                    $productsOptionsJson = $matches[1];
                    $productsOptions = json_decode($productsOptionsJson, true);
                    $productsHtml = str_replace($matches[0], '', $productsHtml);
                }
                $productsCount = isset($productsOptions['count']) ? $productsOptions['count'] : '';
                if ($productsCount) {
                    self::$products = array_slice(self::$products, 0, $productsCount);
                }
                self::$typeControl = 'products';
                $productsHtml = self::_processProductItem($productsHtml);
                $productsHtml .= getGridAutoRowsStyles($productsOptionsJson, count(self::$products));
                return $productsHtml;
            },
            $content
        );
    }

    /**
     * Process product item
     *
     * @param string $content Template content
     *
     * @return string|string[]|null
     */
    private static function _processProductItem($content) {
        return preg_replace_callback(
            '/<\!--product_item-->([\s\S]+?)<\!--\/product_item-->/',
            function ($productMatch) {
                $productHtml = $productMatch[1];
                if (!self::$products) {
                    return '';
                }
                $product = array_shift(self::$products);
                if ($product && isset($product['id'])) {
                    self::$productData = get_product_data($product, $product['id']);
                    if (count(self::$productData) > 0) {
                        self::$productId = $product['id'];
                        $productHtml = self::_replaceProductItemControls($productHtml);
                    }
                }
                return $productHtml;
            },
            $content
        );
    }
}