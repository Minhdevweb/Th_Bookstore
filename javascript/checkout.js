// Xử lý giỏ hàng và thanh toán trong trang checkout
(function(){
  // Các hàm tiện ích
  const $ = (s, r=document) => r.querySelector(s);
  //// Hàm tiện ích chọn nhiều phần tử (trả về mảng)
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));
  // Các phần tử chính
  const cartList = $('#cartList'); // Danh sách sản phẩm trong giỏ hàng
  const totalEl = $('#selectedTotal'); // Tổng tiền của các sản phẩm được chọn
  const form = $('#checkoutForm'); // Form thanh toán

  if (!cartList || !totalEl || !form) return; // Đảm bảo các element tồn tại
// Hàm tính lại tổng tiền
  function recalc() {
    let sum = 0;
    $$('.cart-row', cartList).forEach(row => {
      const checked = $('.select-item', row).checked; // Kiểm tra xem sản phẩm có được chọn không
      const price = parseFloat(row.dataset.price || '0'); // Giá sản phẩm
      const qty = parseInt($('.qty-input', row).value || '1', 10);// Số lượng sản phẩm
      const line = $('.line-total', row);// Phần tử hiển thị thành tiền của dòng sản phẩm
      const lineVal = price * qty; // Tính thành tiền
      line.textContent = '$' + lineVal.toFixed(2);// Cập nhật thành tiền trên giao diện
      if (checked) sum += lineVal;// Cộng vào tổng nếu sản phẩm được chọn
    });
    totalEl.textContent = sum.toFixed(2);// Cập nhật tổng tiền trên giao diện
  }
// Hàm cập nhật số lượng sản phẩm trên server
  function updateServer(productId, quantity) {
    fetch('cart.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}, //"Dữ liệu gửi đi được mã hóa theo định dạng chuẩn của các form HTML thông thường (khi method="POST")"
      body: 'action=update&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity)
    }).then(async r => { // xử lý phản hồi từ server
      const text = await r.text();// Đọc phản hồi dạng text
      try {
        const data = JSON.parse(text); // Chuyển text sang object JSON
        if (data.status === 'error' && data.message) { // Nếu có lỗi từ server
          alert(data.message);
          // Reload để cập nhật lại số lượng từ server
          location.reload();
        }
      } catch(e) {}
    }).catch(()=>{}); // Bắt lỗi (nếu có) nhưng không làm gì
  }
 
  // Lấy tồn kho tối đa của một sản phẩm từ thuộc tính data-stock
  function getMaxStock(row) {
    return parseInt(row.dataset.stock || '0', 10); 
  }
// Xử lý sự kiện trên cartList tăng giảm, xóa
  cartList.addEventListener('click', (e) => {
    const btn = e.target.closest('.qty-btn'); // Nút tăng giảm số lượng
    const remove = e.target.closest('.remove-btn');// Nút xóa sản phẩm
    // Xử lý tăng giảm số lượng
    if (btn) {
      const row = e.target.closest('.cart-row');// Dòng sản phẩm hiện tại
      const input = $('.qty-input', row); // Input số lượng
      const delta = parseInt(btn.dataset.delta, 10); // Giá trị tăng hoặc giảm
      const maxStock = getMaxStock(row);
      let val = parseInt(input.value || '1', 10) + delta;
      if (val < 1) val = 1; // Không cho nhỏ hơn 1
      if (val > maxStock) {
        alert('Số lượng không được vượt quá tồn kho hiện có (' + maxStock + ').');
        val = maxStock;
      }
      input.value = val; //cập nhật
      updateServer(row.dataset.id, val);
      recalc();
    }
    if (remove) {
      const row = e.target.closest('.cart-row');
      const id = row.dataset.id;
      fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=remove&product_id=' + encodeURIComponent(id)
      }).then(()=> {
        row.remove(); // Xóa dòng sản phẩm khỏi giao diện
        recalc();// Tính lại tổng tiền
      });
    }
  });

// Xử lý thay đổi số lượng trực tiếp trong input và checkbox chọn sản phẩm
  cartList.addEventListener('change', (e) => {
    const input = e.target;
    // Xử lý thay đổi số lượng
    if (input.classList.contains('qty-input')) {
      const row = input.closest('.cart-row');
      const maxStock = getMaxStock(row);// Lấy tồn kho tối đa
      let val = parseInt(input.value || '1', 10);// Giá trị hiện tại
      if (val < 1) {
        val = 1;
        input.value = val;
      }
      if (val > maxStock) {
        alert('Số lượng không được vượt quá tồn kho hiện có (' + maxStock + ').');
        val = maxStock;
        input.value = val;
      }
      updateServer(row.dataset.id, val);
      recalc();
    }
    if (input.classList.contains('select-item')) {
      recalc();
    }
  });

  // Ngăn người dùng nhập số lớn hơn max khi đang gõ
  cartList.addEventListener('input', (e) => {
    const input = e.target;
    if (input.classList.contains('qty-input')) {
      const row = input.closest('.cart-row');
      const maxStock = getMaxStock(row);
      let val = parseInt(input.value || '0', 10);
      if (val > maxStock) {
        input.value = maxStock;
      }
    }
  });

  // Xử lý thay đổi phương thức thanh toán
  const paymentMethods = $$('input[name="payment_method"]', form);
  const qrBox = $('#qrBox'); // Hộp thông tin chuyển khoản
  const codInfo = $('#codInfo');// Hộp thông tin thanh toán khi nhận hàng
  
  paymentMethods.forEach(radio => {// Lặp qua các radio button
    radio.addEventListener('change', () => {// Khi có thay đổi
      if (radio.value === 'bank_transfer') {// Nếu chọn chuyển khoản
        qrBox.style.display = 'block';
        codInfo.style.display = 'none';
      } else {
        qrBox.style.display = 'none';
        codInfo.style.display = 'block';
      }
    });
  });
// Xử lý khi submit form
  form.addEventListener('submit', (e) => {
    // Kiểm tra số lượng không vượt quá stock trước khi submit
    let hasError = false;
    $$('.cart-row', cartList).forEach(row => { // Duyệt qua từng dòng sản phẩm
      if ($('.select-item', row).checked) {// Chỉ kiểm tra các sản phẩm được chọn
        const input = $('.qty-input', row);// Input số lượng
        const qty = parseInt(input.value || '0', 10);// Số lượng hiện tại
        const maxStock = getMaxStock(row);// Tồn kho tối đa
        if (qty > maxStock) {
          alert('Số lượng sản phẩm "' + $('strong', row).textContent + '" vượt quá tồn kho (' + maxStock + '). Vui lòng điều chỉnh lại.');// Hiển thị cảnh báo
          input.value = maxStock;// Cập nhật lại số lượng trong input
          hasError = true;// Đánh dấu có lỗi
        }
      }
    });
    
    if (hasError) {
      e.preventDefault();
      recalc();
      return;
    }
    
    // Gắn các sản phẩm được chọn vào form
    // Xóa input cũ
    $$('.selected-hidden', form).forEach(el => el.remove());
    const selectedIds = [];
    $$('.cart-row', cartList).forEach(row => {
      if ($('.select-item', row).checked) selectedIds.push(row.dataset.id);
    });
    if (!selectedIds.length) {
      alert('Vui lòng chọn ít nhất một sản phẩm để đặt hàng.');
      e.preventDefault();
      return;
    }
    selectedIds.forEach(id => {
      const inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'selected[]';
      inp.value = id;
      inp.className = 'selected-hidden';
      form.appendChild(inp);
    });
  });

  recalc();
})();

