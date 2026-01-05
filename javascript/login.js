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
            .then(async res => {
                const text = await res.text();
                const contentType = res.headers.get('content-type') || '';
                
                // Kiểm tra nếu response không phải JSON
                if (!contentType.includes('application/json')) {
                    console.error('Non-JSON response from auth.php:', text.substring(0, 200));
                    showMessage(loginMsg, 'Lỗi server: Nhận được response không phải JSON.', 'error');
                    return null;
                }
                
                // Kiểm tra nếu text bắt đầu bằng HTML tag
                if (text.trim().startsWith('<')) {
                    console.error('HTML response received instead of JSON:', text.substring(0, 200));
                    showMessage(loginMsg, 'Lỗi server: Nhận được HTML thay vì JSON.', 'error');
                    return null;
                }
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text.substring(0, 200));
                    showMessage(loginMsg, 'Lỗi server: Không thể parse JSON response.', 'error');
                    return null;
                }
            })
            .then(data => {
                if (!data) return; // Đã xử lý lỗi ở trên
                if (data.status === 'success') {
                    showMessage(loginMsg, 'Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1000);
                } else {
                    showMessage(loginMsg, data.message || 'Lỗi đăng nhập.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(loginMsg, 'Lỗi kết nối server: ' + error.message, 'error');
            });
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
            .then(async res => {
                const text = await res.text();
                const contentType = res.headers.get('content-type') || '';
                
                // Kiểm tra nếu response không phải JSON
                if (!contentType.includes('application/json')) {
                    console.error('Non-JSON response from auth.php:', text.substring(0, 200));
                    showMessage(regMsg, 'Lỗi server: Nhận được response không phải JSON.', 'error');
                    return null;
                }
                
                // Kiểm tra nếu text bắt đầu bằng HTML tag
                if (text.trim().startsWith('<')) {
                    console.error('HTML response received instead of JSON:', text.substring(0, 200));
                    showMessage(regMsg, 'Lỗi server: Nhận được HTML thay vì JSON.', 'error');
                    return null;
                }
                
                try {
                    return JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text.substring(0, 200));
                    showMessage(regMsg, 'Lỗi server: Không thể parse JSON response.', 'error');
                    return null;
                }
            })
            .then(data => {
                if (!data) return; // Đã xử lý lỗi ở trên
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
            .catch(error => {
                console.error('Error:', error);
                showMessage(regMsg, 'Lỗi kết nối server: ' + error.message, 'error');
            });
        });