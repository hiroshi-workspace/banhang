<?php
// Thêm hàm này vô file functions.php của theme hoặc plugin riêng
add_action('wp_footer', function(){
    if ( !is_page('event') && !(function_exists('is_singular') && is_singular('event')) ) {
        echo do_shortcode('[open_popup_event]');
    }
}, 5);

function add_popup_to_footer()
{
    // KHÔNG hiển thị popup ở trang "event" (page) hoặc CPT "event"
    if (is_page('event') || (function_exists('is_singular') && is_singular('event'))) return;
?>
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center" style="display:none;">
        <div class="relative bg-white p-6 rounded-lg max-w-md w-full">
            <button id="close-popup" class="absolute top-2 right-2 text-2xl font-bold text-red-500 hover:text-red-700" aria-label="Close">&times;</button>
            <?php echo do_shortcode('[block id="popup-event"]'); ?>
        </div>
    </div>

    <script>
        (function() {
            var KEY = 'popupEventClosed'; // lưu trạng thái đã đóng

            function $(id) {
                return document.getElementById(id);
            }

            function showPopup() {
                var o = $('popup-overlay');
                if (o) o.style.display = 'flex';
            }

            function hidePopup(persist) {
                var o = $('popup-overlay');
                if (o) o.style.display = 'none';
                if (persist) {
                    try {
                        localStorage.setItem(KEY, 'true');
                    } catch (e) {}
                }
                showOpenBtn(); // sau khi đóng thì hiện nút
            }

            function showOpenBtn() {
                var b = $('open-popup');
                if (b) b.classList.add('show');
            }

            function hideOpenBtn() {
                var b = $('open-popup');
                if (b) b.classList.remove('show');
            }

            document.addEventListener('DOMContentLoaded', function() {
                var closed = false;
                try {
                    closed = localStorage.getItem(KEY) === 'true';
                } catch (e) {}

                if (!closed) {
                    hideOpenBtn(); // chưa đóng thì ẩn nút
                    setTimeout(showPopup, 5000); // trễ 5s mới hiện popup
                } else {
                    showOpenBtn(); // đã đóng rồi thì chỉ hiện nút
                }

                var closeBtn = $('close-popup');
                if (closeBtn) closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    hidePopup(true);
                });

                var overlay = $('popup-overlay');
                if (overlay) overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) hidePopup(true);
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') hidePopup(true);
                });

                // Nút mở thủ công (dù đã đóng trước đó vẫn mở lại được)
                var openBtn = $('open-popup');
                if (openBtn) openBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showPopup();
                });
            });
        })();
    </script>
<?php
}
add_action('wp_footer', 'add_popup_to_footer');


function popup_fixed_button_shortcode()
{
    // KHÔNG render nút ở trang "event" (page) hoặc CPT "event"
    if (is_page('event') || (function_exists('is_singular') && is_singular('event'))) return '';

    ob_start(); ?>
    <a id="open-popup" class="fixed-popup-button tooltip neon-pulse" title="Event" aria-label="Event" rel="noopener nofollow">
        <i class="fa-regular fa-calendar-days"></i>
    </a>
    <style>
        .fixed-popup-button {
            position: fixed;
            bottom: 5%;
            right: 2%;
            background-color: var(--fs-color-success);
            color: #fff;
            padding: 12px 20px;
            border-radius: 50%;
            text-decoration: none;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .2);
            z-index: 999;
            cursor: pointer;
            height: 60px;
            width: 60px;
            display: none !important;
            /* ẩn mặc định, chỉ hiện khi thêm .show */
            align-items: center;
            justify-content: center;
            transition: all .3s ease;
            overflow: hidden;
        }

        .fixed-popup-button.show {
            display: flex !important;
        }

        .fixed-popup-button i {
            font-size: 20px;
        }

        .fixed-popup-button:hover {
            color: #fff !important;
        }

        .neon-pulse {
            background: var(--fs-color-alert);
            border: 2px solid #FE9900;
            box-shadow: 0 0 10px rgba(0, 255, 255, .3);
            overflow: visible;
        }

        .neon-pulse::before,
        .neon-pulse::after {
            content: "";
            position: absolute;
            inset: -4px;
            border: 2px solid #FE9900;
            border-radius: inherit;
            animation: pulseOut 2s ease-out infinite;
            opacity: 0;
        }

        .neon-pulse::after {
            animation-delay: 1s;
        }

        @keyframes pulseOut {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .fixed-popup-button {
                right: 3%;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('open_popup_event', 'popup_fixed_button_shortcode');
