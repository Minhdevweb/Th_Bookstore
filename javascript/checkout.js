// Xử lý giỏ hàng và thanh toán trong trang checkout
(function(){
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));
  const cartList = $('#cartList');
  const totalEl = $('#selectedTotal');
  const form = $('#checkoutForm');

  if (!cartList || !totalEl || !form) return; // Đảm bảo các element tồn tại

  function recalc() {
    let sum = 0;
    $$('.cart-row', cartList).forEach(row => {
      const checked = $('.select-item', row).checked;
      const price = parseFloat(row.dataset.price || '0');
      const qty = parseInt($('.qty-input', row).value || '1', 10);
      const line = $('.line-total', row);
      const lineVal = price * qty;
      line.textContent = '$' + lineVal.toFixed(2);
      if (checked) sum += lineVal;
    });
    totalEl.textContent = sum.toFixed(2);
  }

  function updateServer(productId, quantity) {
    fetch('cart.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=update&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity)
    }).then(async r => {
      const text = await r.text();
      try {
        const data = JSON.parse(text);
        if (data.status === 'error' && data.message) {
          alert(data.message);
          // Reload để cập nhật lại số lượng từ server
          location.reload();
        }
      } catch(e) {}
    }).catch(()=>{});
  }

  function getMaxStock(row) {
    return parseInt(row.dataset.stock || '0', 10);
  }

  cartList.addEventListener('click', (e) => {
    const btn = e.target.closest('.qty-btn');
    const remove = e.target.closest('.remove-btn');
    if (btn) {
      const row = e.target.closest('.cart-row');
      const input = $('.qty-input', row);
      const delta = parseInt(btn.dataset.delta, 10);
      const maxStock = getMaxStock(row);
      let val = parseInt(input.value || '1', 10) + delta;
      if (val < 1) val = 1;
      if (val > maxStock) {
        alert('Số lượng không được vượt quá tồn kho hiện có (' + maxStock + ').');
        val = maxStock;
      }
      input.value = val;
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
        row.remove();
        recalc();
      });
    }
  });

  cartList.addEventListener('change', (e) => {
    const input = e.target;
    if (input.classList.contains('qty-input')) {
      const row = input.closest('.cart-row');
      const maxStock = getMaxStock(row);
      let val = parseInt(input.value || '1', 10);
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
  const qrBox = $('#qrBox');
  const codInfo = $('#codInfo');
  
  paymentMethods.forEach(radio => {
    radio.addEventListener('change', () => {
      if (radio.value === 'bank_transfer') {
        qrBox.style.display = 'block';
        codInfo.style.display = 'none';
      } else {
        qrBox.style.display = 'none';
        codInfo.style.display = 'block';
      }
    });
  });

  form.addEventListener('submit', (e) => {
    // Kiểm tra số lượng không vượt quá stock trước khi submit
    let hasError = false;
    $$('.cart-row', cartList).forEach(row => {
      if ($('.select-item', row).checked) {
        const input = $('.qty-input', row);
        const qty = parseInt(input.value || '0', 10);
        const maxStock = getMaxStock(row);
        if (qty > maxStock) {
          alert('Số lượng sản phẩm "' + $('strong', row).textContent + '" vượt quá tồn kho (' + maxStock + '). Vui lòng điều chỉnh lại.');
          input.value = maxStock;
          hasError = true;
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

