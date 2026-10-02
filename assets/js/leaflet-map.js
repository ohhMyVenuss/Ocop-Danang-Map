/**
 * OCOP Đà Nẵng - Interactive Tourist Map
 * Mobile-First Leaflet Map with Custom Star divIcons, Filter & Smooth Bottom Sheet
 */

document.addEventListener('DOMContentLoaded', function () {
    const mapElement = document.getElementById('ocop-tourist-map');
    if (!mapElement) return;

    // Các thành phần UI
    const bottomSheet = document.getElementById('ocop-bottom-sheet');
    const bottomSheetBackdrop = document.getElementById('ocop-sheet-backdrop');
    const btnCloseSheet = document.getElementById('btn-close-sheet');
    const searchInput = document.getElementById('ocop-search-input');
    const starFilterButtons = document.querySelectorAll('.ocop-star-filter-btn');
    const countBadge = document.getElementById('ocop-count-badge');
    const btnMyLocation = document.getElementById('btn-my-location');
    const loadingOverlay = document.getElementById('ocop-map-loading');

    // Cấu hình tọa độ Đà Nẵng
    const daNangCenter = [16.0544, 108.2022];
    // Giới hạn khung cuộn nghiêm ngặt trong khu vực Đà Nẵng (Nam Ô, Hải Vân, Hòa Vang, Ngũ Hành Sơn, Hoàng Sa)
    const daNangBounds = [
        [15.8200, 107.7500], // Tây Nam (Bà Nà, Nam Đông giáp ranh)
        [16.3200, 108.4500]  // Đông Bắc (Bán đảo Sơn Trà & ven biển)
    ];

    // Khởi tạo Leaflet Map
    const map = L.map('ocop-tourist-map', {
        center: daNangCenter,
        zoom: 12,
        minZoom: 10,
        maxZoom: 18,
        maxBounds: daNangBounds,
        maxBoundsViscosity: 1.0, // Chống trượt ra ngoài vùng biển/ngoài tỉnh
        zoomControl: false       // Đặt lại nút zoom ở góc thuận tiện trên di động
    });

    // Thêm điều khiển Zoom ở góc phải trên
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Bản đồ nền OpenStreetMap với layer đẹp mượt
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> | OCOP Đà Nẵng'
    }).addTo(map);

    // Dữ liệu và trạng thái
    let allLocations = [];
    let markerInstances = [];
    let currentStarFilter = 'all';
    let searchQuery = '';
    let userLocationMarker = null;

    /**
     * Tạo Marker DivIcon chuyên nghiệp phân cấp theo hạng sao
     * TUYỆT ĐỐI KHÔNG DÙNG EMOJI - Sử dụng vector SVG & FontAwesome
     */
    function createStarIcon(stars) {
        let pinColor = '#d97706'; // Mặc định Vàng (4 sao)
        let ringColor = '#fbbf24';
        let starBadgeBg = '#fef3c7';
        let starBadgeText = '#b45309';
        let iconClass = 'fa-star';

        if (stars === 5) {
            // 5 sao: Đỏ Cam / Crimson Red sang trọng
            pinColor = '#e11d48';
            ringColor = '#fda4af';
            starBadgeBg = '#ffe4e6';
            starBadgeText = '#be123c';
        } else if (stars === 3) {
            // 3 sao: Bạc Titan / Slate
            pinColor = '#475569';
            ringColor = '#cbd5e1';
            starBadgeBg = '#f1f5f9';
            starBadgeText = '#334155';
        }

        const html = `
            <div class="ocop-marker-pin group" data-stars="${stars}">
                <div class="relative flex items-center justify-center transition-transform duration-200 hover:scale-110">
                    <!-- Pin Body SVG -->
                    <svg width="40" height="48" viewBox="0 0 40 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="drop-shadow-md">
                        <path d="M20 0C8.954 0 0 8.954 0 20C0 32.5 17.5 46.5 19.167 47.79C19.667 48.18 20.333 48.18 20.833 47.79C22.5 46.5 40 32.5 40 20C40 8.954 31.046 0 20 0Z" fill="${pinColor}"/>
                        <circle cx="20" cy="19" r="14" fill="#ffffff"/>
                    </svg>
                    <!-- Số sao và icon FontAwesome trung tâm -->
                    <div class="absolute top-[8px] flex flex-col items-center justify-center pointer-events-none">
                        <span class="text-[10px] font-black leading-none text-slate-800 tracking-tighter">${stars}</span>
                        <i class="fa-solid ${iconClass} text-[10px]" style="color: ${pinColor}"></i>
                    </div>
                    <!-- Chấm phát sáng -->
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: ${ringColor}"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3" style="background-color: ${pinColor}"></span>
                    </span>
                </div>
            </div>
        `;

        return L.divIcon({
            className: 'ocop-leaflet-div-icon',
            html: html,
            iconSize: [40, 48],
            iconAnchor: [20, 48],
            popupAnchor: [0, -48]
        });
    }

    /**
     * Tải dữ liệu từ REST API
     */
    function fetchOcopLocations() {
        if (loadingOverlay) loadingOverlay.classList.remove('hidden');

        const apiUrl = window.ocopData ? window.ocopData.rest_url : '/wp-json/ocop/v1/locations';

        fetch(apiUrl)
            .then(res => {
                if (!res.ok) throw new Error('Network error: ' + res.status);
                return res.json();
            })
            .then(data => {
                allLocations = data || [];
                renderMarkers(allLocations);
                if (loadingOverlay) loadingOverlay.classList.add('hidden');
            })
            .catch(err => {
                console.error('Lỗi tải dữ liệu OCOP:', err);
                if (loadingOverlay) {
                    loadingOverlay.innerHTML = `
                        <div class="text-center p-4">
                            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-3xl mb-2"></i>
                            <p class="text-sm text-slate-700 font-semibold">Không thể tải dữ liệu bản đồ OCOP</p>
                        </div>
                    `;
                }
            });
    }

    /**
     * Render các Marker lên bản đồ
     */
    function renderMarkers(items) {
        // Xóa markers cũ
        markerInstances.forEach(item => map.removeLayer(item.marker));
        markerInstances = [];

        items.forEach(item => {
            if (!item.lat || !item.lng) return;

            const icon = createStarIcon(item.stars);
            const marker = L.marker([item.lat, item.lng], { icon: icon }).addTo(map);

            // Bắt sự kiện Click Marker
            marker.on('click', function () {
                openLocationDetails(item, marker);
            });

            markerInstances.push({
                data: item,
                marker: marker
            });
        });

        updateCountBadge(items.length);
    }

    /**
     * Cập nhật số lượng điểm hiển thị
     */
    function updateCountBadge(count) {
        if (countBadge) {
            countBadge.textContent = `${count} điểm OCOP`;
        }
    }

    /**
     * Mở Bottom Sheet (Mobile) hoặc Card Details
     */
    function openLocationDetails(item, marker) {
        // Highlight vị trí trên bản đồ, pan nhẹ lên trên để Bottom Sheet không che mất ghim
        const targetZoom = Math.max(map.getZoom(), 14);
        map.setView([item.lat, item.lng], targetZoom, { animate: true });

        // Cập nhật thông tin vào Bottom Sheet
        const titleEl = document.getElementById('sheet-title');
        const imageEl = document.getElementById('sheet-image');
        const starsBadgeEl = document.getElementById('sheet-stars-badge');
        const starsRatingEl = document.getElementById('sheet-stars-rating');
        const categoryEl = document.getElementById('sheet-category');
        const addressEl = document.getElementById('sheet-address');
        const coopEl = document.getElementById('sheet-coop');
        const phoneEl = document.getElementById('sheet-phone');
        const phoneContainer = document.getElementById('sheet-phone-container');
        const descEl = document.getElementById('sheet-desc');
        const btnBooking = document.getElementById('sheet-btn-booking');
        const btnShop = document.getElementById('sheet-btn-shop');
        const btnDirections = document.getElementById('sheet-btn-directions');

        if (titleEl) titleEl.textContent = item.title;
        if (categoryEl) categoryEl.textContent = item.category || 'Đặc sản OCOP';
        if (addressEl) addressEl.textContent = item.address || 'Đà Nẵng';
        if (coopEl) coopEl.textContent = item.cooperative || item.title;
        if (descEl) descEl.textContent = item.description || 'Chưa có thông tin mô tả chi tiết.';

        // Ảnh cơ sở
        if (imageEl) {
            if (item.image) {
                imageEl.src = item.image;
                imageEl.alt = item.title;
                imageEl.classList.remove('hidden');
            } else {
                imageEl.classList.add('hidden');
            }
        }

        // Xử lý Hạng sao & FontAwesome Stars
        if (starsRatingEl) {
            let starIcons = '';
            for (let i = 0; i < item.stars; i++) {
                starIcons += '<i class="fa-solid fa-star text-amber-400"></i> ';
            }
            starsRatingEl.innerHTML = starIcons;
        }

        if (starsBadgeEl) {
            const badgeConfigs = {
                5: { bg: 'bg-rose-50 text-rose-700 border-rose-200', text: 'OCOP 5 Sao (Quốc Gia)' },
                4: { bg: 'bg-amber-50 text-amber-700 border-amber-200', text: 'OCOP 4 Sao (Cấp Tỉnh/TP)' },
                3: { bg: 'bg-slate-100 text-slate-700 border-slate-300', text: 'OCOP 3 Sao (Quận/Huyện)' }
            };
            const cfg = badgeConfigs[item.stars] || badgeConfigs[4];
            starsBadgeEl.className = `inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border ${cfg.bg}`;
            starsBadgeEl.innerHTML = `<i class="fa-solid fa-award"></i> ${cfg.text}`;
        }

        // Hotline
        if (phoneContainer && phoneEl) {
            if (item.phone) {
                phoneEl.textContent = item.phone;
                phoneEl.href = 'tel:' + item.phone;
                phoneContainer.classList.remove('hidden');
            } else {
                phoneContainer.classList.add('hidden');
            }
        }

        // Nút ĐẶT LỊCH TRẢI NGHIỆM (Nổi bật, button to, icon lịch)
        if (btnBooking) {
            if (item.booking_url) {
                btnBooking.href = item.booking_url;
                btnBooking.target = '_blank';
                btnBooking.classList.remove('hidden');
            } else {
                // Fallback gọi hotline nếu không có link web
                btnBooking.href = item.phone ? `tel:${item.phone}` : '#';
                btnBooking.target = item.phone ? '_self' : '';
                btnBooking.classList.remove('hidden');
            }
        }

        // Nút MUA SẢN PHẨM (Button outline, icon giỏ hàng)
        if (btnShop) {
            if (item.shop_url) {
                btnShop.href = item.shop_url;
                btnShop.target = '_blank';
                btnShop.classList.remove('hidden');
            } else {
                btnShop.classList.add('hidden');
            }
        }

        // Nút DẪN ĐƯỜNG GOOGLE MAPS
        if (btnDirections) {
            btnDirections.href = `https://www.google.com/maps/dir/?api=1&destination=${item.lat},${item.lng}`;
            btnDirections.target = '_blank';
        }

        // Hiển thị Bottom Sheet (Mobile) & Modal Card (PC)
        if (bottomSheet) {
            bottomSheet.classList.remove('translate-y-full', 'opacity-0', 'pointer-events-none');
            bottomSheet.classList.add('translate-y-0', 'opacity-100');
        }
        if (bottomSheetBackdrop) {
            bottomSheetBackdrop.classList.remove('opacity-0', 'pointer-events-none');
            bottomSheetBackdrop.classList.add('opacity-100');
        }
    }

    /**
     * Đóng Bottom Sheet
     */
    function closeBottomSheet() {
        if (bottomSheet) {
            bottomSheet.classList.remove('translate-y-0', 'opacity-100');
            bottomSheet.classList.add('translate-y-full', 'opacity-0', 'pointer-events-none');
        }
        if (bottomSheetBackdrop) {
            bottomSheetBackdrop.classList.remove('opacity-100');
            bottomSheetBackdrop.classList.add('opacity-0', 'pointer-events-none');
        }
    }

    if (btnCloseSheet) {
        btnCloseSheet.addEventListener('click', closeBottomSheet);
    }
    if (bottomSheetBackdrop) {
        bottomSheetBackdrop.addEventListener('click', closeBottomSheet);
    }

    // Phím Escape để đóng
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeBottomSheet();
    });

    /**
     * Lọc và tìm kiếm dữ liệu trên Client-side
     */
    function applyFilters() {
        let filtered = allLocations.filter(item => {
            // Lọc theo sao
            const matchStars = (currentStarFilter === 'all') || (item.stars === parseInt(currentStarFilter, 10));

            // Lọc theo tìm kiếm từ khóa
            let matchSearch = true;
            if (searchQuery.trim() !== '') {
                const q = searchQuery.toLowerCase().trim();
                matchSearch = (
                    (item.title && item.title.toLowerCase().includes(q)) ||
                    (item.address && item.address.toLowerCase().includes(q)) ||
                    (item.cooperative && item.cooperative.toLowerCase().includes(q)) ||
                    (item.category && item.category.toLowerCase().includes(q))
                );
            }

            return matchStars && matchSearch;
        });

        renderMarkers(filtered);
    }

    // Sự kiện bộ lọc Hạng Sao
    starFilterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            starFilterButtons.forEach(b => {
                b.classList.remove('bg-slate-900', 'text-white', 'shadow');
                b.classList.add('bg-white', 'text-slate-700');
            });
            this.classList.remove('bg-white', 'text-slate-700');
            this.classList.add('bg-slate-900', 'text-white', 'shadow');

            currentStarFilter = this.getAttribute('data-stars');
            applyFilters();
        });
    });

    // Sự kiện tìm kiếm Realtime
    if (searchInput) {
        let debounceTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchQuery = searchInput.value;
                applyFilters();
            }, 250);
        });
    }

    /**
     * Định vị vị trí hiện tại của Du khách
     */
    if (btnMyLocation) {
        btnMyLocation.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Trình duyệt không hỗ trợ Geolocation.');
                return;
            }

            const origHtml = btnMyLocation.innerHTML;
            btnMyLocation.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    btnMyLocation.innerHTML = origHtml;
                    const uLat = pos.coords.latitude;
                    const uLng = pos.coords.longitude;

                    if (!userLocationMarker) {
                        const userIcon = L.divIcon({
                            className: 'ocop-user-location-pulse',
                            html: `
                                <div class="relative flex items-center justify-center">
                                    <div class="w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow-md"></div>
                                    <div class="absolute -inset-1 rounded-full bg-blue-400 opacity-60 animate-ping"></div>
                                </div>
                            `,
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });

                        userLocationMarker = L.marker([uLat, uLng], { icon: userIcon }).addTo(map);
                        userLocationMarker.bindPopup('<strong>Vị trí của bạn</strong>').openPopup();
                    } else {
                        userLocationMarker.setLatLng([uLat, uLng]);
                    }

                    map.setView([uLat, uLng], 14, { animate: true });
                },
                function (err) {
                    btnMyLocation.innerHTML = origHtml;
                    alert('Không thể định vị vị trí: ' + err.message);
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    }

    // Khởi động fetch dữ liệu
    fetchOcopLocations();

    // Hỗ trợ resize mượt mà
    window.addEventListener('resize', function () {
        map.invalidateSize();
    });
});
