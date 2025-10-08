<?php
/**
 * Shortcode: Lấy ra top 5 sản phẩm của một thương hiệu trong các danh mục sản phẩm.
 * Hiển thị dưới dạng tab với các sản phẩm được sắp xếp theo lượt xem
 * [brand_cat_tabs brand="solana" per_page="5" columns="3" orderby="date" order="DESC"]
 *
 * - brand:  slug của term trong taxonomy product_brand
 * - brand_id: ID của term (ưu tiên brand_id nếu có)
 * - per_page: số SP mỗi tab
 * - columns: số cột (để mình set grid CSS)
 * - orderby/order: sắp xếp trong từng tab
 * - use_theme_card: yes/no — dùng template mặc định của theme (wc_get_template_part) hay thẻ HTML tuỳ biến
 */
function wb_render_product_card_min($product, $use_theme_card = false, $image_size = 'woocommerce_thumbnail')
{
    if (! $product) return '';
    if ($use_theme_card) {
        ob_start();
        wc_get_template_part('content', 'product');
        return ob_get_clean();
    }

    $post_id    = $product->get_id();
    $permalink  = get_permalink($post_id);
    $title      = get_the_title($post_id);
    $thumb_html = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
    $excerpt = wp_trim_words(get_the_excerpt(), 20);
    ob_start(); ?>



    <div class="col">
        <div class="col-inner product-card">
            <div class="card-header">
                <div class="badge-icon">
                    <img src="<?php echo esc_url($thumb_html); ?>" alt="<?php echo esc_attr($title); ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                </div>
                <div>
                    <span style="font-weight:bold; font-size:16px; flex-grow:1;"><?php echo esc_html($title); ?></span>
                    <span style="font-size:16px;">🔥</span>
                </div>
            </div>
            <div class="card-body">
                <p><?php echo esc_html($excerpt); ?></p>
            </div>
            <div class="card-footer">
                <a href="<?php echo esc_url($permalink); ?>" style="text-decoration:none; color:inherit; display:inline-flex; align-items:center; gap:4px;">
                    See more <i class="fa-solid fa-arrow-right rotate-icon"></i>
                </a>
            </div>
        </div>
    </div>

<?php
    return ob_get_clean();
}
// Function để lấy hình ảnh danh mục
function get_category_image($category_id, $image_size = 'thumbnail')
{
    $thumbnail_id = get_term_meta($category_id, 'thumbnail_id', true);
    if ($thumbnail_id) {
        return wp_get_attachment_image($thumbnail_id, $image_size, false, ['class' => 'category-image']);
    }
    return '';
}

