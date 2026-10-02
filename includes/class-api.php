<?php
/**
 * REST API Endpoint cho Bản đồ OCOP Đà Nẵng
 * Cung cấp dữ liệu JSON chuẩn hóa cho Frontend tương tác
 */

if (!defined('ABSPATH')) {
    exit;
}

class OCOP_API {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Đăng ký REST Route: /wp-json/ocop/v1/locations
     */
    public function register_routes() {
        register_rest_route('ocop/v1', '/locations', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_locations'),
            'permission_callback' => '__return_true', // Công khai cho khách du lịch tra cứu
        ));
    }

    /**
     * Lấy danh sách các điểm OCOP đã duyệt (post_status = 'publish')
     */
    public function get_locations($request) {
        // Query bài viết điểm OCOP đã công khai
        $query_args = array(
            'post_type'      => 'diem_ocop',
            'post_status'    => 'publish', // Chỉ lấy điểm đã được Admin duyệt
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Hỗ trợ lọc theo số sao nếu có query param ?stars=4
        $stars_filter = $request->get_param('stars');
        if (!empty($stars_filter) && in_array((int)$stars_filter, array(3, 4, 5), true)) {
            $query_args['meta_query'] = array(
                array(
                    'key'     => '_ocop_so_sao',
                    'value'   => (string)$stars_filter,
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query($query_args);
        $locations = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $lat = get_post_meta($post_id, '_ocop_vi_do', true);
                $lng = get_post_meta($post_id, '_ocop_kinh_do', true);

                // Chỉ trả về nếu có tọa độ hợp lệ
                if (empty($lat) || empty($lng)) {
                    continue;
                }

                // Lấy ảnh đại diện hoặc fallback ảnh mặc định
                $image_url = '';
                if (has_post_thumbnail($post_id)) {
                    $thumb_src = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'large');
                    if ($thumb_src) {
                        $image_url = $thumb_src[0];
                    }
                }
                if (empty($image_url)) {
                    $image_url = OCOP_MAP_PLUGIN_URL . 'assets/images/placeholder.svg';
                }

                // Lấy danh mục ngành hàng OCOP
                $terms = get_the_terms($post_id, 'ocop_danh_muc');
                $category_names = array();
                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $t) {
                        $category_names[] = $t->name;
                    }
                }
                $category_str = !empty($category_names) ? implode(', ', $category_names) : 'Đặc sản OCOP';

                $stars = (int) get_post_meta($post_id, '_ocop_so_sao', true);
                if ($stars < 3 || $stars > 5) {
                    $stars = 4; // Mặc định 4 sao
                }

                $booking_url = get_post_meta($post_id, '_ocop_link_dat_lich', true);
                $shop_url    = get_post_meta($post_id, '_ocop_link_mua_hang', true);
                $phone       = get_post_meta($post_id, '_ocop_so_dien_thoai', true);
                $cooperative = get_post_meta($post_id, '_ocop_chu_the', true);
                $address     = get_post_meta($post_id, '_ocop_dia_chi', true);

                $description = get_the_excerpt();
                if (empty($description)) {
                    $description = wp_trim_words(wp_strip_all_tags(get_the_content()), 28, '...');
                }

                $locations[] = array(
                    'id'          => $post_id,
                    'title'       => get_the_title(),
                    'slug'        => get_post_field('post_name', $post_id),
                    'lat'         => (float) $lat,
                    'lng'         => (float) $lng,
                    'stars'       => $stars,
                    'category'    => esc_html($category_str),
                    'address'     => esc_html($address ? $address : 'Đà Nẵng'),
                    'cooperative' => esc_html($cooperative ? $cooperative : get_the_title()),
                    'phone'       => esc_html($phone ? $phone : ''),
                    'description' => esc_html($description),
                    'image'       => esc_url($image_url),
                    'booking_url' => esc_url_raw($booking_url),
                    'shop_url'    => esc_url_raw($shop_url),
                    'permalink'   => esc_url(get_permalink($post_id)),
                );
            }
            wp_reset_postdata();
        }

        $response = new WP_REST_Response($locations, 200);
        $response->header('Cache-Control', 'public, max-age=180'); // Cache nhẹ 3 phút tăng tốc
        return $response;
    }
}
