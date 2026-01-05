const qs = (selector) => document.querySelector(selector);
const qsAll = (selector) => document.querySelectorAll(selector);

let products = [];
let cart = [];

// NOTE: removed debug probe to add_products.php because it returns 403 for non-admins
// and produced noise in the console. Real requests will be done when the admin
// submits the Add Product form.

// --- Lấy dữ liệu sản phẩm ---
let currentPage = 1;
let totalPages = 1;
let selectedMood = "";

async function loadProducts(page = 1) {
  try {
    const response = await fetch(`get_products.php?page=${page}`);
    const data = await response.json();
    if (data.status !== 'success') return;

    products = data.products;
    currentPage = data.currentPage;
    totalPages = data.totalPages;
    renderProducts(products);
    renderPagination();
  } catch (error) {
    console.error('Error loading products:', error);
  }
}
// gọi khi vào trang
loadProducts();
// Merge giỏ hàng lưu tạm (localStorage) vào giỏ hàng server nếu đã đăng nhập
(async function mergeGuestCartIntoServer() {
  try {
    const raw = localStorage.getItem('guest_cart') || '{}';
    const guest = JSON.parse(raw);
    const ids = Object.keys(guest);
    if (!ids.length) return;
    for (const id of ids) {
      const qty = parseInt(guest[id], 10) || 1;
      const r = await fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${encodeURIComponent(id)}&quantity=${encodeURIComponent(qty)}`
      });
      const res = await r.json();
      if (res && res.status === 'success') {
        delete guest[id];
      }
    }
    // Cập nhật lại/clear guest cart nếu đã merge hết
    const remaining = Object.keys(guest).length;
    if (remaining) {
      localStorage.setItem('guest_cart', JSON.stringify(guest));
    } else {
      localStorage.removeItem('guest_cart');
    }
    // cập nhật hiển thị số lượng (nếu cần)
    const el = qs('#cartCount');
    if (el && typeof fetch === 'function') {
      try {
        const r = await fetch('cart.php');
        const data = await r.json();
        const count = Array.isArray(data) ? data.length : (data.totalItems || (data.items ? data.items.length : 0));
        el.textContent = count;
      } catch {}
    }
  } catch (_) {}
})();

// Tự động thêm sản phẩm vào giỏ hàng nếu có tham số add_to_cart trong URL
(async function autoAddToCart() {
  try {
    const params = new URLSearchParams(window.location.search);
    const productId = params.get('add_to_cart');
    
    if (productId) {
      // Xóa tham số khỏi URL để tránh thêm lại khi refresh
      window.history.replaceState({}, document.title, window.location.pathname);
      
      // Thêm vào giỏ hàng
      const response = await fetch('cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}&quantity=1`
      });
      const res = await response.json();
      
      if (res.status === 'success') {
        // Cập nhật số lượng giỏ hàng
        const count = typeof res.totalItems !== 'undefined'
          ? res.totalItems
          : (res.cart ? Object.values(res.cart).reduce((sum, qty) => sum + qty, 0) : 0);
        const el = qs('#cartCount');
        if (el) el.textContent = count;
        
        // Hiển thị thông báo
        alert('Đã thêm sản phẩm vào giỏ hàng! 🛒');
        
        // Không tự động mở giỏ hàng
      } else {
        // Nếu lỗi do chưa đăng nhập, mở modal đăng nhập
        if (res.message && res.message.includes('login')) {
          alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!');
          showModal('loginModal');
        } else {
          alert(res.message || 'Không thể thêm sản phẩm vào giỏ hàng');
        }
      }
    }
  } catch (error) {
    console.error('Error auto-adding to cart:', error);
    alert('Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng');
  }
})();

// Lấy số lượng giỏ hàng ban đầu từ server (nếu có)
(async function initCartCount() {
  try {
    const r = await fetch('cart.php');
    const data = await r.json();
    const count = Array.isArray(data) ? data.length : (data.totalItems || (data.items ? data.items.length : 0));
    const el = qs('#cartCount');
    if (el) el.textContent = count;
  } catch {
    // ignore
  }
})();

