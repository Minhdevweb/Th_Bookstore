const qs = (s) => document.querySelector(s);
const qsAll = (s) => document.querySelectorAll(s);

let products = [];
let cart = [];

// Debug: Kiểm tra file
fetch('add_products.php')
  .then(r => r.text())
  .then(text => {
    console.log('add_product.php is accessible');
  })
  .catch(err => {
    console.error('Cannot access add_product.php:', err);
  });

// --- Lấy dữ liệu sản phẩm ---
fetch('get_products.php')
  .then(r => r.json())
  .then(data => {
    products = data;
    renderProducts(products);
  })
  .catch(err => {
    console.error('Error loading products:', err);
  });

// --- Hiển thị danh sách sản phẩm ---
function renderProducts(list) {
  const grid = qs('#productGrid');
  grid.innerHTML = "";

  list.forEach(p => {
    // nếu image là chỉ tên file thì thêm ../uploads/
    let imgSrc = p.image;
    if (!imgSrc.startsWith('http') && !imgSrc.startsWith('../uploads/')) {
      imgSrc = '../uploads/' + imgSrc;
    }

    grid.innerHTML += `
      <div class="card">
        <img src="${imgSrc}" alt="${p.title}" 
             onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
        <div class="card-body">
          <h4>${p.title}</h4>
          <p>${p.author}</p>
          <div class="rating">${'★'.repeat(Math.round(p.rating))}</div>
        </div>
        <div class="card-footer">
          <span class="price">$${p.price}</span>
          <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:5px;">
            <button onclick="addToCart(${p.id})" class="btn-small">Add</button>
            <button onclick="editProduct(${p.id})" class="btn-small btn-edit">Edit</button>
            <button onclick="deleteProduct(${p.id})" class="btn-small btn-delete">Delete</button>
          </div>
        </div>
      </div>
    `;
  });
}



// --- Giỏ hàng ---
function addToCart(id) {
  const item = products.find(p => p.id == id);
  if (!item) return;

  const existing = cart.find(p => p.id == id);
  if (existing) {
    existing.quantity++;
  } else {
    cart.push({...item, quantity: 1});
  }

  updateCartUI();
  qs('#cartCount').textContent = cart.length;
  qs('#cartModal').classList.add('active');
}

function updateCartUI() {
  const cartContainer = qs('#cartItems');
  const totalElement = qs('#cartTotal');

  if (!cartContainer) return;

  if (cart.length === 0) {
    cartContainer.innerHTML = '<p style="text-align:center;color:gray;">Cart is empty.</p>';
    totalElement.textContent = '0.00';
    return;
  }

  let total = 0;
  cartContainer.innerHTML = cart.map(p => {
    total += p.price * p.quantity;
    return `
      <div class="cart-item" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <img src="${p.image}" alt="${p.title}" style="width:50px;height:50px;object-fit:cover;">
        <div style="flex:1;">
          <h5 style="margin:0;">${p.title}</h5>
          <p style="margin:0;color:gray;">$${p.price} × ${p.quantity}</p>
        </div>
        <button class="remove" onclick="removeFromCart(${p.id})" style="background:none;border:none;color:red;cursor:pointer;">✖</button>
      </div>
    `;
  }).join('');

  totalElement.textContent = total.toFixed(2);
}

function removeFromCart(id) {
  cart = cart.filter(p => p.id != id);
  updateCartUI();
  qs('#cartCount').textContent = cart.length;
}

// --- Modal handling ---
qsAll('.close').forEach(btn => {
  btn.onclick = () => document.getElementById(btn.dataset.close).classList.remove('active');
});

// QUAN TRỌNG: ĐÃ THÊM SỰ KIỆN NÚT ADD PRODUCT
qs('#addProductBtn').onclick = () => {
  console.log('Opening add product modal');
  qs('#addProductModal').classList.add('active');
};

qs('#loginBtn').onclick = () => qs('#loginModal').classList.add('active');
qs('#registerBtn').onclick = () => qs('#regModal').classList.add('active');
qs('#cartBtn').onclick = () => {
  qs('#cartModal').classList.add('active');
  updateCartUI();
};

