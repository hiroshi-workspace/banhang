<?php

/**
 * Template Name: Page Tìm Kiếm (Auto Woo/Post)
 * Description: Nếu có WooCommerce -> tìm SẢN PHẨM. Nếu không -> dùng layout Posts archive 3 column của Flatsome.
 */
defined('ABSPATH') || exit;

get_header();

/* --- Canonical: chuyển ?s= sang ?q= để tránh is_search() --- */
if (isset($_GET['s']) && $_GET['s'] !== '') {
  $canonical = add_query_arg('p', urlencode(wp_unslash($_GET['s'])), get_permalink());
  wp_safe_redirect($canonical, 301);
  exit;
}

/* --- Lấy keyword từ ?q= --- */
$keyword_raw = isset($_GET['p']) ? wp_unslash($_GET['p']) : '';
$keyword     = sanitize_text_field($keyword_raw);

/* --- Trang hiện tại --- */
$paged = max(
  1,
  get_query_var('paged') ? intval(get_query_var('paged')) : (isset($_GET['paged']) ? intval($_GET['paged']) : 1)
);

/* --- Woo hay không --- */
$is_woo = class_exists('WooCommerce') && function_exists('wc_get_template_part');

/* --- Query builder --- */
if ($is_woo) {
  // Tìm SẢN PHẨM (Woo)
  $args = [
    'post_type'           => 'product',
    'post_status'         => 'publish',
    's'                   => $keyword,
    'posts_per_page'      => 12,
    'ignore_sticky_posts' => true,
    'paged'               => $paged,
  ];
  $page_title = function_exists('pll__') ? pll__('Kết quả tìm kiếm sản phẩm') : 'Kết quả tìm kiếm sản phẩm';
} else {
  // Tìm BÀI VIẾT (Flatsome archive 3 column)
  $args = [
    'post_type'           => ['post'],
    'post_status'         => 'publish',
    's'                   => $keyword,
    'posts_per_page'      => 10,
    'ignore_sticky_posts' => true,
    'paged'               => $paged,
  ];
  $page_title = function_exists('pll__') ? pll__('Kết quả tìm kiếm') : 'Kết quả tìm kiếm';
}

$q = new WP_Query($args);
?>

<div class="container">
  <header class="page-title shop-page-title">
    <div class="page-title-inner flex-row medium-flex-wrap">
      <div class="flex-col flex-grow">
        <h1 class="uppercase pt pb text-left">
          <?php
          echo esc_html($page_title);
          echo $keyword ? ': “' . esc_html($keyword) . '”' : '';
          ?>
        </h1>
        <?php if ($q->found_posts): ?>
          <p class="text-left d-none mb-0">
            <?php
            $fmt = $is_woo ? __('Tìm thấy %1$s sản phẩm', 'flatsome') : __('Tìm thấy %1$s kết quả', 'flatsome');
            printf(esc_html($fmt), number_format_i18n($q->found_posts));
            ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <div class="row align-center">
    <div class="large-12 col">

      <?php if ($q->have_posts()): ?>

        <?php if ($is_woo): /* ================== WOO (SẢN PHẨM) ================== */ ?>

          <?php if (function_exists('wc_set_loop_prop')) wc_set_loop_prop('columns', 4); ?>
          <?php if (function_exists('woocommerce_product_loop_start')) woocommerce_product_loop_start(); ?>

          <?php while ($q->have_posts()): $q->the_post(); ?>
            <?php
            // Dùng card mặc định nội dung sản phẩm của theme/Woo
            wc_get_template_part('content', 'product');
            ?>
          <?php endwhile; ?>

          <?php if (function_exists('woocommerce_product_loop_end')) woocommerce_product_loop_end(); ?>

        <?php else: /* ============ POSTS (Flatsome Posts archive 3 column) ============ */ ?>

          <?php
          // Ép số cột archive = 3 cho riêng trang này
          add_filter('theme_mod_blog_posts_columns', function ($val) {
            return is_page() ? 3 : $val;
          });

          // Flatsome archive đọc global $wp_query -> tạm gán $q vào đó
          global $wp_query;
          $backup_wp_query = $wp_query;
          $wp_query = $q;

          if (have_posts()) : ?>
            <div id="post-list">
              <?php
              $ids = array();
              while (have_posts()) : the_post();
                array_push($ids, get_the_ID());
              endwhile; // end of the loop.
              $ids = implode(',', $ids);
              ?>

              <?php
              echo flatsome_apply_shortcode('blog_posts', array(
                'style'     => 'default',
                'columns'     => '3',
                'col_spacing' => 'large',
                'title_size'     => 'xlarge',
                'show_date'     => 'text',
                "show_category" => "text",
                'comments'     => 'false',

                "text_bg" => "rgb(247, 247, 247)",
                'text_padding'     => "1rem 1.6rem 1rem 1.6rem",
                'class'     => 'obelix-blog-list--nine',
                "image_height" => "87%",
                "image_size" => "large",
                'text_align'  => get_theme_mod('blog_posts_title_align', 'left'),
                'type'        => get_theme_mod('blog_style_type', 'masonry'),
                'depth'       => get_theme_mod('blog_posts_depth', 0),
                'depth_hover' => get_theme_mod('blog_posts_depth_hover', 0),
                'ids'         => $ids
              ));
              ?>

              <?php flatsome_posts_pagination(); ?>
            </div>
          <?php else : ?>

            <?php get_template_part('template-parts/posts/content', 'none'); ?>

          <?php endif;

          // Khôi phục
          $wp_query = $backup_wp_query;
          wp_reset_postdata();
          ?>

        <?php endif; ?>

        <?php
        // Phân trang thủ công theo $q
        $big = 999999999;
        $pagination = paginate_links([
          'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
          'format'    => '?paged=%#%',
          'current'   => $paged,
          'total'     => $q->max_num_pages,
          'prev_text' => '«',
          'next_text' => '»',
          'type'      => 'list',
        ]);
        if ($pagination) {
          echo '<nav class="ux-pagination text-center mt">' . $pagination . '</nav>';
        }
        ?>

      <?php else: ?>
        <div class="text-center">
          <p><strong>
              <?php
              $msg = $is_woo ? 'Không tìm thấy sản phẩm phù hợp.' : 'Không tìm thấy kết quả phù hợp.';
              echo esc_html(function_exists('pll__') ? pll__($msg) : __($msg, 'flatsome'));
              ?>
            </strong></p>
          <?php
          if ($is_woo && function_exists('get_product_search_form')) {
            // get_product_search_form();
          } else {
            // get_search_form();
          }
          ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<style>
  .ux-pagination ul {
    list-style: none;
    margin: 20px 0;
    padding: 0;
    display: inline-flex;
    gap: 6px;
  }

  .ux-pagination li a,
  .ux-pagination li span {
    display: inline-block;
    padding: 6px 10px;
    border: 1px solid #e5e5e5;
  }

  .ux-pagination .current {
    background: #f2f2f2;
  }
</style>

<?php get_footer(); ?>