function brand_cat_tabs_top5_sc($atts)
{
    if (! function_exists('wc_get_product')) {
        return '<p>WooCommerce chưa được kích hoạt.</p>';
    }

    $atts = shortcode_atts([
        'brand'         => '',
        'brand_id'      => 0,
        'category'      => '',
        'category_id'   => 0,
        'categories'    => '', // Nhiều category slugs, cách nhau bởi dấu phẩy
        'category_ids'  => '', // Nhiều category IDs, cách nhau bởi dấu phẩy
        'per_page'      => 12,
        'columns'       => 4,
        'orderby'       => 'date',
        'order'         => 'DESC',
        'image_size'    => 'woocommerce_thumbnail',
        'use_theme_card' => 'no',
    ], $atts, 'brand_cat_tabs');

    // Lấy brand term
    $brand_term = null;
    if ($atts['brand_id']) {
        $brand_term = get_term((int) $atts['brand_id'], 'product_brand');
    } elseif ($atts['brand']) {
        $brand_term = get_term_by('slug', sanitize_title($atts['brand']), 'product_brand');
    }
    if (! $brand_term || is_wp_error($brand_term)) {
        return '<p>Không tìm thấy thương hiệu.</p>';
    }

    // Lấy category terms (BẮT BUỘC phải có)
    $category_terms = [];

    // Xử lý nhiều category IDs
    if ($atts['category_ids']) {
        $cat_ids = array_map('intval', explode(',', $atts['category_ids']));
        foreach ($cat_ids as $cat_id) {
            $term = get_term($cat_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $category_terms[] = $term;
            }
        }
    }
    // Xử lý nhiều category slugs
    elseif ($atts['categories']) {
        $cat_slugs = array_map('trim', explode(',', $atts['categories']));
        foreach ($cat_slugs as $slug) {
            $term = get_term_by('slug', sanitize_title($slug), 'product_cat');
            if ($term && !is_wp_error($term)) {
                $category_terms[] = $term;
            }
        }
    }
    // Xử lý single category (backward compatibility)
    elseif ($atts['category_id']) {
        $term = get_term((int) $atts['category_id'], 'product_cat');
        if ($term && !is_wp_error($term)) {
            $category_terms[] = $term;
        }
    } elseif ($atts['category']) {
        $term = get_term_by('slug', sanitize_title($atts['category']), 'product_cat');
        if ($term && !is_wp_error($term)) {
            $category_terms[] = $term;
        }
    }

    // Kiểm tra BẮT BUỘC phải có categories
    if (empty($category_terms)) {
        return '<p>Vui lòng chỉ định ít nhất một danh mục sản phẩm.</p>';
    }

    // Kiểm tra category terms hợp lệ
    foreach ($category_terms as $term) {
        if (is_wp_error($term)) {
            return '<p>Một hoặc nhiều danh mục không hợp lệ.</p>';
        }
    }

    $uniq          = uniqid('brand_cat_tabs_');
    $columns       = max(1, (int) $atts['columns']);
    $use_themecard = $atts['use_theme_card'] === 'yes';

    ob_start(); ?>
    <div class="wb-brand-cat-tabs" id="<?php echo esc_attr($uniq); ?>" data-columns="<?php echo esc_attr($columns); ?>">

        <div class="brand-info-header">
            <h3>Top 5 sản phẩm xem nhiều nhất - <?php echo esc_html($brand_term->name); ?></h3>
        </div>

        <div class="wb-tabs-header">
            <?php
            $first_tab = true;
            foreach ($category_terms as $cat) :
                $cat_image = function_exists('get_category_image') ? get_category_image($cat->term_id, $atts['image_size']) : '';
                $active_class = $first_tab ? 'active' : '';
                $first_tab = false;
                ?>
                <button class="wb-tab-btn <?php echo $active_class; ?>" data-target="cat-<?php echo esc_attr($cat->term_id); ?>">
                    <?php echo $cat_image; ?>
                    <span class="tab-name"><?php echo esc_html($cat->name); ?></span>
                    <span class="top-icon">🔥</span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="wb-tabs-content">
            <?php
            $first_category = true;
            foreach ($category_terms as $cat) :
                // Tạo tax_query cho từng category với brand
                $cat_tax_query = [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_brand',
                        'field'    => 'term_id',
                        'terms'    => $brand_term->term_id,
                    ],
                    [
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $cat->term_id,
                    ],
                ];

                // Query top 5 sản phẩm xem nhiều nhất của category này
                // Thử với meta_key 'views' trước
                $top_q = new WP_Query([
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => 5,
                    'orderby'        => 'meta_value_num',
                    'meta_key'       => 'views',
                    'order'          => 'DESC',
                    'tax_query'      => $cat_tax_query,
                ]);

                // Fallback với meta_key '_view_count'
                if (!$top_q->have_posts()) {
                    wp_reset_postdata();
                    $top_q = new WP_Query([
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 5,
                        'orderby'        => 'meta_value_num',
                        'meta_key'       => '_view_count',
                        'order'          => 'DESC',
                        'tax_query'      => $cat_tax_query,
                    ]);
                }

                // Fallback với comment_count
                if (!$top_q->have_posts()) {
                    wp_reset_postdata();
                    $top_q = new WP_Query([
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 5,
                        'orderby'        => 'comment_count',
                        'order'          => 'DESC',
                        'tax_query'      => $cat_tax_query,
                    ]);
                }

                // Fallback cuối với date
                if (!$top_q->have_posts()) {
                    wp_reset_postdata();
                    $top_q = new WP_Query([
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 5,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'tax_query'      => $cat_tax_query,
                    ]);
                }

                $active_class = $first_category ? 'active' : '';
                $first_category = false;
                ?>
                <div class="wb-tab-panel <?php echo $active_class; ?>" id="wb-panel-<?php echo esc_attr($uniq); ?>-cat-<?php echo esc_attr($cat->term_id); ?>">
                    <div class="category-header">
                        <h4><?php echo esc_html($cat->name); ?> - Top 5 xem nhiều nhất</h4>
                        <p>Thương hiệu: <strong><?php echo esc_html($brand_term->name); ?></strong></p>
                    </div>

                    <?php if ($top_q->have_posts()) : ?>
                        <div class="custom-products row large-columns-<?php echo esc_attr($columns); ?> medium-columns-3 small-columns-1 row-small">
                            <?php
                            $rank = 1;
                            while ($top_q->have_posts()) : $top_q->the_post();
                                global $product;

                                // Lấy view count từ nhiều nguồn
                                $view_count = get_post_meta(get_the_ID(), 'views', true);
                                if (!$view_count) {
                                    $view_count = get_post_meta(get_the_ID(), '_view_count', true);
                                }
                                if (!$view_count) {
                                    $view_count = get_comments_number(get_the_ID());
                                }
                            ?>
                                <div class="product-wrapper rank-<?php echo $rank; ?>">
                                    <div class="rank-badge">
                                        <span class="rank-number">#<?php echo $rank; ?></span>
                                        <?php if ($view_count) : ?>
                                            <span class="view-count"><?php echo number_format($view_count); ?> lượt xem</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php echo wb_render_product_card_min($product, $use_themecard, $atts['image_size']); ?>
                                </div>
                                <?php $rank++; ?>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        </div>
                    <?php else : ?>
                        <div class="no-products-message">
                            <p>Không có sản phẩm nào trong danh mục <strong><?php echo esc_html($cat->name); ?></strong> của thương hiệu <strong><?php echo esc_html($brand_term->name); ?></strong>.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        (function($) {
            $(document).on('click', '#<?php echo esc_js($uniq); ?> .wb-tab-btn', function(e) {
                e.preventDefault();
                var $wrap = $('#<?php echo esc_js($uniq); ?>');
                var target = $(this).data('target');

                $wrap.find('.wb-tab-btn').removeClass('active');
                $(this).addClass('active');

                $wrap.find('.wb-tab-panel').removeClass('active');
                $('#wb-panel-<?php echo esc_js($uniq); ?>-' + target).addClass('active');
            });
        })(jQuery);
    </script>

    <style>
        .wb-brand-cat-tabs .brand-info-header {
            margin-bottom: 20px;
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b35, #ff8e35);
            color: white;
            border-radius: 8px;
        }

        .wb-brand-cat-tabs .brand-info-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .wb-brand-cat-tabs .wb-tabs-header {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .wb-brand-cat-tabs .wb-tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border: 2px solid #ddd;
            background: #f8f9fa;
            color: #333;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .wb-brand-cat-tabs .wb-tab-btn:hover {
            background: #e9ecef;
            border-color: #ff6b35;
        }

        .wb-brand-cat-tabs .wb-tab-btn.active {
            background: linear-gradient(135deg, #ff6b35, #ff8e35);
            color: white;
            border-color: #ff6b35;
        }

        .wb-brand-cat-tabs .wb-tab-btn .top-icon {
            font-size: 16px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .wb-brand-cat-tabs .wb-tab-btn .tab-name {
            font-size: 14px;
        }

        .wb-brand-cat-tabs .category-header {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #ff6b35;
        }

        .wb-brand-cat-tabs .category-header h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 18px;
        }

        .wb-brand-cat-tabs .category-header p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .wb-brand-cat-tabs .wb-tab-panel {
            display: none;
        }

        .wb-brand-cat-tabs .wb-tab-panel.active {
            display: block;
        }

        .wb-brand-cat-tabs .product-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .wb-brand-cat-tabs .rank-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            z-index: 10;
            background: linear-gradient(45deg, #ff6b35, #ff8e35);
            color: white;
            padding: 5px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .wb-brand-cat-tabs .rank-badge .rank-number {
            display: block;
            font-size: 14px;
            line-height: 1;
        }

        .wb-brand-cat-tabs .rank-badge .view-count {
            display: block;
            font-size: 10px;
            opacity: 0.9;
            margin-top: 2px;
        }

        .wb-brand-cat-tabs .rank-1 .rank-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #333;
        }

        .wb-brand-cat-tabs .rank-2 .rank-badge {
            background: linear-gradient(45deg, #c0c0c0, #e5e5e5);
            color: #333;
        }

        .wb-brand-cat-tabs .rank-3 .rank-badge {
            background: linear-gradient(45deg, #cd7f32, #daa520);
            color: white;
        }

        .wb-brand-cat-tabs .no-products-message {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .wb-brand-cat-tabs .no-products-message p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .wb-brand-cat-tabs .wb-tabs-header {
                flex-direction: column;
            }

            .wb-brand-cat-tabs .wb-tab-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('brand_two_cat_tabs', 'brand_cat_tabs_top5_sc');