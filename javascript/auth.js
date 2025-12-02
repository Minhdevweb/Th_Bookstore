// auth.js - Xử lý logic Đăng nhập/Đăng ký/Đăng xuất bằng AJAX
document.addEventListener('DOMContentLoaded', function() {
    const AUTH_URL = 'auth.php'; // Đảm bảo file auth.php đã tồn tại và chứa logic tổng hợp

    // Elements chung
    const loginModal = document.getElementById('loginModal');
    const regModal = document.getElementById('regModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const openAuthModalBtn = document.getElementById('openAuthModalBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const addProductBtn = document.getElementById('addProductBtn'); // Nút Admin

    // Login Form Elements
    const loginForm = document.getElementById('loginForm');
    const loginMessage = document.getElementById('loginMessage');
    const switchToRegisterLink = document.getElementById('switchToRegister');

    // Register Form Elements
    const registerForm = document.getElementById('registerForm');
    const regConfirmPwd = document.getElementById('regConfirm');
    const regPwd = document.getElementById('regPwd');
    const registerMessage = document.getElementById('registerMessage');
    const switchToLoginLink = document.getElementById('switchToLogin');

    // Hàm chung để mở modal
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
        modalBackdrop.classList.add('active');
    }

    // Hàm chung để đóng modal
    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
        modalBackdrop.classList.remove('active');
    }

    // Hàm hiển thị thông báo
    function showMessage(element, message, type) {
        element.textContent = message;
        element.className = 'message-area ' + type; // Sử dụng class .success hoặc .error
        element.style.display = 'block';
    }

    // Xử lý nút mở Modal Tài khoản
    if (openAuthModalBtn) {
        openAuthModalBtn.addEventListener('click', function() {
            openModal('loginModal'); // Mặc định mở form Đăng nhập
            loginMessage.style.display = 'none'; // Đảm bảo ẩn thông báo
        });
    }

    // Xử lý nút đóng Modal (nút X trên modal)
    document.querySelectorAll('.close').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = button.getAttribute('data-close');
            closeModal(modalId);
        });
    });

    // Chuyển đổi giữa Đăng nhập và Đăng ký
    if (switchToRegisterLink) {
        switchToRegisterLink.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal('loginModal');
            openModal('regModal');
            registerMessage.style.display = 'none';
        });
    }

    if (switchToLoginLink) {
        switchToLoginLink.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal('regModal');
            openModal('loginModal');
            loginMessage.style.display = 'none';
        });
    }

    // --------------------------------------------------------------------------------
    // 1. XỬ LÝ ĐĂNG KÝ (AJAX)
    // --------------------------------------------------------------------------------
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (regPwd.value !== regConfirmPwd.value) {
            showMessage(registerMessage, 'Mật khẩu và Xác nhận mật khẩu không khớp.', 'error');
            return;
        }

        const formData = new FormData(registerForm);
        formData.append('action', 'register');

        fetch(AUTH_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage(registerMessage, 'Đăng ký thành công! Đang chuyển sang Đăng nhập...', 'success');
                setTimeout(() => {
                    closeModal('regModal');
                    openModal('loginModal');
                }, 1500);
            } else {
                showMessage(registerMessage, data.message || 'Đăng ký thất bại. Vui lòng thử lại.', 'error');
            }
        })
        .catch(() => {
            showMessage(registerMessage, 'Lỗi kết nối mạng hoặc máy chủ.', 'error');
        });
    });

    // --------------------------------------------------------------------------------
    // 2. XỬ LÝ ĐĂNG NHẬP (AJAX)
    // --------------------------------------------------------------------------------
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(loginForm);
        formData.append('action', 'login');

        fetch(AUTH_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showMessage(loginMessage, 'Đăng nhập thành công! Đang tải lại trang...', 'success');
                setTimeout(() => {
                    // Tải lại trang để PHP hiển thị các nút dựa trên $_SESSION mới
                    window.location.reload(); 
                }, 1000);
            } else {
                showMessage(loginMessage, data.message || 'Email hoặc mật khẩu không hợp lệ.', 'error');
            }
        })
        .catch(() => {
            showMessage(loginMessage, 'Lỗi kết nối mạng hoặc máy chủ.', 'error');
        });
    });

    // --------------------------------------------------------------------------------
    // 3. XỬ LÝ ĐĂNG XUẤT (AJAX)
    // --------------------------------------------------------------------------------
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'logout'); 

            fetch(AUTH_URL, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Tải lại trang để PHP loại bỏ các nút Admin/User
                    window.location.reload(); 
                } else {
                    alert('Lỗi đăng xuất. Vui lòng thử lại.');
                }
            })
            .catch(() => {
                alert('Lỗi kết nối mạng khi đăng xuất.');
            });
        });
    }

    // --------------------------------------------------------------------------------
    // 4. XỬ LÝ CÁC MODAL KHÁC (Chỉ cho mục đích đóng/mở)
    // --------------------------------------------------------------------------------
    // Các nút mở modal khác (cart, addProduct, orders) có thể được xử lý trong main.js/cart.js/orders.js
    // Nhưng bạn cần đảm bảo các hàm `openModal` và `closeModal` ở đây hoặc ở `modal.js` mới hoạt động.
    
    // Nếu bạn muốn giữ lại file modal.js cũ, hãy đảm bảo rằng logic DOMContentLoaded không bị xung đột.
    // Nếu bạn xóa modal.js, các hàm này cần được thêm vào global scope (ví dụ: window.openModal) 
    // hoặc bạn phải xử lý tất cả các nút mở modal trong auth.js/main.js.
});