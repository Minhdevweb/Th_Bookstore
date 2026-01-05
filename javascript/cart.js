// Cart related functions extracted from Home/index.php
//Hiển thị nội dung giỏ hàng (mini-cart hoặc modal giỏ hàng)
//Gọi API cart.php (phương thức GET) để lấy danh sách sản phẩm trong giỏ
 // Sau đó render ra giao diện và tính tổng tiền
function showCart() {
  fetch('cart.php')
    .then(async res => {
      const text = await res.text();
      const contentType = res.headers.get('content-type') || '';
      
      if (!contentType.includes('application/json')) {
        console.error('Non-JSON response from cart.php:', text.substring(0, 200));
        return null;
      }
      
      if (text.trim().startsWith('<')) {
        console.error('HTML response received instead of JSON:', text.substring(0, 200));
        return null;
      }
      
      try {
        return JSON.parse(text);
      } catch (e) {
        console.error('JSON parse error:', e);
        return null;
      }
    })
    .then(resp => {
      if (!resp) {
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        if (cartItems) cartItems.innerHTML = '<p class="empty-cart">Lỗi khi tải giỏ hàng</p>';
        if (cartTotal) cartTotal.textContent = '0.00';
        return;
      }
      
      // Xử lý dữ liệu trả về: có thể là mảng cũ hoặc object mới {items: [...]}
      const items = Array.isArray(resp) ? resp : (resp.items || []);
      const cartItems = document.getElementById('cartItems'); // phần hiển thị danh sách sản phẩm trong giỏ
      const cartTotal = document.getElementById('cartTotal');// phần hiển thị tổng tiền
      let total = 0;

      if (!cartItems || !cartTotal) return;// Nếu không tìm thấy phần tử HTML thì dừng lại (tránh lỗi)
// Render từng sản phẩm trong giỏ hàng
      if (items.length === 0) {
        cartItems.innerHTML = '<p class="empty-cart">Giỏ hàng của bạn đang trống</p>';
        cartTotal.textContent = '0.00';
        return;
      }
      
      cartItems.innerHTML = items.map(item => {
        let imgSrc = item.image || '';
        // Kiểm tra và điều chỉnh đường dẫn hình ảnh nếu cần
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('../uploads/')) {
          imgSrc = '../uploads/' + imgSrc; // THÊM ĐƯƠNG DÂN UPLOADS NẾU LÀ ẢNH LOCAL
        }
        // Tính thành tiền cho từng sản phẩm (nếu có total thì dùng, không thì tính lại)
        const itemTotal = item.total || (item.price * item.quantity);
        total += itemTotal;
        // Trả về HTML cho một sản phẩm trong giỏ
        return `
          <div class="cart-item" data-product-id="${item.id}">
            <img src="${imgSrc}" alt="${item.title}" onerror="this.src='https://via.placeholder.com/60x60?text=No+Img'">
            <div class="cart-item-info">
              <h4>${item.title}</h4>
              <p class="cart-item-author">${item.author || ''}</p>
              <div class="cart-item-details">
                <span class="cart-item-price">$${parseFloat(item.price).toFixed(2)}</span>
                <span class="cart-item-qty">x${item.quantity}</span>
                <span class="cart-item-total">$${itemTotal.toFixed(2)}</span>
              </div>
              <div class="cart-item-actions">
                <input type="number" min="1" value="${item.quantity}" onchange="updateCart(${item.id}, this.value)" class="quantity-input">
                <button onclick="removeFromCart(${item.id})" class="btn-remove">✖ Xóa</button>
              </div>
            </div>
          </div>
        `;
      }).join('');// Ghép tất cả HTML lại thành chuỗi

      cartTotal.textContent = total.toFixed(2); // CẬP NHẬT TỔNG TIỀN
    });
}

function updateCart(productId, quantity) {
  fetch('cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update&product_id=${productId}&quantity=${quantity}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      showCart();
    }
  });
}

function removeFromCart(productId) {
  fetch('cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=remove&product_id=${productId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      showCart();
    }
  });
}

function checkout() {
  fetch('cart.php')
    .then(res => res.json())
    .then(resp => {
      const items = Array.isArray(resp) ? resp : (resp.items || []);
      const promises = items.map(item => fetch('orders.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${item.id}&quantity=${item.quantity}`
      }));

      Promise.all(promises)
        .then(() => fetch('cart.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'action=clear'
        }))
        .then(() => {
          alert('Order placed successfully!');
          closeModal('cartModal');
        });
    });
}

// Event: checkout button
(function () {
  const btn = document.getElementById('checkout');
  if (btn) btn.addEventListener('click', function(e){
    e.preventDefault();
    window.location.href = 'checkout.php';
  });
})();

