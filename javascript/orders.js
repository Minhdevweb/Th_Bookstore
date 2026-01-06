//hiển thị và quản lý đơn hàng  (có phân quyền khách hàng và admin):

// Hiển thị đơn hàng của khách hàng
function showOrders() {
  fetch('orders.php')
    .then(res => res.json())
    .then(orders => {
      const ordersList = document.getElementById('ordersList');
      if (!ordersList) return;
      ordersList.innerHTML = orders.map(order => `
        <div class="order-item">
          <div>
            <h3>${order.title}</h3>
            <p>Quantity: ${order.quantity}</p>
            <p>Total: $${order.total}</p>
            <p>Status: ${order.status}</p>
            <p>Date: ${new Date(order.created_at).toLocaleDateString()}</p>
          </div>
        </div>
      `).join(''); // Ghép tất cả đơn hàng lại thành chuỗi
    });
}
// hiển thị đơn hàng của admin
function showAdminOrders() {
  fetch('orders.php')
    .then(res => res.json())
    .then(orders => {
      const adminOrdersList = document.getElementById('adminOrdersList');
      if (!adminOrdersList) return;
      adminOrdersList.innerHTML = orders.map(order => `
        <div class="order-item">
          <div>
            <h3>${order.title}</h3>
            <p>Customer: ${order.email}</p>
            <p>Quantity: ${order.quantity}</p>
            <p>Total: $${order.total}</p>
            <p>Date: ${new Date(order.created_at).toLocaleDateString()}</p>
            <select onchange="updateOrderStatus(${order.id}, this.value)">
              ${['pending','confirmed','shipping','delivered','cancelled']
                .map(status => `<option value="${status}" ${status === order.status ? 'selected' : ''}>${status.charAt(0).toUpperCase()+status.slice(1)}</option>`).join('')}
            </select>
          </div>
        </div>
      `).join('');
    });
}
// Cập nhật trạng thái đơn hàng (dành cho admin)
function updateOrderStatus(orderId, status) {
  fetch('update_order_status.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `order_id=${orderId}&status=${status}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      showAdminOrders();
    }
  });
}

// Gán sự kiện cho nút xem đơn hàng
(function () {
  const ordersBtn = document.getElementById('ordersBtn');
  if (ordersBtn) {
    ordersBtn.addEventListener('click', () => {
      showOrders();
      openModal('ordersModal');
    });
  }

  const adminOrdersBtn = document.getElementById('adminOrdersBtn');
  try {
    if (adminOrdersBtn && typeof isAdmin !== 'undefined' && isAdmin) {
      adminOrdersBtn.addEventListener('click', () => {
        showAdminOrders();
        openModal('adminOrdersModal');
      });
    }
  } catch (e) { /* ignore if isAdmin not defined */ }
})();

