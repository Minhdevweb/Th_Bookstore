
//cung cấp hai hàm tái sử dụng để mở và đóng các modal (cửa sổ popup) trên trang web mà không cần viết lặp lại code cho từng modal riêng lẻ.
function openModal(modalId) { // Mở modal với ID cho trước
  const el = document.getElementById(modalId);// Lấy phần tử modal theo ID
  if (!el) return;// Nếu không tìm thấy thì dừng
  if (el.classList.contains('modal')) {
    el.classList.add('active');
  } else {
    el.style.display = 'block';
  }
  const backdrop = document.getElementById('modalBackdrop');
  if (backdrop) backdrop.style.display = 'block';
}
// Đóng modal với ID cho trước
function closeModal(modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (el.classList.contains('modal')) {
    el.classList.remove('active');
  } else {
    el.style.display = 'none';
  }
  const backdrop = document.getElementById('modalBackdrop');
  if (backdrop) backdrop.style.display = 'none';
}

