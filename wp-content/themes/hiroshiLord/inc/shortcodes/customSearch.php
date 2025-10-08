<?php
// Search Suggestions với Ajax
add_action('wp_footer', 'add_search_suggestions_script');
function add_search_suggestions_script() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var searchInput = $('.searchform input[type="search"], .search-field, .header-search input');
            var suggestionsContainer = $('<div class="search-suggestions"></div>');

            // Thêm container suggestions sau search input
            searchInput.each(function() {
                $(this).closest('.searchform, .search-wrapper, .header-search').css('position', 'relative');
                $(this).after(suggestionsContainer.clone());
            });

            // Khi focus vào search input
            searchInput.on('focus', function() {
                var $this = $(this);
                var $suggestions = $this.siblings('.search-suggestions');

                if ($this.val() === '') {
                    loadPopularProducts($suggestions);
                }
                $suggestions.show();
            });

            // Khi click ra ngoài
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.searchform, .search-wrapper, .header-search, .search-suggestions').length) {
                    $('.search-suggestions').hide();
                }
            });

            // Load sản phẩm phổ biến
            function loadPopularProducts($container) {
                $container.html('<div class="suggestions-loading">Loading...</div>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'get_popular_products',
                        nonce: '<?php echo wp_create_nonce('search_suggestions_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="suggestions-header">Tool phổ biến:</div>';
                            html += '<div class="suggestions-list">';

                            $.each(response.data, function(index, product) {
                                html += '<div class="suggestion-item" data-url="' + product.url + '">';
                                html += '<img src="' + product.image + '" alt="' + product.title + '">';
                                html += '<div class="suggestion-info">';
                                html += '<div class="suggestion-title">' + product.title + '</div>';
                                html += '<div class="suggestion-price">' + product.price + '</div>';
                                html += '</div>';
                                html += '</div>';
                            });

                            html += '</div>';
                            $container.html(html);
                        }
                    }
                });
            }

            // Click vào suggestion item
            $(document).on('click', '.suggestion-item', function() {
                window.location.href = $(this).data('url');
            });

            // Live search khi gõ
            var searchTimeout;
            searchInput.on('input', function() {
                var $this = $(this);
                var $suggestions = $this.siblings('.search-suggestions');
                var query = $this.val();

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if (query.length >= 2) {
                        liveSearch(query, $suggestions);
                    } else if (query.length === 0) {
                        loadPopularProducts($suggestions);
                    }
                }, 300);
            });

            // Live search function
            function liveSearch(query, $container) {
                $container.html('<div class="suggestions-loading">Searching...</div>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'live_search_products',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('search_suggestions_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="suggestions-header">Result Search:</div>';
                            html += '<div class="suggestions-list">';

                            if (response.data.length > 0) {
                                $.each(response.data, function(index, product) {
                                    html += '<div class="suggestion-item" data-url="' + product.url + '">';
                                    html += '<img src="' + product.image + '" alt="' + product.title + '">';
                                    html += '<div class="suggestion-info">';
                                    html += '<div class="suggestion-title">' + product.title + '</div>';
                                    html += '<div class="suggestion-price">' + product.price + '</div>';
                                    html += '</div>';
                                    html += '</div>';
                                });

                                // Thêm link "Xem tất cả"
                                html += '<div class="view-all-results">';
                                html += '<a href="<?php echo home_url(); ?>/?s=' + encodeURIComponent(query) + '&post_type=product">Xem tất cả kết quả (' + response.data.length + '+)</a>';
                                html += '</div>';
                            } else {
                                html += '<div class="no-suggestions">Not found tools</div>';
                            }

                            html += '</div>';
                            $container.html(html);
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
}

// Ajax handler cho popular products
add_action('wp_ajax_get_popular_products', 'get_popular_products_ajax');
add_action('wp_ajax_nopriv_get_popular_products', 'get_popular_products_ajax');
function get_popular_products_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'search_suggestions_nonce')) {
        wp_die('Security check failed');
    }

    // Query sản phẩm bán chạy
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 8,
        'meta_key' => 'total_sales',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_stock_status',
                'value' => 'instock'
            )
        )
    );

    $products = get_posts($args);

    // Nếu không có sản phẩm bán chạy, lấy sản phẩm mới nhất
    if (empty($products)) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 8,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_stock_status',
                    'value' => 'instock'
                )
            )
        );
        $products = get_posts($args);
    }

    $results = array();

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        if ($product && $product->is_visible()) {
            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
            if (!$image_url) {
                $image_url = wc_placeholder_img_src('thumbnail');
            }

            $results[] = array(
                'title' => $product->get_name(),
                'url' => get_permalink($product->get_id()),
                'image' => $image_url,
                'price' => $product->get_price_html()
            );
        }
    }

    wp_send_json_success($results);
}

// Ajax handler cho live search
add_action('wp_ajax_live_search_products', 'live_search_products_ajax');
add_action('wp_ajax_nopriv_live_search_products', 'live_search_products_ajax');
function live_search_products_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'search_suggestions_nonce')) {
        wp_die('Security check failed');
    }

    $query = sanitize_text_field($_POST['query']);

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 8,
        's' => $query,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_stock_status',
                'value' => 'instock'
            )
        )
    );

    $products = get_posts($args);
    $results = array();

    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);
        if ($product && $product->is_visible()) {
            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
            if (!$image_url) {
                $image_url = wc_placeholder_img_src('thumbnail');
            }

            $results[] = array(
                'title' => $product->get_name(),
                'url' => get_permalink($product->get_id()),
                'image' => $image_url,
                'price' => $product->get_price_html()
            );
        }
    }

    wp_send_json_success($results);
}

// Lưu search history
add_action('init', function() {
    if (is_search() && !empty(get_search_query())) {
        $searches = get_transient('recent_searches') ?: array();
        array_unshift($searches, get_search_query());
        $searches = array_unique(array_slice($searches, 0, 5));
        set_transient('recent_searches', $searches, WEEK_IN_SECONDS);
    }
});