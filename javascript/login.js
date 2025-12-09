        // --- LOGIC ĐĂNG NHẬP / ĐĂNG KÝ ---
        const loginBox = document.getElementById('login-box');
        const regBox = document.getElementById('register-box');
        const loginMsg = document.getElementById('loginMsg');
        const regMsg = document.getElementById('regMsg');
        const authContainer = document.getElementById('authContainer');
        const openAuthFormBtn = document.getElementById('openAuthFormBtn');

        // 1. Ẩn form lúc mới vào, chỉ hiện nút
        if (authContainer) {
            authContainer.classList.add('hidden');
        }

        if (openAuthFormBtn && authContainer) {
            openAuthFormBtn.addEventListener('click', () => {
                authContainer.classList.remove('hidden');
                openAuthFormBtn.style.display = 'none';
            });
        }

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