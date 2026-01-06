// javascript/comments.js
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('commentForm');
    const nameInput = document.getElementById('commentName');
    const contentInput = document.getElementById('commentContent');
    const commentsContainer = document.getElementById('commentsList');
    const countSpan = document.getElementById('commentCount');
    const productId = document.body.dataset.productId;

    // Load bình luận khi mở trang
    loadComments();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const name = nameInput.value.trim();
        const content = contentInput.value.trim();

        if (!name || !content) return alert('Vui lòng điền đầy đủ!');

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('name', name);
        formData.append('content', content);

        try {
            // Đường dẫn API 
            const res = await fetch('add_comment.php', {
                method: 'POST',
                body: formData
            });
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const text = await res.text();
            const contentType = res.headers.get('content-type') || '';
            
            // Kiểm tra nếu response không phải JSON
            if (!contentType.includes('application/json')) {
                console.error('Non-JSON response from add_comment.php:', text.substring(0, 200));
                alert('Lỗi server: Nhận được response không phải JSON.');
                return;
            }
            
            // Kiểm tra nếu text bắt đầu bằng HTML tag
            if (text.trim().startsWith('<')) {
                console.error('HTML response received instead of JSON:', text.substring(0, 200));
                alert('Lỗi server: Nhận được HTML thay vì JSON.');
                return;
            }
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text.substring(0, 200));
                alert('Lỗi server: Không thể parse JSON response.');
                return;
            }

            if (data.status === 'success') {
                // Thêm bình luận mới lên đầu danh sách
                const newComment = data.comment;
                const div = document.createElement('div');
                div.className = 'comment';
                div.innerHTML = `
                    <div class="comment-meta">
                        <strong>${newComment.name}</strong>
                        <span>${newComment.created_at}</span>
                    </div>
                    <p>${newComment.content}</p>
                `;
                commentsContainer.prepend(div);

                // Cập nhật số lượng
                const currentCount = parseInt(countSpan.textContent.match(/\d+/) || 0) + 1;
                countSpan.textContent = `Bình luận (${currentCount})`;

                // Reset form
                form.reset();
                alert('Cảm ơn bạn đã bình luận! ❤️');
            } else {
                alert('Lỗi: ' + data.message);
            }
        } catch (err) {
            console.error('Error:', err);
            alert('Lỗi mạng, vui lòng thử lại! Chi tiết: ' + err.message);
        }
    });

    async function loadComments() {
        try {
            // Đường dẫn API - các file PHP cùng thư mục với blog_detail.php (Home/)
            const res = await fetch(`get_comment.php?product_id=${productId}`);
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const text = await res.text();
            const contentType = res.headers.get('content-type') || '';
            
            // Kiểm tra nếu response không phải JSON
            if (!contentType.includes('application/json')) {
                console.error('Non-JSON response from get_comment.php:', text.substring(0, 200));
                commentsContainer.innerHTML = '<p>Lỗi khi tải bình luận. Vui lòng thử lại sau.</p>';
                return;
            }
            
            // Kiểm tra nếu text bắt đầu bằng HTML tag
            if (text.trim().startsWith('<')) {
                console.error('HTML response received instead of JSON:', text.substring(0, 200));
                commentsContainer.innerHTML = '<p>Lỗi khi tải bình luận. Vui lòng thử lại sau.</p>';
                return;
            }
            
            let comments;
            try {
                comments = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text.substring(0, 200));
                commentsContainer.innerHTML = '<p>Lỗi khi tải bình luận. Vui lòng thử lại sau.</p>';
                return;
            }
            if (comments.length === 0) {
                commentsContainer.innerHTML = '<p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>';
                countSpan.textContent = 'Bình luận (0)';
                return;
            }
            countSpan.textContent = `Bình luận (${comments.length})`;
            commentsContainer.innerHTML = comments.map(c => `
                <div class="comment">
                    <div class="comment-meta">
                        <strong>${c.name}</strong>
                        <span>${c.created_at}</span>
                    </div>
                    <p>${c.content}</p>
                </div>
            `).join('');
        } catch (err) {
            console.error('Error loading comments:', err);
            commentsContainer.innerHTML = '<p>Không tải được bình luận. Vui lòng thử lại sau.</p>';
        }
    }
});