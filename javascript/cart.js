// Cart related functions extracted from Home/index.php
function showCart() {
  fetch('cart.php')
    .then(res => res.json())
    .then(resp => {
      const items = Array.isArray(resp) ? resp : (resp.items || []);
      const cartItems = document.getElementById('cartItems');
      const cartTotal = document.getElementById('cartTotal');
      let total = 0;

      if (!cartItems || !cartTotal) return;

      cartItems.innerHTML = items.map(item => {
        let imgSrc = item.image || '';
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('../uploads/')) {
          imgSrc = '../uploads/' + imgSrc;
        }
        total += item.total || (item.price * item.quantity);
        return `
          <div class="cart-item">
            <img src="${imgSrc}" alt="${item.title}" onerror="this.src='https://via.placeholder.com/50x50?text=No+Img'">
            <div>
              <h3>${item.title}</h3>
              <p>$${item.price}</p>
              <input type="number" min="1" value="${item.quantity}" onchange="updateCart(${item.id}, this.value)" class="quantity-input">
              <button onclick="removeFromCart(${item.id})">Remove</button>
            </div>
          </div>
        `;
      }).join('');

      cartTotal.textContent = total.toFixed(2);
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

