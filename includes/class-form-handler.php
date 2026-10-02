<?php
/**
 * Xử lý Form đăng ký dành cho Chủ Hợp Tác Xã (HTX)
 * Giao diện Mobile-first bằng Tailwind CSS, tích hợp Mini-map Leaflet và xử lý AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

class OCOP_Form_Handler {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('ocop_register_form', array($this, 'render_form_shortcode'));

        // AJAX handlers cho cả khách và user đăng nhập
        add_action('wp_ajax_ocop_submit_registration', array($this, 'handle_ajax_submission'));
        add_action('wp_ajax_nopriv_ocop_submit_registration', array($this, 'handle_ajax_submission'));
    }

    /**
     * Render Shortcode [ocop_register_form]
     */
    public function render_form_shortcode($atts) {
        // Enqueue script cho form mini-map
        wp_enqueue_script('ocop-form-picker-js');
        wp_enqueue_style('ocop-custom-style');

        ob_start();
        ?>
        <div class="ocop-form-wrapper max-w-3xl mx-auto my-8 px-4 sm:px-6">
            <!-- Header Card -->
            <div class="bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-900 rounded-t-2xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 opacity-10 pointer-events-none">
                    <i class="fa-solid fa-map-location-dot text-9xl"></i>
                </div>
                <div class="inline-flex items-center gap-2 bg-emerald-500/30 backdrop-blur-sm border border-emerald-400/40 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider text-emerald-100 mb-3">
                    <i class="fa-solid fa-leaf"></i> Mạng Lưới OCOP Đà Nẵng
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-snug">
                    Đăng Ký Điểm Du Lịch & Trải Nghiệm OCOP
                </h2>
                <p class="mt-2 text-sm sm:text-base text-emerald-100/90 leading-relaxed">
                    Dành cho các Hợp tác xã, Tổ hợp tác, Doanh nghiệp đặc sản Đà Nẵng muốn kết nối quảng bá tới du khách thập phương.
                </p>
            </div>

            <!-- Form Body -->
            <div class="bg-white rounded-b-2xl shadow-xl border-x border-b border-slate-200/80 p-5 sm:p-8">
                
                <!-- Alert Feedback Box -->
                <div id="ocop-form-alert" class="hidden mb-6 p-4 rounded-xl text-sm font-medium transition-all duration-300">
                    <div class="flex items-start gap-3">
                        <span id="ocop-alert-icon" class="text-lg"></span>
                        <div id="ocop-alert-text" class="flex-1"></div>
                    </div>
                </div>

                <form id="ocop-registration-form" enctype="multipart/form-data" class="space-y-6">
                    <?php wp_nonce_field('ocop_secure_nonce', 'ocop_security_nonce'); ?>

                    <!-- KHỐI 1: THÔNG TIN CƠ SỞ -->
                    <div class="border-b border-slate-100 pb-6">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-4">
                            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">1</span>
                            Thông Tin Cơ Sở Sản Xuất & Chủ Thể
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <div class="sm:col-span-2">
                                <label for="facility_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Tên Điểm Đến / Tên Cơ Sở <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-store"></i>
                                    </div>
                                    <input type="text" name="facility_name" id="facility_name" required
                                        placeholder="Ví dụ: Làng Chiếu Cẩm Nê - Trải nghiệm dệt chiếu thủ công"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>

                            <div>
                                <label for="contact_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Tên Chủ Thể / Hợp Tác Xã <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-building"></i>
                                    </div>
                                    <input type="text" name="contact_name" id="contact_name" required
                                        placeholder="Ví dụ: HTX Nông nghiệp Hòa Phong"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Hotline / Zalo Liên Hệ <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-phone"></i>
                                    </div>
                                    <input type="tel" name="phone" id="phone" required
                                        placeholder="0905 xxx xxx"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Địa Chỉ Chi Tiết (Thôn/Xã/Phường tại Đà Nẵng) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-location-dot"></i>
                                    </div>
                                    <input type="text" name="address" id="address" required
                                        placeholder="Ví dụ: Thôn Cẩm Nê, Xã Hòa Tiến, Huyện Hòa Vang, Đà Nẵng"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 2: HẠNG SAO & LIÊN KẾT THƯƠNG MẠI -->
                    <div class="border-b border-slate-100 pb-6">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-4">
                            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">2</span>
                            Chứng Nhận OCOP & Liên Kết Dịch Vụ
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label for="ocop_stars" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Hạng Sao OCOP Được Công Nhận <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-amber-500">
                                        <i class="fa-solid fa-award"></i>
                                    </div>
                                    <select name="ocop_stars" id="ocop_stars" required
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                                        <option value="3">OCOP 3 Sao (Cấp Huyện/Quận)</option>
                                        <option value="4" selected>OCOP 4 Sao (Cấp Tỉnh/Thành phố)</option>
                                        <option value="5">OCOP 5 Sao (Cấp Quốc Gia)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="ocop_category" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Nhóm Ngành Hàng Chính
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fa-solid fa-tags"></i>
                                    </div>
                                    <select name="ocop_category" id="ocop_category"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                                        <option value="Thực phẩm & Nông sản chế biến">Thực phẩm & Nông sản chế biến</option>
                                        <option value="Đồ uống & Trà thảo mộc">Đồ uống & Trà thảo mộc</option>
                                        <option value="Thủ công mỹ nghệ & Làng nghề">Thủ công mỹ nghệ & Làng nghề</option>
                                        <option value="Dược liệu & Thảo dược">Dược liệu & Thảo dược</option>
                                        <option value="Du lịch nông nghiệp & Trải nghiệm cộng đồng">Du lịch nông nghiệp & Trải nghiệm cộng đồng</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label for="booking_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Link Đặt Lịch Trải Nghiệm (Nút Nổi Bật)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-600">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <input type="url" name="booking_url" id="booking_url"
                                        placeholder="https://zalo.me/... hoặc link form tour"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>

                            <div>
                                <label for="shop_url" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Link Gian Hàng (Shopee / Web / TMĐT)
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-600">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </div>
                                    <input type="url" name="shop_url" id="shop_url"
                                        placeholder="https://shopee.vn/... hoặc link web bán hàng"
                                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KHỐI 3: CHẤM TỌA ĐỘ BẢN ĐỒ -->
                    <div class="border-b border-slate-100 pb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">3</span>
                                Chấm Tọa Độ Cơ Sở Tại Đà Nẵng <span class="text-rose-500">*</span>
                            </h3>
                            <!-- Nút GPS tự động -->
                            <button type="button" id="btn-ocop-get-gps"
                                class="inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition active:scale-95 shadow-sm">
                                <i class="fa-solid fa-location-crosshairs text-sm"></i> Lấy vị trí GPS hiện tại
                            </button>
                        </div>

                        <p class="text-xs text-slate-500 mb-3">
                            <i class="fa-solid fa-circle-info text-emerald-600"></i> Chạm hoặc nhấp chuột lên bản đồ bên dưới để cắm ghim đúng địa chỉ của cơ sở. Bạn cũng có thể kéo ghim để điều chỉnh.
                        </p>

                        <!-- Leaflet Mini Map Container -->
                        <div class="relative rounded-2xl overflow-hidden border border-slate-300 shadow-inner">
                            <div id="ocop-form-map" style="height: 320px; width: 100%; z-index: 10;"></div>
                            
                            <!-- Overlay hiển thị tọa độ đã chọn -->
                            <div class="absolute bottom-3 left-3 right-3 sm:right-auto z-20 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-xl shadow-md border border-slate-200 text-xs font-semibold text-slate-700 flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-location-pin text-rose-500 animate-bounce"></i>
                                    <span id="ocop-coords-display">Chạm vào bản đồ để chọn</span>
                                </span>
                            </div>
                        </div>

                        <!-- Hidden Inputs lưu tọa độ -->
                        <input type="hidden" name="latitude" id="ocop_form_lat" value="" />
                        <input type="hidden" name="longitude" id="ocop_form_lng" value="" />
                    </div>

                    <!-- KHỐI 4: HÌNH ẢNH & GIỚI THIỆU -->
                    <div class="pb-2">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-4">
                            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">4</span>
                            Hình Ảnh & Giới Thiệu Trải Nghiệm
                        </h3>

                        <div class="space-y-4">
                            <!-- Ảnh đại diện -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Ảnh Đại Diện Cơ Sở / Sản Phẩm Tiêu Biểu
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl transition bg-slate-50/50 cursor-pointer relative" id="dropzone-area">
                                    <div class="space-y-1 text-center">
                                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-600 mb-2"></i>
                                        <div class="flex text-sm text-slate-600 justify-center">
                                            <span class="relative font-bold text-emerald-600 hover:text-emerald-500 focus-within:outline-none">
                                                Chọn tệp ảnh
                                            </span>
                                            <p class="pl-1 text-slate-500">hoặc kéo thả vào đây</p>
                                        </div>
                                        <p class="text-xs text-slate-400">PNG, JPG, WEBP dung lượng tối đa 5MB</p>
                                        <div id="file-chosen-name" class="text-xs font-bold text-emerald-700 mt-2 hidden"></div>
                                    </div>
                                    <input id="featured_image" name="featured_image" type="file" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                </div>
                            </div>

                            <!-- Giới thiệu ngắn -->
                            <div>
                                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Giới Thiệu Ngắn Về Điểm Đến & Trải Nghiệm <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="description" id="description" rows="4" required
                                    placeholder="Mô tả các hoạt động trải nghiệm đặc sắc du khách có thể tham gia, câu chuyện sản phẩm OCOP, giá vé tham quan (nếu có)..."
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- NÚT SUBMIT -->
                    <div class="pt-4">
                        <button type="submit" id="btn-submit-ocop"
                            class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-extrabold text-base tracking-wide shadow-lg hover:shadow-emerald-600/30 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-3 cursor-pointer">
                            <span id="btn-submit-spinner" class="hidden">
                                <i class="fa-solid fa-circle-notch fa-spin text-lg"></i>
                            </span>
                            <span id="btn-submit-text" class="flex items-center gap-2">
                                <i class="fa-solid fa-paper-plane text-lg"></i> GỬI HỒ SƠ ĐĂNG KÝ NGAY
                            </span>
                        </button>
                        <p class="text-center text-xs text-slate-500 mt-3 flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-emerald-600"></i> Thông tin được kiểm duyệt cẩn trọng bởi Tổ công tác OCOP TP. Đà Nẵng trước khi hiển thị trên bản đồ số.
                        </p>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Xử lý AJAX nhận dữ liệu đăng ký từ Frontend
     */
    public function handle_ajax_submission() {
        // Kiểm tra bảo mật Nonce
        check_ajax_referer('ocop_secure_nonce', 'ocop_security_nonce');

        // Lấy dữ liệu và validate cơ bản
        $facility_name = isset($_POST['facility_name']) ? sanitize_text_field(wp_unslash($_POST['facility_name'])) : '';
        $contact_name  = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
        $phone         = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $address       = isset($_POST['address']) ? sanitize_text_field(wp_unslash($_POST['address'])) : '';
        $ocop_stars    = isset($_POST['ocop_stars']) ? sanitize_text_field(wp_unslash($_POST['ocop_stars'])) : '4';
        $category      = isset($_POST['ocop_category']) ? sanitize_text_field(wp_unslash($_POST['ocop_category'])) : '';
        $booking_url   = isset($_POST['booking_url']) ? esc_url_raw(wp_unslash($_POST['booking_url'])) : '';
        $shop_url      = isset($_POST['shop_url']) ? esc_url_raw(wp_unslash($_POST['shop_url'])) : '';
        $latitude      = isset($_POST['latitude']) ? sanitize_text_field(wp_unslash($_POST['latitude'])) : '';
        $longitude     = isset($_POST['longitude']) ? sanitize_text_field(wp_unslash($_POST['longitude'])) : '';
        $description   = isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '';

        // Kiểm tra trường bắt buộc
        if (empty($facility_name) || empty($phone) || empty($address) || empty($description)) {
            wp_send_json_error(array(
                'message' => 'Vui lòng điền đầy đủ các thông tin bắt buộc có dấu (*).'
            ));
        }

        if (empty($latitude) || empty($longitude)) {
            wp_send_json_error(array(
                'message' => 'Vui lòng chấm tọa độ vị trí của cơ sở trên bản đồ!'
            ));
        }

        // Tạo bài viết mới với trạng thái pending (Chờ duyệt chống spam)
        $post_data = array(
            'post_title'   => $facility_name,
            'post_content' => $description,
            'post_status'  => 'pending', // QUAN TRỌNG: Chờ Admin kiểm duyệt
            'post_type'    => 'diem_ocop',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array(
                'message' => 'Không thể tạo bản ghi: ' . $post_id->get_error_message()
            ));
        }

        // Lưu các Custom Meta Fields
        update_post_meta($post_id, '_ocop_vi_do', $latitude);
        update_post_meta($post_id, '_ocop_kinh_do', $longitude);
        update_post_meta($post_id, '_ocop_so_sao', $ocop_stars);
        update_post_meta($post_id, '_ocop_dia_chi', $address);
        update_post_meta($post_id, '_ocop_chu_the', $contact_name);
        update_post_meta($post_id, '_ocop_so_dien_thoai', $phone);
        update_post_meta($post_id, '_ocop_link_dat_lich', $booking_url);
        update_post_meta($post_id, '_ocop_link_mua_hang', $shop_url);

        // Gán Taxonomy nếu có
        if (!empty($category)) {
            wp_set_object_terms($post_id, $category, 'ocop_danh_muc');
        }

        // Xử lý upload ảnh đại diện (Featured Image) an toàn
        if (!empty($_FILES['featured_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file = $_FILES['featured_image'];

            // Kiểm tra định dạng ảnh cho phép
            $allowed_mimes = array('image/jpeg', 'image/png', 'image/webp');
            if (in_array($file['type'], $allowed_mimes, true)) {
                $attach_id = media_handle_upload('featured_image', $post_id);
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }

        wp_send_json_success(array(
            'message' => 'Đăng ký thành công! Hồ sơ điểm OCOP "' . esc_html($facility_name) . '" đã được tiếp nhận và đang chờ Ban Quản trị phê duyệt để hiển thị lên bản đồ du lịch.'
        ));
    }
}
