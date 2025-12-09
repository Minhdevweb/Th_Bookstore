// cập nhật trạng thái đơn hàng

// đợi trang load xong mới chạy code
document.addEventListener('DOMContentLoaded', function(){
    // tìm tất cả các nút " cập nhật"
    const updateButtons = document.querySelectorAll('.btn-update-status');

    // duyệt qua từng nút và thêm sự kiện click
    updateButtons.forEach(button => {
        button.addEventListener('click', function(){
            //lấy mã đơn hàng từ thuộc tính data-order-id
            const orderId = this.getAttribute('data-order-id');
            //Tìm select box tương ứng với đơn hàng này
            const statusSelect = document.querySelector(`.status-select[data-order-id="${orderId}"]`);
            //lấy giá trị trạng thái mới
            const newStatus = statusSelect ? statusSelect.value : '';
            //gọi hàm cập nhật
            updateOrderStatus(orderId, newStatus);
        });
    });
});

// hàm cập nhật trạng thái đơn hàng
function updateOrderStatus(orderId,newStatus){
    // Hiển thị thông báo đang xử lý 
    if (confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng này?')) {
        // tạo đối tượng FormData để gửi dữ liệu
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', newStatus);

        // gửi requesr đến server
        fetch('update_order_status.php',{
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) // chuyển đổi reponds thành json
        .then(data =>{
            //kiểm trâ kết quả\
            if (data.status === 'success'){
                alert('Cập nhật trạng thái đơn hàng thành công!')
                // reload trang để hiển thị trạng thái mới
                location.reload();
            } else {
                alert('Lỗi:' + (data.message || 'Không thể cập nhật trạng thái'));
            }
        })
        .catch(error => {
            //xử lý lôi nếu có
            console.error('Lỗi:' , error);
            alert('Đã xảy ra lỗi khi cập nhật. Vui lòng thử lại :((')
        });
    }
}
