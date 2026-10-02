<?php
/**
 * Plugin Name: OCOP Đà Nẵng Map - Bản Đồ & Đăng Ký Điểm OCOP
 * Plugin URI:  https://ocopdanang.gov.vn
 * Description: Hệ thống bản đồ số tương tác du lịch trải nghiệm và xúc tiến thương mại OCOP Đà Nẵng (Mobile-first). Tích hợp luồng tiếp nhận đăng ký trực tuyến cho các Hợp tác xã (HTX) và bản đồ tìm kiếm điểm đến cho du khách.
 * Version:     1.0.0
 * Author:      OCOP Da Nang Tourism Team & Full-stack Architect
 * Author URI:  https://ocopdanang.gov.vn
 * Text Domain: ocop-danang-map
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Chống truy cập trực tiếp
}

// Định nghĩa các hằng số cốt lõi
define('OCOP_MAP_VERSION', '1.0.0');
define('OCOP_MAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OCOP_MAP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Class khởi tạo plugin chính
 */
final class OCOP_Danang_Map_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Tải các file chức năng chuyên biệt trong includes/
     */
    private function load_dependencies() {
        require_once OCOP_MAP_PLUGIN_DIR . 'includes/class-cpt.php';
        require_once OCOP_MAP_PLUGIN_DIR . 'includes/class-api.php';
        require_once OCOP_MAP_PLUGIN_DIR . 'includes/class-form-handler.php';
    }

    /**
     * Đăng ký các hooks chính của WordPress
     */
    private function init_hooks() {
        // Khởi tạo các module con
        OCOP_CPT::get_instance();
        OCOP_API::get_instance();
        OCOP_Form_Handler::get_instance();

        // Đăng ký Shortcode Bản đồ Tương tác Du khách
        add_shortcode('ocop_interactive_map', array($this, 'render_map_shortcode'));

        // Enqueue tài nguyên
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Đăng ký CSS/JS bên ngoài và nội bộ cho Frontend
     */
    public function enqueue_frontend_assets() {
        // 1. Tailwind CSS CDN
        wp_enqueue_script(
            'ocop-tailwindcss',
            'https://cdn.tailwindcss.com',
            array(),
            '3.4.1',
            false
        );

        $tailwind_config = "
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            ocop: {
                                red: '#e11d48',
                                emerald: '#059669',
                                gold: '#d97706',
                                silver: '#64748b',
                                dark: '#0f172a'
                            }
                        },
                        boxShadow: {
                            'sheet': '0 -10px 25px -5px rgba(0, 0, 0, 0.1), 0 -8px 10px -6px rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        ";
        wp_add_inline_script('ocop-tailwindcss', $tailwind_config);

        // 2. FontAwesome 6 (CDN) - TUYỆT ĐỐI KHÔNG DÙNG EMOJI
        wp_enqueue_style(
            'ocop-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );

        // 3. Leaflet CSS & JS
        wp_enqueue_style(
            'ocop-leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_enqueue_script(
            'ocop-leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // 4. CSS Tùy chỉnh Plugin
        wp_enqueue_style(
            'ocop-custom-style',
            OCOP_MAP_PLUGIN_URL . 'assets/css/style.css',
            array('ocop-leaflet-css', 'ocop-fontawesome'),
            OCOP_MAP_VERSION
        );

        // 5. JavaScript Tương Tác Bản Đồ Du Khách
        wp_register_script(
            'ocop-tourist-map-js',
            OCOP_MAP_PLUGIN_URL . 'assets/js/leaflet-map.js',
            array('ocop-leaflet-js'),
            OCOP_MAP_VERSION,
            true
        );

        // 6. JavaScript Mini-Map Form Đăng Ký HTX
        wp_register_script(
            'ocop-form-picker-js',
            OCOP_MAP_PLUGIN_URL . 'assets/js/form-picker.js',
            array('ocop-leaflet-js'),
            OCOP_MAP_VERSION,
            true
        );

        // Cung cấp biến cấu hình chung
        $localized_data = array(
            'ajax_url'    => admin_url('admin-ajax.php'),
            'rest_url'    => esc_url_raw(rest_url('ocop/v1/locations')),
            'nonce'       => wp_create_nonce('ocop_secure_nonce'),
            'default_lat' => 16.0544,
            'default_lng' => 108.2022,
            'default_zoom'=> 12,
            'plugin_url'  => OCOP_MAP_PLUGIN_URL,
            'i18n'        => array(
                'loading'          => 'Đang tải dữ liệu điểm OCOP...',
                'locate_error'     => 'Không thể xác định vị trí của bạn.',
                'searching'        => 'Đang tìm kiếm...',
                'no_result'        => 'Không tìm thấy điểm OCOP phù hợp',
                'require_coords'   => 'Vui lòng chấm tọa độ trên bản đồ!',
                'submit_success'   => 'Đăng ký thành công! Hồ sơ điểm OCOP đang chờ ban quản trị phê duyệt.',
                'submit_error'     => 'Có lỗi xảy ra trong quá trình gửi, vui lòng thử lại!'
            )
        );

        wp_localize_script('ocop-tourist-map-js', 'ocopData', $localized_data);
        wp_localize_script('ocop-form-picker-js', 'ocopData', $localized_data);
    }

    /**
     * Enqueue Leaflet & Style cho Admin
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        if ('diem_ocop' === $post_type && in_array($hook, array('post.php', 'post-new.php'), true)) {
            wp_enqueue_style(
                'ocop-admin-leaflet-css',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                array(),
                '1.9.4'
            );
            wp_enqueue_script(
                'ocop-admin-leaflet-js',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                array(),
                '1.9.4',
                true
            );
            wp_enqueue_style(
                'ocop-admin-fa',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                array(),
                '6.5.1'
            );
        }
    }

    /**
     * Render Shortcode [ocop_interactive_map] - Bản đồ tương tác cho Du khách
     */
    public function render_map_shortcode($atts) {
        wp_enqueue_script('ocop-tourist-map-js');
        wp_enqueue_style('ocop-custom-style');

        $atts = shortcode_atts(array(
            'height' => '650px',
            'title'  => 'Bản Đồ Điểm Du Lịch & Trải Nghiệm OCOP Đà Nẵng'
        ), $atts, 'ocop_interactive_map');

        ob_start();
        ?>
        <div class="ocop-map-wrapper w-full max-w-7xl mx-auto my-6 px-2 sm:px-4 font-sans select-none">
            
            <!-- THANH ĐIỀU HƯỚNG & BỘ LỌC THÔNG MINH -->
            <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-md border border-slate-200/80 p-3 sm:p-4 mb-3 sm:mb-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    
                    <!-- Tiêu đề & Đếm số lượng điểm -->
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center text-base shadow-sm">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </span>
                            <div>
                                <h3 class="text-base sm:text-lg font-extrabold text-slate-800 leading-tight">
                                    <?php echo esc_html($atts['title']); ?>
                                </h3>
                                <p class="text-xs text-slate-500 hidden sm:block">Chạm vào điểm ghim để xem chi tiết, đặt lịch trải nghiệm & mua sắm</p>
                            </div>
                        </div>
                        <span id="ocop-count-badge" class="px-2.5 py-1 bg-emerald-50 text-emerald-800 text-xs font-bold rounded-full border border-emerald-200 whitespace-nowrap">
                            Đang tải...
                        </span>
                    </div>

                    <!-- Ô tìm kiếm & Nút GPS vị trí của tôi -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            </div>
                            <input type="text" id="ocop-search-input"
                                placeholder="Tìm cơ sở, địa chỉ, đặc sản..."
                                class="w-full pl-8 pr-3 py-2 bg-slate-100 hover:bg-slate-50 focus:bg-white border border-slate-200 rounded-xl text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition" />
                        </div>

                        <button type="button" id="btn-my-location" title="Định vị vị trí của tôi"
                            class="px-3 py-2 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-700 text-slate-700 border border-slate-200 rounded-xl text-xs sm:text-sm font-semibold transition active:scale-95 flex items-center gap-1.5 whitespace-nowrap">
                            <i class="fa-solid fa-crosshairs text-emerald-600"></i>
                            <span class="hidden sm:inline">Vị trí của tôi</span>
                        </button>
                    </div>
                </div>

                <!-- Dải nút lọc theo hạng sao (Mobile horizontal scrollable) -->
                <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mr-1 whitespace-nowrap">Lọc sao:</span>
                    
                    <button type="button" data-stars="all"
                        class="ocop-star-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-slate-900 text-white shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fa-solid fa-layer-group"></i> Tất cả
                    </button>

                    <button type="button" data-stars="5"
                        class="ocop-star-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-slate-700 hover:bg-rose-50 border border-slate-200 flex items-center gap-1.5 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <i class="fa-solid fa-star text-rose-500 text-[10px]"></i> 5 Sao (Quốc Gia)
                    </button>

                    <button type="button" data-stars="4"
                        class="ocop-star-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-slate-700 hover:bg-amber-50 border border-slate-200 flex items-center gap-1.5 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <i class="fa-solid fa-star text-amber-500 text-[10px]"></i> 4 Sao (Cấp Tỉnh/TP)
                    </button>

                    <button type="button" data-stars="3"
                        class="ocop-star-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 flex items-center gap-1.5 whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <i class="fa-solid fa-star text-slate-500 text-[10px]"></i> 3 Sao (Cấp Quận/Huyện)
                    </button>
                </div>
            </div>

            <!-- CONTAINER KHUNG BẢN ĐỒ VÀ BOTTOM SHEET -->
            <div class="relative w-full rounded-2xl overflow-hidden shadow-xl border border-slate-200/80 ocop-map-container" style="height: <?php echo esc_attr($atts['height']); ?>;">
                
                <!-- Leaflet Map Container -->
                <div id="ocop-tourist-map" class="w-full h-full z-10"></div>

                <!-- Loading Spinner Overlay -->
                <div id="ocop-map-loading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-30 flex items-center justify-center transition-opacity duration-300">
                    <div class="text-center p-4">
                        <i class="fa-solid fa-circle-notch fa-spin text-4xl text-emerald-600 mb-3"></i>
                        <p class="text-sm font-bold text-slate-800">Đang tải bản đồ OCOP Đà Nẵng...</p>
                        <p class="text-xs text-slate-500 mt-1">Kết nối mạng lưới du lịch trải nghiệm</p>
                    </div>
                </div>

                <!-- BACKDROP CHO MOBILE BOTTOM SHEET -->
                <div id="ocop-sheet-backdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-40 transition-opacity duration-300 opacity-0 pointer-events-none md:hidden"></div>

                <!-- BOTTOM SHEET (MOBILE) / MODAL CARD (DESKTOP) -->
                <div id="ocop-bottom-sheet"
                    class="fixed md:absolute inset-x-0 bottom-0 md:bottom-5 md:left-5 md:right-auto z-50 md:w-[410px] max-h-[82vh] md:max-h-[580px] bg-white rounded-t-3xl md:rounded-2xl shadow-sheet md:shadow-2xl border border-slate-200 transform translate-y-full opacity-0 pointer-events-none transition-all duration-300 ease-out flex flex-col overflow-hidden">
                    
                    <!-- Drag Handle Bar (Chỉ hiển thị trên Mobile) -->
                    <div class="pt-3 pb-1 flex justify-center md:hidden bg-slate-50/80 cursor-grab">
                        <div class="w-12 h-1.5 bg-slate-300 rounded-full"></div>
                    </div>

                    <!-- Nút Đóng -->
                    <button type="button" id="btn-close-sheet"
                        class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-slate-900/60 hover:bg-slate-900 text-white flex items-center justify-center transition active:scale-90"
                        title="Đóng thông tin">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>

                    <!-- Vùng Cuộn Thông Tin Chi Tiết -->
                    <div class="overflow-y-auto ocop-bottom-sheet-content flex-1">
                        
                        <!-- Ảnh Cơ Sở & Hạng Sao Floating -->
                        <div class="relative w-full h-44 sm:h-48 bg-slate-100 overflow-hidden">
                            <img id="sheet-image" src="" alt="" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                            
                            <!-- Badges trên ảnh -->
                            <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between gap-2">
                                <span id="sheet-stars-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold shadow-sm bg-white text-slate-800">
                                    <!-- Badge sao JS -->
                                </span>
                                <span id="sheet-category" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900/80 text-white backdrop-blur-sm truncate max-w-[180px]">
                                    <!-- Nhóm ngành -->
                                </span>
                            </div>
                        </div>

                        <!-- Nội dung Chi tiết -->
                        <div class="p-4 sm:p-5 space-y-3">
                            <div>
                                <h4 id="sheet-title" class="text-lg sm:text-xl font-black text-slate-900 leading-snug">
                                    <!-- Tên Cơ sở -->
                                </h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <div id="sheet-stars-rating" class="text-xs">
                                        <!-- Sao FontAwesome JS -->
                                    </div>
                                    <span class="text-xs text-slate-400">•</span>
                                    <span id="sheet-coop" class="text-xs font-semibold text-slate-600 truncate">
                                        <!-- Tên HTX -->
                                    </span>
                                </div>
                            </div>

                            <!-- Địa chỉ -->
                            <div class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                <i class="fa-solid fa-location-dot text-rose-500 mt-0.5 text-sm shrink-0"></i>
                                <span id="sheet-address" class="flex-1 font-medium leading-relaxed">
                                    <!-- Địa chỉ JS -->
                                </span>
                            </div>

                            <!-- Hotline (Nếu có) -->
                            <div id="sheet-phone-container" class="hidden flex items-center gap-2 text-xs font-bold text-slate-700">
                                <i class="fa-solid fa-phone text-emerald-600"></i>
                                <span>Hotline: </span>
                                <a id="sheet-phone" href="" class="text-emerald-700 hover:underline"></a>
                            </div>

                            <!-- Giới thiệu ngắn -->
                            <div>
                                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Trải nghiệm & Sản phẩm:</h5>
                                <p id="sheet-desc" class="text-xs sm:text-sm text-slate-600 leading-relaxed max-h-24 overflow-y-auto pr-1">
                                    <!-- Mô tả JS -->
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- THANH HÀNH ĐỘNG DƯỚI ĐÁY (STICKY ACTION BAR - TOUCH TARGET LỚN) -->
                    <div class="p-3 sm:p-4 bg-slate-50 border-t border-slate-200/80 flex flex-col gap-2 shrink-0">
                        <!-- NÚT 1: ĐẶT LỊCH TRẢI NGHIỆM (Nổi bật, button to, icon lịch) -->
                        <a id="sheet-btn-booking" href="#" target="_blank"
                            class="ocop-touch-target w-full py-3 sm:py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-sm text-center shadow-md hover:shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 active:scale-98">
                            <i class="fa-solid fa-calendar-check text-base"></i> ĐẶT LỊCH TRẢI NGHIỆM
                        </a>

                        <!-- HÀNG 2: MUA SẢN PHẨM & DẪN ĐƯỜNG -->
                        <div class="flex items-center gap-2">
                            <!-- NÚT 2: MUA SẢN PHẨM (Button outline, icon giỏ hàng) -->
                            <a id="sheet-btn-shop" href="#" target="_blank"
                                class="ocop-touch-target flex-1 py-2.5 px-3 rounded-xl border border-slate-300 hover:border-amber-500 bg-white hover:bg-amber-50/50 text-slate-800 font-bold text-xs text-center transition flex items-center justify-center gap-1.5 active:scale-98">
                                <i class="fa-solid fa-cart-shopping text-amber-600"></i> MUA SẢN PHẨM
                            </a>

                            <!-- NÚT 3: DẪN ĐƯỜNG GOOGLE MAPS -->
                            <a id="sheet-btn-directions" href="#" target="_blank"
                                class="ocop-touch-target px-3.5 py-2.5 rounded-xl border border-slate-300 hover:border-blue-500 bg-white hover:bg-blue-50/50 text-slate-800 font-bold text-xs text-center transition flex items-center justify-center gap-1.5 active:scale-98"
                                title="Chỉ đường trên Google Maps">
                                <i class="fa-solid fa-diamond-turn-right text-blue-600"></i> Dẫn đường
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}

// Khởi chạy Plugin
function ocop_danang_map_init() {
    return OCOP_Danang_Map_Plugin::get_instance();
}
add_action('plugins_loaded', 'ocop_danang_map_init');