// If redirected with login=1 (after logout), open login modal automatically
(function openLoginIfRequested() {
  try {
    const params = new URLSearchParams(window.location.search);
    if (params.get('login') === '1') {
      showModal('loginModal');
      // clean the query so refresh won't keep reopening
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  } catch (_) {}
})();

function renderPagination() {
  const pagDiv = document.getElementById('pagination');
  // build a compact page list (e.g. 1 2 3 ... 17) with neighbors around current
  function buildPages(cur, total) {
    const pages = [];
    if (total <= 7) {
      for (let i = 1; i <= total; i++) pages.push(i);
      return pages;
    }

    pages.push(1);
    const left = Math.max(2, cur - 1);
    const right = Math.min(total - 1, cur + 1);

    if (left > 2) pages.push('...');

    for (let i = left; i <= right; i++) pages.push(i);

    if (right < total - 1) pages.push('...');

    pages.push(total);
    return pages;
  }

  const pages = buildPages(currentPage, totalPages);

  pagDiv.innerHTML = `
    <nav class="pagination-wrap">
      <button class="page-arrow" id="prevPage" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous">‹</button>
      <ul class="page-list">
        ${pages.map(p => {
          if (p === '...') return `<li class="page-ellipsis">${p}</li>`;
          return `<li class="page-item ${p === currentPage ? 'active' : ''}"><button data-page="${p}" class="page-btn">${p}</button></li>`;
        }).join('')}
      </ul>
      <button class="page-arrow" id="nextPage" ${currentPage >= totalPages ? 'disabled' : ''} aria-label="Next">›</button>
    </nav>
  `;

  // attach handlers
  const prev = document.getElementById('prevPage');
  const next = document.getElementById('nextPage');
  if (prev) prev.onclick = () => { if (currentPage > 1) loadProducts(currentPage - 1); };
  if (next) next.onclick = () => { if (currentPage < totalPages) loadProducts(currentPage + 1); };

  qsAll('.page-btn').forEach(b => {
    b.addEventListener('click', (e) => {
      const p = Number(e.currentTarget.getAttribute('data-page'));
      if (!isNaN(p) && p !== currentPage) loadProducts(p);
    });
  });
}

// Small view helpers
function formatImageSrc(src) {
  if (!src) return '';
  if (!src.startsWith('http') && !src.startsWith('../uploads/')) return '../uploads/' + src;
  return src;
}

function isOutOfStock(p) {
  return typeof p.stock !== 'undefined' && Number(p.stock) === 0;
}

function productCardHtml(p, adminButtons) {
  const imgSrc = formatImageSrc(p.image);
  const out = isOutOfStock(p);
  const stockLine = typeof p.stock !== 'undefined' ? `<p class="muted">Stock: ${p.stock}</p>` : '';
  const soldOut = out ? '<p class="muted" style="color:#c00">Hết hàng</p>' : '';
  const buyBtn = `<button onclick="addToCart(${p.id}, event)" class="btn-small" ${out ? 'disabled' : ''}>${out ? 'Hết hàng' : 'Thêm vào giỏ'}</button>`;
  return `
    <div class="card">
      <img src="${imgSrc}" alt="${p.title}" onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
      <div class="card-body">
        <h4>${p.title}</h4>
        <p>${p.author}</p>
        <div class="rating">${'★'.repeat(Math.round(p.rating))}</div>
        ${stockLine}
        ${soldOut}
      </div>
      <div class="card-footer">
        <span class="price">$${p.price}</span>
        <div class="flex-row-gap">
          ${buyBtn}
          ${adminButtons}
        </div>
      </div>
    </div>
  `;
}

// --- Hiển thị danh sách sản phẩm ---
function renderProducts(list) {
  const grid = qs('#productGrid');
  grid.innerHTML = "";

  list.forEach(p => {
    // Chỉ hiện nút Edit và Delete cho admin
    const adminButtons = isAdmin ? `
      <button onclick="editProduct(${p.id})" class="btn-small btn-edit">Sửa</button>
      <button onclick="deleteProduct(${p.id})" class="btn-small btn-delete">Xóa</button>
    ` : '';
    grid.innerHTML += productCardHtml(p, adminButtons);
  });
}



// --- Giỏ hàng ---
// Hàm tạo animation sản phẩm di chuyển vào giỏ hàng
function animateProductToCart(productElement, cartIcon) {
  return new Promise((resolve) => {
    // Tạo clone của ảnh sản phẩm (nhẹ hơn clone toàn bộ card)
    const productImg = productElement.querySelector('img');
    if (!productImg) {
      resolve();
      return;
    }
    
    const clone = productImg.cloneNode(true);
    clone.style.position = 'fixed';
    clone.style.zIndex = '10000';
    clone.style.pointerEvents = 'none';
    clone.style.borderRadius = '8px';
    clone.style.boxShadow = '0 4px 12px rgba(0,0,0,0.3)';
    clone.style.transition = 'all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
    
    // Lấy vị trí của sản phẩm và icon giỏ hàng
    const productRect = productImg.getBoundingClientRect();
    const cartRect = cartIcon.getBoundingClientRect();
    
    // Đặt vị trí ban đầu
    clone.style.left = productRect.left + 'px';
    clone.style.top = productRect.top + 'px';
    clone.style.width = productRect.width + 'px';
    clone.style.height = productRect.height + 'px';
    clone.style.opacity = '0.9';
    clone.style.transform = 'scale(1)';
    
    document.body.appendChild(clone);
    
    // Trigger reflow
    clone.offsetHeight;
    
    // Di chuyển đến icon giỏ hàng
    clone.style.left = cartRect.left + (cartRect.width / 2) - 15 + 'px';
    clone.style.top = cartRect.top + (cartRect.height / 2) - 15 + 'px';
    clone.style.width = '30px';
    clone.style.height = '30px';
    clone.style.opacity = '0';
    clone.style.transform = 'scale(0.1) rotate(360deg)';
    
    // Xóa clone sau khi animation xong
    setTimeout(() => {
      if (clone.parentNode) {
        document.body.removeChild(clone);
      }
      resolve();
    }, 600);
  });
}

async function addToCart(productId, event) {
  try {
    // Tìm element sản phẩm và nút thêm vào giỏ
    let productCard = null;
    if (event && event.target) {
      productCard = event.target.closest('.card');
    }
    const cartIcon = qs('#cartBtn') || qs('.cart') || qs('nav a.cart');
    
    // Tạo animation nếu có element
    if (productCard && cartIcon) {
      await animateProductToCart(productCard, cartIcon);
    }
    
    const response = await fetch('cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add&product_id=${productId}&quantity=1`
    });
    
    const text = await response.text();
    const contentType = response.headers.get('content-type') || '';
    
    // Kiểm tra nếu response không phải JSON
    if (!contentType.includes('application/json')) {
      console.error('Non-JSON response from cart.php:', text.substring(0, 200));
      alert('Lỗi server: Nhận được response không phải JSON. Vui lòng thử lại.');
      return;
    }
    
    // Kiểm tra nếu text bắt đầu bằng HTML tag
    if (text.trim().startsWith('<')) {
      console.error('HTML response received instead of JSON:', text.substring(0, 200));
      alert('Lỗi server: Nhận được HTML thay vì JSON. Vui lòng thử lại.');
      return;
    }
    
    let res;
    try {
      res = JSON.parse(text);
    } catch (parseError) {
      console.error('JSON parse error:', parseError);
      console.error('Response text:', text.substring(0, 200));
      alert('Lỗi server: Không thể parse JSON response. Vui lòng thử lại.');
      return;
    }
    
    if (res.status !== 'success') {
      // Nếu chưa đăng nhập, lưu tạm vào localStorage để gộp sau khi đăng nhập
      if ((res.message || '').toLowerCase().includes('login')) {
        try {
          const raw = localStorage.getItem('guest_cart') || '{}';
          const guest = JSON.parse(raw);
          guest[productId] = (parseInt(guest[productId] || '0', 10) || 0) + 1;
          localStorage.setItem('guest_cart', JSON.stringify(guest));
          alert('Đã lưu tạm sản phẩm vào giỏ. Vui lòng đăng nhập để hoàn tất.');
          return;
        } catch (_) {
          // fallback
        }
      }
      return alert(res.message || 'Thêm vào giỏ không thành công');
    }

    const count = typeof res.totalItems !== 'undefined'
      ? res.totalItems
      : (res.cart ? Object.values(res.cart).reduce((sum, qty) => sum + qty, 0) : 0);
    const el = qs('#cartCount');
    if (el) el.textContent = count;
    
    // Cập nhật và hiển thị modal giỏ hàng
    if (typeof showCart === 'function') {
      showCart();
    } else if (typeof updateCartUI === 'function') {
      updateCartUI();
    }
    
    // Mở modal giỏ hàng
    showModal('cartModal');
  } catch (error) {
    console.error('Error adding to cart:', error);
    alert('Lỗi mạng khi thêm vào giỏ hàng: ' + error.message);
  }
}

