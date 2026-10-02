# OCOP Đà Nẵng Map - Bản Đồ & Đăng Ký Điểm OCOP

Plugin WordPress độc lập phục vụ mạng lưới **Du lịch trải nghiệm và Xúc tiến thương mại OCOP TP. Đà Nẵng**. Thiết kế chuẩn kiến trúc **Mobile-first**, giao diện hiện đại với **Tailwind CSS**, hệ thống biểu tượng **FontAwesome 6**, và bản đồ tương tác đa năng **Leaflet.js** (OpenStreetMap).

---

## 🌟 Tính Năng Nổi Bật (Senior UI/UX Standards)

1. **Giao diện Di động Thông minh (Mobile-First)**:
   - Tối ưu hóa 100% cảm ứng (Touch UI). Nút bấm lớn, padding rộng rãi, hỗ trợ cử chỉ vuốt chạm mượt mà.
   - Thẻ thông tin điểm đến trên di động hiển thị dạng **Bottom Sheet** (trượt từ đáy màn hình lên), tích hợp thanh kéo vuốt, nền làm mờ Backdrop, đóng mở tự nhiên thay thế hoàn toàn dạng bóng bay (balloon popup) truyền thống của Leaflet.
2. **Hệ Thống Icon Chuẩn Vector (FontAwesome 6)**:
   - **Tuyệt đối không dùng text emoji** (như ⭐, 📍, 🛒) – toàn bộ icon nút, sao xếp hạng và ghim bản đồ đều là vector sắc nét.
3. **Phân Cấp Ghim Theo Hạng Sao OCOP**:
   - **5 Sao (Quốc gia)**: Ghim Đỏ Cam / Crimson Red (`#e11d48`) kèm hiệu ứng phát sáng Pulse.
   - **4 Sao (Cấp Tỉnh/TP)**: Ghim Vàng Gold / Amber (`#d97706`).
   - **3 Sao (Cấp Huyện/Quận)**: Ghim Bạc Titan / Slate (`#475569`).
4. **Giới Hạn Bản Đồ Đà Nẵng (`maxBounds`)**:
   - Khóa chặt khung cuộn và độ phóng to/thu nhỏ trong phạm vi địa giới hành chính Đà Nẵng (Hải Vân, Sơn Trà, Hòa Vang, Ngũ Hành Sơn), ngăn du khách vuốt nhầm ra ngoài biển Đông.
5. **Hệ Thống Phê Duyệt An Toàn Chống Spam**:
   - Khi chủ Hợp tác xã (HTX) đăng ký ngoài Frontend, bài viết sẽ lưu ở trạng thái **Chờ duyệt (Pending)**. Quản trị viên kiểm duyệt thông tin trước khi điểm đến chính thức xuất hiện trên bản đồ số.
6. **Không Phụ Thuộc Plugin Bên Thứ 3**:
   - Sử dụng Custom Post Type và Meta Box PHP thuần, tích hợp mini-map chọn tọa độ ngay trong trang quản trị WP Admin, không cần cài đặt ACF hay Metabox.

---

## 📁 Cấu Trúc Thư Mục Plugin

```
ocop-danang-map/
├── ocop-danang-map.php        # File khởi tạo chính, enqueue assets, đăng ký Shortcode
├── README.md                 # Tài liệu hướng dẫn sử dụng & triển khai
├── includes/
│   ├── class-cpt.php         # Quản lý CPT 'diem_ocop', Taxonomy, Meta Box & Cột Admin
│   ├── class-api.php         # REST API endpoint /wp-json/ocop/v1/locations
│   └── class-form-handler.php# Shortcode form đăng ký HTX, Leaflet mini-map, xử lý AJAX
└── assets/
    ├── css/
    │   └── style.css         # Reset xung đột theme WP, CSS Bottom sheet & animations
    ├── js/
    │   ├── leaflet-map.js    # Logic bản đồ du khách, bộ lọc sao, tìm kiếm & Bottom Sheet
    │   └── form-picker.js    # Logic thả ghim tọa độ, GPS và submit AJAX trong form HTX
    └── images/
        └── placeholder.svg   # Ảnh vector mặc định khi cơ sở chưa có ảnh đại diện
```

---

## 🚀 Hướng Dẫn Cài Đặt (Installation)

### Cách 1: Tải trực tiếp vào thư mục WordPress
1. Copy hoặc tải toàn bộ thư mục `ocop-danang-map` vào đường dẫn:
   ```
   wp-content/plugins/ocop-danang-map
   ```
2. Đăng nhập vào trang quản trị WordPress (**WP Admin**).
3. Vào mục **Plugins (Gói mở rộng)** -> **Installed Plugins (Đã cài đặt)**.
4. Tìm plugin **OCOP Đà Nẵng Map - Bản Đồ & Đăng Ký Điểm OCOP** và bấm **Kích hoạt (Activate)**.

