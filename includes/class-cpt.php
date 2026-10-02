<?php
/**
 * Quản lý Custom Post Type & Meta Box cho Điểm OCOP Đà Nẵng
 * Không phụ thuộc bên thứ 3 (Pure PHP & WP Native APIs)
 */

if (!defined('ABSPATH')) {
    exit;
}

class OCOP_CPT {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_custom_meta_boxes'));
        add_action('save_post_diem_ocop', array($this, 'save_meta_box_data'));

        // Tùy biến bảng hiển thị bài viết trong WP Admin
        add_filter('manage_diem_ocop_posts_columns', array($this, 'custom_admin_columns'));
        add_action('manage_diem_ocop_posts_custom_column', array($this, 'render_admin_column_content'), 10, 2);
        add_filter('manage_edit-diem_ocop_sortable_columns', array($this, 'sortable_admin_columns'));
    }

    /**
     * Đăng ký Custom Post Type 'diem_ocop'
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => __('Điểm OCOP Đà Nẵng', 'ocop-danang-map'),
            'singular_name'         => __('Điểm OCOP', 'ocop-danang-map'),
            'menu_name'             => __('Điểm OCOP Đà Nẵng', 'ocop-danang-map'),
            'name_admin_bar'        => __('Điểm OCOP', 'ocop-danang-map'),
            'add_new'               => __('Thêm Điểm Mới', 'ocop-danang-map'),
            'add_new_item'          => __('Thêm Điểm OCOP Mới', 'ocop-danang-map'),
            'new_item'              => __('Điểm OCOP Mới', 'ocop-danang-map'),
            'edit_item'             => __('Chỉnh Sửa Điểm OCOP', 'ocop-danang-map'),
            'view_item'             => __('Xem Điểm OCOP', 'ocop-danang-map'),
            'all_items'             => __('Tất Cả Điểm OCOP', 'ocop-danang-map'),
            'search_items'          => __('Tìm Điểm OCOP', 'ocop-danang-map'),
            'parent_item_colon'     => __('Điểm Cha:', 'ocop-danang-map'),
            'not_found'             => __('Không tìm thấy Điểm OCOP nào.', 'ocop-danang-map'),
            'not_found_in_trash'    => __('Thùng rác trống.', 'ocop-danang-map'),
            'featured_image'        => __('Ảnh Đại Diện Cơ Sở', 'ocop-danang-map'),
            'set_featured_image'    => __('Đặt ảnh đại diện cơ sở', 'ocop-danang-map'),
            'remove_featured_image' => __('Xóa ảnh đại diện', 'ocop-danang-map'),
            'use_featured_image'    => __('Sử dụng làm ảnh đại diện', 'ocop-danang-map'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'diem-ocop', 'with_front' => false),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'       => true,
        );

        register_post_type('diem_ocop', $args);
    }

    /**
     * Đăng ký Phân loại Ngành Hàng OCOP
     */
    public function register_taxonomies() {
        $labels = array(
            'name'              => __('Ngành Hàng OCOP', 'ocop-danang-map'),
            'singular_name'     => __('Ngành Hàng', 'ocop-danang-map'),
            'search_items'      => __('Tìm Ngành Hàng', 'ocop-danang-map'),
            'all_items'         => __('Tất Cả Ngành Hàng', 'ocop-danang-map'),
            'parent_item'       => __('Ngành Hàng Cha', 'ocop-danang-map'),
            'parent_item_colon' => __('Ngành Hàng Cha:', 'ocop-danang-map'),
            'edit_item'         => __('Sửa Ngành Hàng', 'ocop-danang-map'),
            'update_item'       => __('Cập Nhật Ngành Hàng', 'ocop-danang-map'),
            'add_new_item'      => __('Thêm Ngành Hàng Mới', 'ocop-danang-map'),
            'new_item_name'     => __('Tên Ngành Hàng Mới', 'ocop-danang-map'),
            'menu_name'         => __('Ngành Hàng OCOP', 'ocop-danang-map'),
        );

        register_taxonomy('ocop_danh_muc', array('diem_ocop'), array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'nganh-hang-ocop'),
            'show_in_rest'      => true,
        ));
    }

    /**
     * Khởi tạo Meta Box trong trang Quản trị Admin
     */
    public function add_custom_meta_boxes() {
        add_meta_box(
            'ocop_meta_box_details',
            __('Thông Tin Tọa Độ & Dịch Vụ OCOP (Bản Đồ Số)', 'ocop-danang-map'),
            array($this, 'render_meta_box_content'),
            'diem_ocop',
            'normal',
            'high'
        );
    }

    /**
     * Render giao diện Meta Box trong Admin
     */
    public function render_meta_box_content($post) {
        wp_nonce_field('ocop_save_meta_box_data', 'ocop_meta_box_nonce');

        $vi_do         = get_post_meta($post->ID, '_ocop_vi_do', true);
        $kinh_do        = get_post_meta($post->ID, '_ocop_kinh_do', true);
        $so_sao        = get_post_meta($post->ID, '_ocop_so_sao', true);
        $dia_chi       = get_post_meta($post->ID, '_ocop_dia_chi', true);
        $chu_the       = get_post_meta($post->ID, '_ocop_chu_the', true);
        $so_dien_thoai = get_post_meta($post->ID, '_ocop_so_dien_thoai', true);
        $link_dat_lich = get_post_meta($post->ID, '_ocop_link_dat_lich', true);
        $link_mua_hang = get_post_meta($post->ID, '_ocop_link_mua_hang', true);

        // Giá trị mặc định tọa độ nếu trống
        if (empty($vi_do))  $vi_do  = '16.054400';
        if (empty($kinh_do)) $kinh_do = '108.202200';
        if (empty($so_sao)) $so_sao = '4';
        ?>
        <style>
            .ocop-admin-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
            .ocop-admin-field { margin-bottom: 16px; }
            .ocop-admin-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b; }
            .ocop-admin-field input, .ocop-admin-field select { width: 100%; max-width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; }
            .ocop-admin-field .desc { font-size: 12px; color: #64748b; margin-top: 4px; }
            #ocop-admin-map-preview { height: 280px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; margin-top: 10px; z-index: 1; }
            .ocop-admin-notice-bar { background: #f0fdf4; border-left: 4px solid #16a34a; padding: 10px 14px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; color: #166534; }
        </style>

        <div class="ocop-admin-notice-bar">
            <strong><i class="fa-solid fa-map-location-dot"></i> Bản đồ định vị:</strong> Click hoặc kéo thả con trỏ ghim trên bản đồ bên dưới để tự động cập nhật Vĩ độ và Kinh độ chính xác của cơ sở tại TP. Đà Nẵng.
        </div>

        <div class="ocop-admin-grid">
            <!-- Cột trái: Tọa độ & Đánh giá -->
            <div>
                <div class="ocop-admin-field">
                    <label for="ocop_so_sao"><i class="fa-solid fa-award text-amber-500"></i> Hạng Sao OCOP Chứng Nhận:</label>
                    <select name="ocop_so_sao" id="ocop_so_sao">
                        <option value="3" <?php selected($so_sao, '3'); ?>>⭐⭐⭐ 3 Sao OCOP (Đạt chuẩn cấp Quận/Huyện)</option>
                        <option value="4" <?php selected($so_sao, '4'); ?>>⭐⭐⭐⭐ 4 Sao OCOP (Đạt chuẩn cấp Thành Phố)</option>
                        <option value="5" <?php selected($so_sao, '5'); ?>>⭐⭐⭐⭐⭐ 5 Sao OCOP (Đạt chuẩn Quốc Gia)</option>
                    </select>
                    <div class="desc">Phân loại màu ghim trên bản đồ (3 sao: Bạc, 4 sao: Vàng Gold, 5 sao: Đỏ Cam).</div>
                </div>

                <div class="ocop-admin-field">
                    <label for="ocop_chu_the"><i class="fa-solid fa-building"></i> Tên Chủ Thể / Hợp Tác Xã Sản Xuất:</label>
                    <input type="text" name="ocop_chu_the" id="ocop_chu_the" value="<?php echo esc_attr($chu_the); ?>" placeholder="Ví dụ: HTX Nông nghiệp Công nghệ cao Hòa Vang">
                </div>

                <div class="ocop-admin-field">
                    <label for="ocop_dia_chi"><i class="fa-solid fa-location-dot"></i> Địa Chỉ Chi Tiết Tại Đà Nẵng:</label>
                    <input type="text" name="ocop_dia_chi" id="ocop_dia_chi" value="<?php echo esc_attr($dia_chi); ?>" placeholder="Ví dụ: Thôn Túy Loan Đông, Xã Hòa Phong, Huyện Hòa Vang">
                </div>

                <div class="ocop-admin-field">
                    <label for="ocop_so_dien_thoai"><i class="fa-solid fa-phone"></i> Hotline / Zalo Liên Hệ:</label>
                    <input type="text" name="ocop_so_dien_thoai" id="ocop_so_dien_thoai" value="<?php echo esc_attr($so_dien_thoai); ?>" placeholder="Ví dụ: 0905 123 456">
                </div>
            </div>

            <!-- Cột phải: Liên kết & Tọa độ -->
            <div>
                <div class="ocop-admin-field">
                    <label for="ocop_link_dat_lich"><i class="fa-solid fa-calendar-check text-emerald-600"></i> Link Đặt Lịch Trải Nghiệm:</label>
                    <input type="url" name="ocop_link_dat_lich" id="ocop_link_dat_lich" value="<?php echo esc_url($link_dat_lich); ?>" placeholder="https://zalo.me/... hoặc link form đặt tour">
                    <div class="desc">Nút hành động chính dành cho khách du lịch trên popup bản đồ.</div>
                </div>

                <div class="ocop-admin-field">
                    <label for="ocop_link_mua_hang"><i class="fa-solid fa-cart-shopping text-blue-600"></i> Link Mua Sản Phẩm (Shopee / TMĐT / Web):</label>
                    <input type="url" name="ocop_link_mua_hang" id="ocop_link_mua_hang" value="<?php echo esc_url($link_mua_hang); ?>" placeholder="https://shopee.vn/... hoặc website bán lẻ">
                    <div class="desc">Nút phụ mở gian hàng trực tuyến của chủ thể OCOP.</div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <div class="ocop-admin-field" style="flex: 1;">
                        <label for="ocop_vi_do">Vĩ Độ (Latitude):</label>
                        <input type="text" name="ocop_vi_do" id="ocop_vi_do" value="<?php echo esc_attr($vi_do); ?>" readonly style="background: #f8fafc; font-weight: bold;">
                    </div>
                    <div class="ocop-admin-field" style="flex: 1;">
                        <label for="ocop_kinh_do">Kinh Độ (Longitude):</label>
                        <input type="text" name="ocop_kinh_do" id="ocop_kinh_do" value="<?php echo esc_attr($kinh_do); ?>" readonly style="background: #f8fafc; font-weight: bold;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bản đồ chọn tọa độ trực quan cho Admin -->
        <div style="margin-top: 15px;">
            <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                <i class="fa-solid fa-crosshairs"></i> Vị Trí Ghim Tọa Độ (Kéo ghim hoặc click trên bản đồ):
            </label>
            <div id="ocop-admin-map-preview"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var latInput = document.getElementById('ocop_vi_do');
            var lngInput = document.getElementById('ocop_kinh_do');
            var mapContainer = document.getElementById('ocop-admin-map-preview');

            if (!mapContainer || typeof L === 'undefined') return;

            var currentLat = parseFloat(latInput.value) || 16.0544;
            var currentLng = parseFloat(lngInput.value) || 108.2022;

            var map = L.map('ocop-admin-map-preview').setView([currentLat, currentLng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap Đà Nẵng OCOP'
            }).addTo(map);

            var marker = L.marker([currentLat, currentLng], {
                draggable: true
            }).addTo(map);

            function updateInputs(lat, lng) {
                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
            }

            marker.on('dragend', function(e) {
                var position = marker.getLatLng();
                updateInputs(position.lat, position.lng);
            });

            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateInputs(e.latlng.lat, e.latlng.lng);
            });

            // Refresh map khi render trong tab admin
            setTimeout(function() {
                map.invalidateSize();
            }, 400);
        });
        </script>
        <?php
    }

    /**
     * Lưu dữ liệu Meta Box an toàn
     */
    public function save_meta_box_data($post_id) {
        // Kiểm tra nonce
        if (!isset($_POST['ocop_meta_box_nonce']) || !wp_verify_nonce($_POST['ocop_meta_box_nonce'], 'ocop_save_meta_box_data')) {
            return;
        }

        // Bỏ qua autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Kiểm tra quyền người dùng
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Lưu và sanitize các trường
        $fields = array(
            'ocop_vi_do'         => 'sanitize_text_field',
            'ocop_kinh_do'        => 'sanitize_text_field',
            'ocop_so_sao'        => 'sanitize_text_field',
            'ocop_dia_chi'       => 'sanitize_text_field',
            'ocop_chu_the'       => 'sanitize_text_field',
            'ocop_so_dien_thoai' => 'sanitize_text_field',
            'ocop_link_dat_lich' => 'esc_url_raw',
            'ocop_link_mua_hang' => 'esc_url_raw',
        );

        foreach ($fields as $field_key => $sanitize_func) {
            if (isset($_POST[$field_key])) {
                $val = call_user_func($sanitize_func, wp_unslash($_POST[$field_key]));
                update_post_meta($post_id, '_' . $field_key, $val);
            }
        }
    }

    /**
     * Cột hiển thị trong trang danh sách Admin
     */
    public function custom_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb']          = $columns['cb'];
        $new_columns['ocop_thumb']  = __('Ảnh', 'ocop-danang-map');
        $new_columns['title']       = __('Tên Cơ Sở / Điểm OCOP', 'ocop-danang-map');
        $new_columns['ocop_stars']  = __('Hạng Sao', 'ocop-danang-map');
        $new_columns['ocop_address']= __('Địa Chỉ', 'ocop-danang-map');
        $new_columns['ocop_coords'] = __('Tọa Độ', 'ocop-danang-map');
        $new_columns['date']        = $columns['date'];
        return $new_columns;
    }

    /**
     * Dữ liệu render cho từng cột trong danh sách Admin
     */
    public function render_admin_column_content($column, $post_id) {
        switch ($column) {
            case 'ocop_thumb':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50), array('style' => 'border-radius: 6px; object-fit: cover;'));
                } else {
                    echo '<span style="display:inline-block;width:50px;height:50px;background:#f1f5f9;border-radius:6px;line-height:50px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-image"></i></span>';
                }
                break;

            case 'ocop_stars':
                $stars = (int) get_post_meta($post_id, '_ocop_so_sao', true);
                if (!$stars) $stars = 3;

                $colors = array(
                    3 => '#64748b', // Bạc
                    4 => '#d97706', // Vàng
                    5 => '#dc2626'  // Đỏ
                );
                $star_color = isset($colors[$stars]) ? $colors[$stars] : '#64748b';

                echo '<span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:12px;background:' . esc_attr($star_color) . '15;color:' . esc_attr($star_color) . ';font-weight:700;font-size:12px;">';
                for ($i = 0; $i < $stars; $i++) {
                    echo '<i class="fa-solid fa-star" style="margin-right:2px;font-size:11px;"></i>';
                }
                echo ' ' . esc_html($stars) . ' Sao</span>';
                break;

            case 'ocop_address':
                $address = get_post_meta($post_id, '_ocop_dia_chi', true);
                echo esc_html($address ? $address : '—');
                break;

            case 'ocop_coords':
                $lat = get_post_meta($post_id, '_ocop_vi_do', true);
                $lng = get_post_meta($post_id, '_ocop_kinh_do', true);
                if ($lat && $lng) {
                    echo '<code style="font-size:11px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">' . esc_html(round((float)$lat, 4)) . ', ' . esc_html(round((float)$lng, 4)) . '</code>';
                } else {
                    echo '<span style="color:#ef4444;font-size:12px;">Chưa ghim</span>';
                }
                break;
        }
    }

    public function sortable_admin_columns($columns) {
        $columns['ocop_stars'] = 'ocop_stars';
        return $columns;
    }
}