function updateCartUI() {
  const cartContainer = qs('#cartItems');
  const totalElement = qs('#cartTotal');

  if (!cartContainer) return;

  if (cart.length === 0) {
    cartContainer.innerHTML = '<p class="empty-note">Cart is empty.</p>';
    totalElement.textContent = '0.00';
    return;
  }

  let total = 0;
  cartContainer.innerHTML = cart.map(p => {
    total += p.price * p.quantity;
    return `
      <div class="cart-item">
        <img src="${p.image}" alt="${p.title}">
        <div class="grow">
          <h5 class="no-margin">${p.title}</h5>
          <p class="no-margin muted">$${p.price} × ${p.quantity}</p>
        </div>
        <button class="remove btn-link-danger" onclick="removeFromCart(${p.id})">✖</button>
      </div>
    `;
  }).join('');

  totalElement.textContent = total.toFixed(2);
}

async function removeFromCart(productId) {
  try {
    const response = await fetch('cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=remove&product_id=${productId}`
    });
    const res = await response.json();
    if (res.status !== 'success') return alert(res.message || 'Remove failed');

    const count = typeof res.totalItems !== 'undefined' ? res.totalItems : 0;
    const el = qs('#cartCount');
    if (el) el.textContent = count;
    if (typeof showCart === 'function') showCart();
  } catch (error) {
    console.error('Error removing from cart:', error);
    alert('Network error');
  }
}
// Helper to open modal using page functions when available
function showModal(id) {
  if (typeof openModal === 'function') {
    openModal(id);
  } else {
    const m = qs('#' + id); if (m) m.style.display = 'block';
    const backdrop = qs('#modalBackdrop'); if (backdrop) backdrop.style.display = 'block';
  }
}