### Cách 2: Nén ZIP để Upload
1. Nén toàn bộ thư mục `ocop-danang-map` thành file `ocop-danang-map.zip`.
2. Vào **WP Admin** -> **Plugins** -> **Add New (Cài mới)** -> **Upload Plugin**.
3. Chọn file `ocop-danang-map.zip`, bấm **Install Now** và **Activate**.

---

## 🧩 Hướng Dẫn Chèn Shortcode Bằng Elementor

Plugin cung cấp 2 shortcode độc lập, dễ dàng chèn vào bất kỳ trang nào bằng trình dựng trang **Elementor** hoặc **Gutenberg**:

### 1. Bản Đồ Tương Tác Dành Cho Du Khách (`[ocop_interactive_map]`)
- **Mục đích**: Hiển thị bản đồ Đà Nẵng, bộ lọc sao, thanh tìm kiếm, và Bottom Sheet chi tiết điểm đến.
- **Cách chèn trong Elementor**:
  1. Mở trang bạn muốn hiển thị bản đồ bằng **Edit with Elementor**.
  2. Tại thanh công cụ widget bên trái, tìm kiếm widget **Shortcode** (hoặc **Mã ngắn**).
  3. Kéo widget **Shortcode** vào vị trí mong muốn trên trang.
  4. Nhập shortcode:
     ```text
     [ocop_interactive_map]
     ```
  5. *Tùy chọn tùy biến chiều cao & tiêu đề:*
     ```text
     [ocop_interactive_map height="700px" title="Bản Đồ Điểm Trải Nghiệm OCOP Đà Nẵng"]
     ```
  6. Bấm **Update (Cập nhật)** để xuất bản trang.

---

### 2. Form Đăng Ký Dành Cho Chủ Hợp Tác Xã (`[ocop_register_form]`)
- **Mục đích**: Hiển thị form đăng ký hiện đại, tích hợp bản đồ nhỏ chấm tọa độ Lat/Lng và upload ảnh sản phẩm.
- **Cách chèn trong Elementor**:
  1. Tạo hoặc chỉnh sửa trang (Ví dụ: `Trang Đăng Ký Tham Gia Mạng Lưới OCOP`).
  2. Kéo thả widget **Shortcode** vào trang.
  3. Nhập shortcode:
     ```text
     [ocop_register_form]
     ```
  4. Bấm **Update (Cập nhật)** để hoàn tất.

---

## 🔄 Quy Trình Vận Hành & Quản Trị (Workflow)

```
[Chủ HTX Điền Form & Chấm Tọa Độ Trên Bản Đồ]
                      │
                      ▼
[Hệ thống tự động lưu bài viết ở trạng thái: PENDING (Chờ duyệt)]
                      │
                      ▼
[Admin vào WP Admin -> "Điểm OCOP Đà Nẵng" kiểm tra thông tin, ảnh, tọa độ]
                      │
                      ▼
[Admin chuyển trạng thái sang "Đã xuất bản (Publish)"]
                      │
                      ▼
[REST API tự động đồng bộ sang Bản Đồ Tương Tác cho Du khách]
```

1. **Tiếp nhận hồ sơ**: Khi chủ cơ sở gửi form, hồ sơ sẽ nằm trong mục **Điểm OCOP Đà Nẵng** -> **Tất cả Điểm OCOP** với nhãn **Pending (Chờ duyệt)**.
2. **Kiểm duyệt & Chỉnh sửa**: Quản trị viên click vào bài viết để kiểm tra tên cơ sở, số điện thoại, link Shopee, link đặt lịch và xem trước vị trí trên bản đồ mini trong Admin. Có thể kéo ghim để tinh chỉnh tọa độ nếu cần.
3. **Phê duyệt**: Bấm **Publish (Xuất bản)**. Điểm đến sẽ xuất hiện ngay lập tức trên bản đồ du khách ngoài Frontend mà không cần cấu hình thêm.

---

## 📡 REST API Endpoint

- **URL**: `GET /wp-json/ocop/v1/locations`
- **Bộ lọc**: `GET /wp-json/ocop/v1/locations?stars=5`
- **Dữ liệu trả về**: Mảng JSON chứa ID, tên điểm đến, tọa độ `lat`/`lng`, hạng sao `stars`, địa chỉ, hotline, link đặt lịch trải nghiệm, link gian hàng và ảnh đại diện.

---

## 🛡️ Bản Quyền & Giấy Phép
Dự án được phát triển chuyên biệt cho ngành Du lịch nông nghiệp & Xúc tiến thương mại Thành phố Đà Nẵng. Giấy phép mã nguồn mở GPL-2.0+.
