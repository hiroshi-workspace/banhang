<?php
// require_once get_stylesheet_directory() . '/inc/shortcodes/main.php';

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using
 * **/

function call_main_scripts()
{
    // CSS
    wp_enqueue_style(
        'cssFeBlog', // handle
        get_stylesheet_directory_uri() . '/assets/css/styleBlogByFle.css',
        array(), // deps (để rỗng nếu không phụ thuộc gì)
        filemtime(get_stylesheet_directory() . '/assets/css/styleBlogByFle.css'), // version
        'all' // medias
    );
    wp_enqueue_style(
        'cssSidebar', // handle
        get_stylesheet_directory_uri() . '/assets/css/sidebarBlog.css',
        array(), // deps (để rỗng nếu không phụ thuộc gì)
        filemtime(get_stylesheet_directory() . '/assets/css/sidebarBlog.css'), // version
        'all' // medias
    );
    // wp_enqueue_style(
    //     'swiper-css', // handle
    //     get_stylesheet_directory_uri() . '/assets/css/swiper-bundle.min.css',
    //     array(), // deps (để rỗng nếu không phụ thuộc gì)
    //     filemtime(get_stylesheet_directory() . '/assets/css/swiper-bundle.min.css'), // version
    //     'all' // medias
    // );

    // JS
    wp_enqueue_script('jquery'); // nếu cần jQuery

    wp_enqueue_script(
        'main',
        get_stylesheet_directory_uri() . '/assets/js/main.js',
        array('jquery'), // deps
        filemtime(get_stylesheet_directory() . '/assets/js/main.js'),
        true // in footer
    );
    // wp_enqueue_script(
    //     'swiper-js',
    //     get_stylesheet_directory_uri() . '/assets/js/swiper-bundle.min.js',
    //     array('jquery'), // deps
    //     filemtime(get_stylesheet_directory() . '/assets/js/swiper-bundle.min.js'),
    //     true // in footer
    // );
}
add_action('wp_enqueue_scripts', 'call_main_scripts');


add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) return;

    // Khi user gõ ?s=... ở bất kỳ đâu, chuyển sang trang tim-kiem với ?q=
    if (is_search() && get_query_var('s')) {
        $page = get_page_by_path('tim-kiem');            // đúng slug Page đích
        if (!$page) return;

        $url = add_query_arg('p', urlencode(get_query_var('s')), get_permalink($page->ID));
        wp_safe_redirect($url, 302);
        exit;
    }
});


// Trang tim-kiem dùng tham số ?q=, ép không bị is_search
add_action('pre_get_posts', function($p){
  if (!is_admin() && $p->is_main_query() && is_page('tim-kiem')) {
    $p->is_search = false;   // quan trọng!
    $p->is_404    = false;
    $p->set('s','');         // không để main query bị ảnh hưởng
  }
});

// Đảm bảo Flatsome KHÔNG tắt footer ở trang tim-kiem
add_filter('flatsome_show_footer', function($show){
  if (is_page('tim-kiem')) return true;
  return $show;
});