// Helper to close modal using page functions when available
function hideModal(id) {
  if (typeof closeModal === 'function') {
    closeModal(id);
  } else {
    const m = qs('#' + id); if (m) m.style.display = 'none';
    const backdrop = qs('#modalBackdrop'); if (backdrop) backdrop.style.display = 'none';
  }
}

// Open Register modal
if (qs('#registerBtn')) {
  qs('#registerBtn').onclick = () => showModal('regModal');
}

// Open Login modal
if (qs('#loginBtn')) {
  qs('#loginBtn').onclick = () => showModal('loginModal');
}

// Add Product button
if (qs('#addProductBtn')) {
  qs('#addProductBtn').onclick = () => {
    showModal('addProductModal');
    const header = qs('#addProductModal h3');
    if (header) header.textContent = 'Thêm Sản Phẩm';
    const btn = qs('#submitProduct');
    if (btn) btn.textContent = 'Thêm Sản Phẩm';
  };
}

// Open add product modal if requested by query (?open=addProduct)
if (typeof openModalFromQuery !== 'undefined' && openModalFromQuery === 'addProduct') {
  showModal('addProductModal');
}

// Cart button navigates to integrated cart/checkout page
if (qs('#cartBtn')) {
  qs('#cartBtn').onclick = () => {
    window.location.href = 'checkout.php';
  };
}

// Continue shopping button in cart modal
if (qs('#continueShopping')) {
  qs('#continueShopping').onclick = () => {
    hideModal('cartModal');
  };
}

// Close buttons (elements with .close and data-close attribute)
qsAll('.close').forEach((btn) => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-close');
    if (target) return hideModal(target);
    ['loginModal','regModal','cartModal','addProductModal','ordersModal','adminOrdersModal'].forEach(hideModal);
  });
});

// Auth logic is handled in auth.js

// --- Dark mode ---
const toggle = qs('#themeToggle');
toggle.onclick = () => {
  document.body.classList.toggle('dark');
  localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
};
if (localStorage.getItem('theme') === 'dark') {
  document.body.classList.add('dark');
}

