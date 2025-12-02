// --- 1. JAVASCRIPT TẠO HIỆU ỨNG MƯA ICONS (MỚI) ---
        function createRain() {
            const body = document.querySelector('body');
            const icon = document.createElement('i');
            
            // Danh sách các icon sẽ rơi (Sách, bút, kính, sao, tim...)
            const iconsList = [
                'fa-book', 'fa-book-open', 'fa-pen-nib', 
                'fa-glasses', 'fa-graduation-cap', 'fa-bookmark',
                'fa-star', 'fa-heart'
            ];
            
            // Chọn ngẫu nhiên 1 icon
            const randomIcon = iconsList[Math.floor(Math.random() * iconsList.length)];
            icon.classList.add('fas', randomIcon, 'falling-icon');

            // Vị trí ngẫu nhiên theo chiều ngang
            icon.style.left = Math.random() * 100 + 'vw';
            
            // Kích thước ngẫu nhiên (từ 10px đến 30px)
            const size = Math.random() * 20 + 10; 
            icon.style.fontSize = size + 'px';
            
            // Thời gian rơi ngẫu nhiên (từ 3s đến 8s)
            const duration = Math.random() * 5 + 3;
            icon.style.animationDuration = duration + 's';

            body.appendChild(icon);

            // Xóa icon sau khi rơi xong để tránh nặng máy
            setTimeout(() => {
                icon.remove();
            }, duration * 1000);
        }

        // Tạo icon mới mỗi 300ms (bạn có thể chỉnh số này để mưa dày hơn hoặc thưa hơn)
        setInterval(createRain, 300);


        // --- 2. LOGIC ĐĂNG NHẬP / ĐĂNG KÝ (GIỮ NGUYÊN) ---
        const loginBox = document.getElementById('login-box');
        const regBox = document.getElementById('register-box');
        const loginMsg = document.getElementById('loginMsg');
        const regMsg = document.getElementById('regMsg');

        function showRegister() {
            loginBox.style.display = 'none';
            regBox.style.display = 'block';
            loginMsg.style.display = 'none';
        }

        function showLogin() {
            regBox.style.display = 'none';
            loginBox.style.display = 'block';
            regMsg.style.display = 'none';
        }

        function showMessage(element, text, type) {
            element.textContent = text;
            element.className = 'message ' + type;
            element.style.display = 'block';
        }

        document.getElementById('formLogin').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'login');

            fetch('auth.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage(loginMsg, 'Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else {
                    showMessage(loginMsg, data.message || 'Lỗi đăng nhập.', 'error');
                }
            })
            .catch(() => showMessage(loginMsg, 'Lỗi kết nối server.', 'error'));
        });

        document.getElementById('formRegister').addEventListener('submit', function(e) {
            e.preventDefault();
            if (document.getElementById('regPwd').value !== document.getElementById('regConfirm').value) {
                showMessage(regMsg, 'Mật khẩu xác nhận không khớp.', 'error');
                return;
            }

            const formData = new FormData(this);
            formData.append('action', 'register');

            fetch('auth.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showMessage(regMsg, 'Đăng ký thành công!', 'success');
                    setTimeout(() => {
                        document.getElementById('formRegister').reset();
                        showLogin();
                        showMessage(loginMsg, 'Hãy đăng nhập bằng tài khoản mới.', 'success');
                    }, 1500);
                } else {
                    showMessage(regMsg, data.message || 'Lỗi đăng ký.', 'error');
                }
            })
            .catch(() => showMessage(regMsg, 'Lỗi kết nối server.', 'error'));
        });