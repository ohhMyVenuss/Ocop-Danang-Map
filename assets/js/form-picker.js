/**
 * OCOP Đà Nẵng - Form Map Picker & AJAX Submission
 * Mobile-First, Touch-Friendly Leaflet Integration
 */

document.addEventListener('DOMContentLoaded', function () {
    const mapContainer = document.getElementById('ocop-form-map');
    const formElement = document.getElementById('ocop-registration-form');

    if (!mapContainer || !formElement) {
        return;
    }

    // Tọa độ mặc định: Trung tâm Đà Nẵng
    const defaultLat = (window.ocopData && window.ocopData.default_lat) ? parseFloat(window.ocopData.default_lat) : 16.0544;
    const defaultLng = (window.ocopData && window.ocopData.default_lng) ? parseFloat(window.ocopData.default_lng) : 108.2022;

    const latInput = document.getElementById('ocop_form_lat');
    const lngInput = document.getElementById('ocop_form_lng');
    const coordsDisplay = document.getElementById('ocop-coords-display');
    const gpsBtn = document.getElementById('btn-ocop-get-gps');

    // 1. Khởi tạo Leaflet Map cho Form
    const map = L.map('ocop-form-map', {
        center: [defaultLat, defaultLng],
        zoom: 12,
        zoomControl: true,
        scrollWheelZoom: false, // Tránh kẹt cuộn trang trên mobile
        tap: true
    });

    // Thêm TileLayer OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap Đà Nẵng OCOP'
    }).addTo(map);

    let marker = null;

    // Custom Icon Pin chuyên nghiệp bằng FontAwesome (Không dùng emoji)
    const customPickerIcon = L.divIcon({
        className: 'ocop-picker-pin-wrapper',
        html: `
            <div class="relative flex items-center justify-center -translate-x-1/2 -translate-y-full cursor-pointer">
                <div class="w-10 h-10 rounded-full bg-emerald-600 border-2 border-white shadow-lg flex items-center justify-center text-white text-base">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <div class="absolute -bottom-1 w-2.5 h-2.5 bg-emerald-700 rotate-45"></div>
            </div>
        `,
        iconSize: [40, 48],
        iconAnchor: [20, 48]
    });

    // Hàm cập nhật tọa độ vào hidden inputs và hiển thị UI
    function setCoordinates(lat, lng, shouldPan = false) {
        const fixedLat = lat.toFixed(6);
        const fixedLng = lng.toFixed(6);

        latInput.value = fixedLat;
        lngInput.value = fixedLng;

        if (coordsDisplay) {
            coordsDisplay.innerHTML = `<span class="text-emerald-700 font-bold">Đã chọn: ${fixedLat}, ${fixedLng}</span>`;
        }

        if (!marker) {
            marker = L.marker([lat, lng], {
                icon: customPickerIcon,
                draggable: true
            }).addTo(map);

            marker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                setCoordinates(pos.lat, pos.lng, false);
            });
        } else {
            marker.setLatLng([lat, lng]);
        }

        if (shouldPan) {
            map.setView([lat, lng], 15, { animate: true });
        }
    }

    // Bắt sự kiện Click / Chạm trên bản đồ
    map.on('click', function (e) {
        setCoordinates(e.latlng.lat, e.latlng.lng, false);
    });

    // 2. Nút định vị GPS hiện tại của người dùng
    if (gpsBtn) {
        gpsBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!navigator.geolocation) {
                alert('Trình duyệt của bạn không hỗ trợ định vị GPS.');
                return;
            }

            const originalHtml = gpsBtn.innerHTML;
            gpsBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> Đang tìm...';
            gpsBtn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    setCoordinates(lat, lng, true);
                    gpsBtn.innerHTML = '<i class="fa-solid fa-check text-emerald-600"></i> Đã lấy GPS';
                    setTimeout(() => {
                        gpsBtn.innerHTML = originalHtml;
                        gpsBtn.disabled = false;
                    }, 2500);
                },
                function (error) {
                    alert('Không thể xác định vị trí GPS: ' + error.message);
                    gpsBtn.innerHTML = originalHtml;
                    gpsBtn.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }

    // 3. Tương tác chọn file ảnh xem trước tên file
    const fileInput = document.getElementById('featured_image');
    const fileChosenName = document.getElementById('file-chosen-name');
    if (fileInput && fileChosenName) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                fileChosenName.textContent = 'Đã chọn ảnh: ' + this.files[0].name;
                fileChosenName.classList.remove('hidden');
            } else {
                fileChosenName.classList.add('hidden');
            }
        });
    }

    // 4. Xử lý Submit Form bằng AJAX
    const alertBox = document.getElementById('ocop-form-alert');
    const alertIcon = document.getElementById('ocop-alert-icon');
    const alertText = document.getElementById('ocop-alert-text');
    const submitBtn = document.getElementById('btn-submit-ocop');
    const spinner = document.getElementById('btn-submit-spinner');
    const submitText = document.getElementById('btn-submit-text');

    function showAlert(type, message) {
        if (!alertBox) return;
        alertBox.className = 'mb-6 p-4 rounded-xl text-sm font-medium transition-all duration-300';
        if (type === 'success') {
            alertBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
            alertIcon.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600"></i>';
        } else {
            alertBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
            alertIcon.innerHTML = '<i class="fa-solid fa-circle-exclamation text-rose-600"></i>';
        }
        alertText.innerHTML = message;
        alertBox.classList.remove('hidden');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    formElement.addEventListener('submit', function (e) {
        e.preventDefault();

        // Kiểm tra tọa độ trước khi gửi
        if (!latInput.value || !lngInput.value) {
            showAlert('error', '<strong>Chưa chọn vị trí:</strong> Vui lòng chạm hoặc click lên bản đồ để chọn tọa độ cơ sở tại Đà Nẵng.');
            mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // Bật trạng thái loading
        submitBtn.disabled = true;
        spinner.classList.remove('hidden');
        submitText.innerHTML = 'Đang xử lý hồ sơ...';

        const formData = new FormData(formElement);
        formData.append('action', 'ocop_submit_registration');

        fetch(window.ocopData.ajax_url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
                submitText.innerHTML = '<i class="fa-solid fa-paper-plane text-lg"></i> GỬI HỒ SƠ ĐĂNG KÝ NGAY';

                if (data.success) {
                    showAlert('success', data.data.message || window.ocopData.i18n.submit_success);
                    formElement.reset();
                    if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    latInput.value = '';
                    lngInput.value = '';
                    if (coordsDisplay) {
                        coordsDisplay.textContent = 'Chạm vào bản đồ để chọn';
                    }
                    if (fileChosenName) {
                        fileChosenName.classList.add('hidden');
                    }
                } else {
                    showAlert('error', data.data.message || window.ocopData.i18n.submit_error);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
                submitText.innerHTML = '<i class="fa-solid fa-paper-plane text-lg"></i> GỬI HỒ SƠ ĐĂNG KÝ NGAY';
                showAlert('error', 'Có lỗi kết nối mạng, vui lòng thử lại sau giây lát!');
                console.error('OCOP Form Submit Error:', error);
            });
    });

    // Invalidate size sau khi render
    setTimeout(() => {
        map.invalidateSize();
    }, 300);
});