function applyFilters() {
  const categorySelect = qs('#category');
  const priceSelect = qs('#price');
  const ratingSelect = qs('#rating');

  const category = selectedMood || (categorySelect ? categorySelect.value.trim().toLowerCase() : '');
  const price = priceSelect ? priceSelect.value.trim() : '';
  const rating = ratingSelect ? ratingSelect.value.trim() : '';

  let filtered = products.slice();

  if (category !== "") {
    filtered = filtered.filter(p => (p.category || '').toLowerCase() === category);
  }

  if (price !== "") {
    filtered = filtered.filter(p => {
      const pr = parseFloat(p.price);
      if (price === "Under $10") return pr < 10;
      if (price === "$10-20") return pr >= 10 && pr <= 20;
      if (price === "$20-50") return pr > 20 && pr <= 50;
      if (price === "Over $50") return pr > 50;
      return true;
    });
  }

  if (rating !== "") {
    filtered = filtered.filter(p => parseFloat(p.rating) >= parseFloat(rating));
  }

  renderProducts(filtered);

  if (filtered.length === 0) {
    qs('#productGrid').innerHTML = '<p class="empty-note">No products found.</p>';
  }
}

const applyBtn = qs('#apply');
if (applyBtn) {
  applyBtn.onclick = applyFilters;
}

// Reset filters / "Quay lại"
const resetBtn = qs('#resetFilters');
if (resetBtn) {
  resetBtn.addEventListener('click', () => {
    // clear selects
    const cat = qs('#category'); if (cat) cat.value = '';
    const price = qs('#price'); if (price) price.value = '';
    const rating = qs('#rating'); if (rating) rating.value = '';

    // clear mood chips
    selectedMood = '';
    qsAll('.filters .mood-chip').forEach(c => c.classList.remove('active'));

    // clear search
    const s = qs('#search'); if (s) s.value = '';

    // reload default product list (first page)
    if (typeof loadProducts === 'function') loadProducts(1);
    else renderProducts(products);
  });
}

const moodChips = qsAll('.filters .mood-chip');
if (moodChips.length) {
  moodChips.forEach(chip => {
    chip.addEventListener('click', () => {
      const value = (chip.dataset.value || '').toLowerCase();
      if (selectedMood === value) {
        selectedMood = '';
        chip.classList.remove('active');
      } else {
        selectedMood = value;
        moodChips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        const categorySelect = qs('#category');
        if (categorySelect) categorySelect.value = '';
      }
      applyFilters();
    });
  });
}

const categorySelectEl = qs('#category');
if (categorySelectEl) {
  categorySelectEl.addEventListener('change', () => {
    if (selectedMood) {
      selectedMood = '';
      moodChips.forEach(c => c.classList.remove('active'));
    }
  });
}

const adminMoodChips = qsAll('.admin-mood-chip');
const categoryInput = qs('#prodCategory');

function highlightAdminMoodChip(value) {
  const normalized = (value || '').toLowerCase();
  let matched = false;
  adminMoodChips.forEach(chip => {
    const chipValue = (chip.dataset.value || '').toLowerCase();
    if (normalized && chipValue === normalized) {
      chip.classList.add('active');
      matched = true;
    } else {
      chip.classList.remove('active');
    }
  });
  if (!matched) {
    adminMoodChips.forEach(chip => chip.classList.remove('active'));
  }
}

if (adminMoodChips.length && categoryInput) {
  adminMoodChips.forEach(chip => {
    chip.addEventListener('click', () => {
      const value = chip.dataset.value || '';
      categoryInput.value = value;
      highlightAdminMoodChip(value);
    });
  });

  categoryInput.addEventListener('input', () => {
    highlightAdminMoodChip(categoryInput.value);
  });
}

// --- Tìm kiếm sản phẩm theo tên ---
qs('#search').addEventListener('input', () => {
  const keyword = qs('#search').value.trim().toLowerCase();

  if (keyword === "") {
    renderProducts(products);
    return;
  }

  const filtered = products.filter(p =>
    p.title.toLowerCase().includes(keyword)
  );

  renderProducts(filtered);

  if (filtered.length === 0) {
    qs('#productGrid').innerHTML = '<p class="empty-note">No products found.</p>';
  }
});