// --- Đăng ký ---
qs('#submitReg').onclick = () => {
  const email = qs('#regEmail').value;
  const pwd = qs('#regPwd').value;
  const confirm = qs('#regConfirm').value;
  if (pwd !== confirm) return alert("Passwords do not match");

  fetch('register.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `email=${email}&password=${pwd}`
  }).then(r=>r.text()).then(res=>{
    if(res==='success'){
      alert('Registered successfully');
      qs('#regModal').classList.remove('active');
    } else alert('Registration failed');
  });
};

// --- Đăng nhập ---
qs('#submitLogin').onclick = () => {
  const email = qs('#loginEmail').value;
  const pwd = qs('#loginPwd').value;

  fetch('login.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `email=${email}&password=${pwd}`
  }).then(r=>r.text()).then(res=>{
    if(res==='success'){
      alert('Login successful');
      qs('#loginModal').classList.remove('active');
    } else alert('Invalid login');
  });
};

// --- Dark mode ---
const toggle = qs('#themeToggle');
toggle.onclick = () => {
  document.body.classList.toggle('dark');
  localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
};
if (localStorage.getItem('theme') === 'dark') {
  document.body.classList.add('dark');
}

// --- Lọc sản phẩm ---
qs('#apply').onclick = () => {
  const category = qs('#category').value.trim().toLowerCase();
  const price = qs('#price').value.trim();
  const rating = qs('#rating').value.trim();

  let filtered = products.slice();

  if (category !== "") {
    filtered = filtered.filter(p => p.category.toLowerCase() === category);
  }

  if (price !== "") {
    filtered = filtered.filter(p => {
      const pr = parseFloat(p.price);
      if (price === "Under $10") return pr < 10;
      if (price === "$10-20") return pr >= 10 && pr <= 20;
      if (price === "$20-50") return pr > 20 && pr <= 50;
      if (price === "Over $50") return pr > 50;
    });
  }

  if (rating !== "") {
    filtered = filtered.filter(p => parseFloat(p.rating) >= parseFloat(rating));
  }

  renderProducts(filtered);

  if (filtered.length === 0) {
    qs('#productGrid').innerHTML = '<p style="text-align:center; color:gray;">No products found.</p>';
  }
};

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
    qs('#productGrid').innerHTML = '<p style="text-align:center;color:gray;">No products found.</p>';
  }
});

// --- Thêm sản phẩm mới ---
qs('#submitProduct').onclick = () => {
  const title = qs('#prodTitle').value.trim();
  const author = qs('#prodAuthor').value.trim();
  const category = qs('#prodCategory').value.trim();
  const price = parseFloat(qs('#prodPrice').value);
  const rating = parseFloat(qs('#prodRating').value);
  const imageFile = qs('#prodImage').files[0];

  if (!title || !author || !category || !imageFile || isNaN(price) || isNaN(rating)) {
    alert("Please fill all fields correctly!");
    return;
  }

  const formData = new FormData();
  formData.append('title', title);
  formData.append('author', author);
  formData.append('category', category);
  formData.append('price', price);
  formData.append('rating', rating);
  formData.append('image', imageFile);

  
  fetch('add_products.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(res => {
    if (res.status === 'success') {
      alert(res.message);
      qs('#addProductModal').classList.remove('active');
      location.reload();
    } else {
      alert('Failed to add product: ' + res.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Network error: ' + error);
  });
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

  qs('#prodTitle').value = product.title;
  qs('#prodAuthor').value = product.author;
  qs('#prodCategory').value = product.category;
  qs('#prodPrice').value = product.price;
  qs('#prodRating').value = product.rating;

  const btn = qs('#submitProduct');
  btn.textContent = "Update Product";

  // Gỡ sự kiện cũ rồi thêm sự kiện update
  const newBtn = btn.cloneNode(true);
  btn.parentNode.replaceChild(newBtn, btn);

  newBtn.onclick = () => {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('title', qs('#prodTitle').value.trim());
    formData.append('author', qs('#prodAuthor').value.trim());
    formData.append('category', qs('#prodCategory').value.trim());
    formData.append('price', parseFloat(qs('#prodPrice').value));
    formData.append('rating', parseFloat(qs('#prodRating').value));

    const imageFile = qs('#prodImage').files[0];
    if (imageFile) formData.append('image', imageFile);

    fetch('update_product.php', { method: 'POST', body: formData })
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          alert("Cập nhật thành công!");
          location.reload();
        } else {
          alert("Cập nhật thất bại: " + res.message);
        }
      })
      .catch(err => alert("Lỗi: " + err));
  };
}