// --- Thêm sản phẩm mới ---
qs('#submitProduct').onclick = async () => {
  const title = qs('#prodTitle').value.trim();
  const author = qs('#prodAuthor').value.trim();
  const category = qs('#prodCategory').value.trim();
  const price = parseFloat(qs('#prodPrice').value.replace(',', '.'));
  const rating = parseFloat(qs('#prodRating').value.replace(',', '.'));
  const stock = parseInt(qs('#prodStock').value, 10);
  const imageFile = qs('#prodImage').files[0];

  if (!title || !author || !category || !imageFile || isNaN(price) || isNaN(rating) || isNaN(stock)) {
    alert("Please fill all fields correctly!");
    return;
  }

  const formData = new FormData();
  formData.append('title', title);
  formData.append('author', author);
  formData.append('category', category);
  formData.append('price', price);
  formData.append('rating', rating);
  formData.append('stock', stock);
  formData.append('image', imageFile);

  try {
    const r = await fetch('add_products.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    });
    if (r.status === 403) {
      const txt = await r.text();
      console.error('add_products.php returned 403:', txt);
      alert('Access denied. Are you logged in as admin?');
      return;
    }
    const text = await r.text();
    const contentType = r.headers.get('content-type') || '';
    
    // Kiểm tra nếu response không phải JSON
    if (!contentType.includes('application/json')) {
      console.error('Non-JSON response from add_products.php:', text.substring(0, 200));
      alert('Server error: Received non-JSON response. Please check console for details.');
      return;
    }
    
    // Kiểm tra nếu text bắt đầu bằng HTML tag (có thể có lỗi PHP)
    if (text.trim().startsWith('<')) {
      console.error('HTML response received instead of JSON:', text.substring(0, 200));
      alert('Server error: Received HTML instead of JSON. Please check console for details.');
      return;
    }
    
    let res;
    try {
      res = JSON.parse(text);
    } catch (parseError) {
      console.error('JSON parse error:', parseError);
      console.error('Response text:', text.substring(0, 200));
      alert('Server error: Invalid JSON response. Please check console for details.');
      return;
    }
    
    if (res.status !== 'success') {
      alert('Failed to add product: ' + (res.message || 'Unknown error'));
      return;
    }
    alert(res.message);
    qs('#addProductModal').classList.remove('active');
    location.reload();
  } catch (error) {
    console.error('Error:', error);
    alert('Network/server error: ' + error.message);
  }
};
// hàm xóa sản phẩm 
// --- Xóa sản phẩm ---
function deleteProduct(id) {
  if (!confirm("Bạn có chắc muốn xóa sản phẩm này không?")) return;

  fetch('delete_product.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `id=${id}`
  })
  .then(r => r.json())
  .then(res => {
    if (res.status === 'success') {
      alert("Đã xóa thành công!");
      location.reload();
    } else {
      alert("Xóa thất bại: " + res.message);
    }
  })
  .catch(err => alert("Lỗi: " + err));
}


  // hàm thêm sản phẩm
  // --- Sửa sản phẩm ---
function editProduct(id) {
  const product = products.find(p => p.id == id);
  if (!product) return alert("Không tìm thấy sản phẩm!");

  const modal = qs('#addProductModal');
  modal.classList.add('active');
  const header = qs('#addProductModal h3');
  if (header) header.textContent = 'Sửa sản phẩm';

  qs('#prodTitle').value = product.title;
  qs('#prodAuthor').value = product.author;
  qs('#prodCategory').value = product.category;
  qs('#prodPrice').value = product.price;
  qs('#prodRating').value = product.rating;
  highlightAdminMoodChip(product.category || '');
  if (qs('#prodStock')) qs('#prodStock').value = product.stock || 0;

  const btn = qs('#submitProduct');
  btn.textContent = "Cập nhật sản phẩm";

  const newBtn = btn.cloneNode(true);
  btn.parentNode.replaceChild(newBtn, btn);

  newBtn.onclick = async () => {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('title', qs('#prodTitle').value.trim());
    formData.append('author', qs('#prodAuthor').value.trim());
    formData.append('category', qs('#prodCategory').value.trim());
    // Chuẩn hóa số thập phân: đổi dấu phẩy thành dấu chấm
    const priceVal = qs('#prodPrice').value.replace(',', '.');
    const ratingVal = qs('#prodRating').value.replace(',', '.');
    formData.append('price', parseFloat(priceVal));
    formData.append('rating', parseFloat(ratingVal));
    formData.append('stock', parseInt(qs('#prodStock').value || '0', 10));

    const imageFile = qs('#prodImage').files[0];
    if (imageFile) formData.append('image', imageFile);

    try {
      const r = await fetch('update_product.php', { method: 'POST', body: formData });
      const res = await r.json();
      if (res.status !== 'success') return alert("Cập nhật thất bại: " + res.message);
      alert("Cập nhật thành công!");
      location.reload();
    } catch (err) {
      alert("Lỗi: " + err);
    }
  };
}
