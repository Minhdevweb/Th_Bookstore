<?php include "config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TH Bookstore</title>
        <!-- Inline SVG favicon to avoid missing /favicon.ico 404 in console -->
        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%230b5ed7'/%3E%3Cpath d='M20 25h40v50H20z' fill='%23fff'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/chatbot.css">
    <link rel="stylesheet" href="../CSS/footer.css">
    <link rel="stylesheet" href="../CSS/wheel.css">
    <link rel="stylesheet" href="../CSS/wheel-custom.css">
    <link rel="stylesheet" href="../CSS/carousel.css">
    <script>
        // Thêm biến toàn cục để kiểm tra role (Cần giữ lại PHP ở đây)

        const isAdmin = <?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'true' : 'false'; ?>;
        const openModalFromQuery = <?php echo json_encode($_GET['open'] ?? ''); ?>;
    </script>
    
</head>
<body>
  <header>
    <div class="logo">TH BOOKs</div>
    <div class="controls">
      <div class="controls-left">
        <button id="themeToggle" class="btn"><i class="fas fa-adjust"></i></button>
        <?php if (isset($_SESSION['email'])): ?>
          <a href="order_history.php" class="btn"><i class="fas fa-history"></i> Lịch sử đơn hàng</a>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin_orders.php" class="btn"><i class="fas fa-warehouse"></i> Quản lý shop</a>
          <?php endif; ?>
          <a href="logout.php" class="btn">Đăng xuất</a>
        <?php else: ?>
          <a href="login.php" class="btn">Đăng nhập</a>
          <!-- <a href="login.php" class="btn">Đăng ký</a> -->
        <?php endif; ?>
        <a href="#" id="cartBtn" class="btn cart"><i class="fas fa-shopping-cart"></i><span id="cartCount">0</span></a>
      </div>
      <div class="search-wrapper controls-right">
        <input type="search" id="search" placeholder="Search books..." />
        <i class="fas fa-search"></i>
      </div>
    </div>
    <a href="blog.php" class="btn blog-btn">
      <i class="fas fa-book-open"></i> Blog
    </a>
  </header>
 
  <!-- Hero banner giới thiệu 1–2 loại sách nổi bật -->
  <section class="hero-section">
    <div class="hero-copy">
      <p class="hero-kicker" id="heroKicker">SÁCH NỔI BẬT TRONG NĂM</p>
      <h1 id="heroMainTitle">BEST BOOKS OF THE YEAR</h1>
      <p class="hero-subtitle">Giảm <span>30%</span> cho đơn hàng trên <strong>$199</strong></p>
      <div class="hero-tags">
        <span id="heroTagPrimary">English Books</span>
        <span id="heroTagSecondary">Vietnamese Books</span>
      </div>
      <button class="hero-btn" onclick="document.getElementById('productGrid').scrollIntoView({ behavior: 'smooth' });">
        VIEW ALL BOOKS
      </button>
    </div>
    <div class="hero-visual">
      <div class="hero-book">
        <img id="heroImage" class="hero-image" src="../images/logo_thbooks.png" alt="Featured book">
        <div class="hero-book-label" id="heroCategory">Editor's pick</div>
        <div class="hero-book-title" id="heroTitle">The Ride of a Lifetime</div>
        <div class="hero-book-author" id="heroAuthor">Bob Dover</div>
      </div>
      <div class="hero-badge">
        <span>-30%</span>
        <small>On order over $199</small>
      </div>
    </div>
  </section>

  <!-- Section giới thiệu nhà sách trực tuyến -->
  <section class="intro-section">
    <h2 class="intro-title">NHÀ SÁCH TRỰC TUYẾN DÀNH CHO BẠN</h2>
    <p class="intro-text">
      “Một nơi những câu chuyện được sống lại. Khám phá các tác phẩm kinh điển vượt thời gian,
      những tựa sách hiện đại được yêu thích và những cuốn sách tuyển chọn dành riêng cho mọi độc giả.”
    </p>
    <div class="intro-badges">
      <div class="intro-badge">
        <i class="fas fa-truck-fast"></i>
        <span>Giao hàng nhanh chóng</span>
      </div>
      <div class="intro-badge">
        <i class="fas fa-book-open"></i>
        <span>Sách chính hãng, đa dạng</span>
      </div>
      <div class="intro-badge">
        <i class="fas fa-clock"></i>
        <span>Đọc mọi lúc, mọi nơi</span>
      </div>
      <div class="intro-badge">
        <i class="fas fa-gift"></i>
        <span>Ưu đãi cho thành viên</span>
      </div>
    </div>
  </section>
  <div class="content">
    <aside class="filters">
      <h4>Filters</h4>
      <select id="category">
        <option value="">Tất cả danh sách</option>
        <option>English Books</option>
        <option>Vietnamese Books</option>
        <option>Stationery</option>
      </select>
      <select id="price">
        <option value="">Vùng giá</option>
        <option>Under $10</option>
        <option>$10-20</option>
        <option>$20-50</option>
        <option>Over $50</option>
      </select>
      <select id="rating">
        <option value="">Tỉ lệ sao</option>
        <option>4.0</option>
        <option>4.5</option>
        <option>4.7</option>
      </select>
      <div class="filter-group">
        <p class="filter-label">Chủ đề cảm xúc</p>
        <div class="mood-tags" id="moodTags">
          <button type="button" class="mood-chip" data-value="Buồn">Buồn</button>
          <button type="button" class="mood-chip" data-value="Vui">Vui</button>
          <button type="button" class="mood-chip" data-value="Động lực">Động lực</button>
          <button type="button" class="mood-chip" data-value="Hài hước">Hài hước</button>
          <button type="button" class="mood-chip" data-value="Lãng mạn">Lãng mạn</button>
          <button type="button" class="mood-chip" data-value="Phiêu lưu">Phiêu lưu</button>
        </div>
      </div>
      <button id="apply" class="btn-modal">Áp dụng</button>
    </aside>
    
    <main>
      <section class="grid" id="productGrid"></section>
      <div id="pagination"></div>

    </main>
  </div>

  <div class="modal" id="cartModal">
    <div class="modal-content">
      <button class="close" data-close="cartModal">&times;</button>
      <h3>Your Cart</h3>
      <div id="cartItems"></div>
      <p>Total: $<span id="cartTotal">0.00</span></p>
      <button id="checkout" class="btn-modal">Thanh Toán</button>
    </div>
  </div>
  
  <div class="modal" id="addProductModal">
    <div class="modal-content add-product-card">
      <button class="close" data-close="addProductModal">&times;</button>
      <h3>Thêm Sản Phẩm</h3>

      <div class="add-product-grid">
        <div class="form-field">
          <label>Tiêu đề</label>
          <input id="prodTitle" type="text" required />
        </div>

        <div class="form-field">
          <label>Tác giả</label>
          <input id="prodAuthor" type="text" required />
        </div>

        <div class="form-field full">
          <label>Thể loại</label>
          <input id="prodCategory" type="text" placeholder="Nhập hoặc chọn chủ đề cảm xúc" required />
          <div class="filter-group mood-select-group">
            <p class="filter-label">Chủ đề cảm xúc nhanh</p>
            <div class="mood-tags admin-mood-tags">
              <button type="button" class="mood-chip admin-mood-chip" data-value="Buồn">Buồn</button>
              <button type="button" class="mood-chip admin-mood-chip" data-value="Vui">Vui</button>
              <button type="button" class="mood-chip admin-mood-chip" data-value="Động lực">Động lực</button>
              <button type="button" class="mood-chip admin-mood-chip" data-value="Hài hước">Hài hước</button>
              <button type="button" class="mood-chip admin-mood-chip" data-value="Lãng mạn">Lãng mạn</button>
              <button type="button" class="mood-chip admin-mood-chip" data-value="Phiêu lưu">Phiêu lưu</button>
            </div>
          </div>
        </div>

        <div class="form-field">
          <label>Giá</label>
          <input id="prodPrice" type="number" min="0" step="0.01" required />
        </div>

        <div class="form-field">
          <label>Đánh giá</label>
          <input id="prodRating" type="number" min="0" max="5" step="0.1" required />
        </div>

        <div class="form-field">
          <label>Tồn kho</label>
          <input id="prodStock" type="number" min="0" step="1" value="0" required />
        </div>

        <div class="form-field full">
          <label>File Ảnh</label>
          <input id="prodImage" type="file" accept="image/*" required />
        </div>
      </div>

      <button id="submitProduct" class="btn-modal btn-full">Thêm Sản Phẩm</button>
    </div>
  </div>

  <!-- Modal Backdrop for all modals -->
  <div class="modal-backdrop" id="modalBackdrop"></div>

  <!-- Orders Modal -->

  <div class="orders-modal" id="ordersModal">
    <h2>My Orders</h2>
    <div id="ordersList"></div>
    <button onclick="closeModal('ordersModal')">Đóng</button>
  </div>

  <!-- Admin Orders Modal -->
  <div class="orders-modal" id="adminOrdersModal">
    <h2>Manage Orders</h2>
    <div id="adminOrdersList"></div>
    <button onclick="closeModal('adminOrdersModal')">Đóng</button>
  </div>
        <!-- tạo chatbot gợi ý sách -->
        <button id="chatbotBtn" class="chatbot-toggle" aria-label="Mở chatbot tư vấn">
          <i class="fas fa-robot" aria-hidden="true"></i>
        </button>

        <!-- chatbot window -->
        <div class="chatbot-window" id="chatbotWindow">
          <div class="chatbot-header">
            <h3>Chatbot Tư Vấn Sách</h3>
            <button class="chatbot-close" id="chatbotClose">&times;</button>
          </div>
          <div class="chatbot-messages" id="chatbotMessages">
            <!-- Messages sẽ được thêm vào đây bằng JavaScript -->
          </div>
          <div class="chatbot-input">
            <input type="text" id="chatbotInput" placeholder="Nhập câu hỏi của bạn...">
            <button id="chatbotSend"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
<!-- footer -->
<footer class="footer">
        <div class="footer_collumn footer_brand">
          <img src="../images/thbooj.png" alt="th Books">
          <p>34 Trần Đại Nghĩa, Quận Hải Châu, Thành phố Đà Nẵng</p>
          <p>Công ty cổ phần th Bookstore TP.Đà Nẵng - th Books</p>

            <div class="footer_social">
              <a href="https://www.facebook.com/minh.vu.981194" target="_blank" rel="noreferrer noopener">
                <img src="../images/icon_fb.png" alt="Facebook">
              </a>
              <a href="https://www.instagram.com/__tn.mnh___/" target="_blank" rel="noreferrer noopener"> 
                <img src="../images/icon_ins.jpg" alt="Instagram">
              </a>
            </div>
            <div class="footer_badges">
              <img src="../images/icon_ggplay.png" alt="Google Play">
              <img src="../images/icon_appstore.jpg" alt="App Store">
            </div>
        </div>

        <div class="footer_column">
          <h4>DỊCH VỤ</h4>
          <a href="#">Điều khoản sử dụng</a>
          <a href="#">Chính sách bảo mật thông tin cá nhân</a>
          <a href="#">Chính sách bảo mật thanh toán</a>
          <a href="#">Giới thiệu Th Bookstore</a>
          <a href="#">Hệ thống nhà sách th Bookstore</a>
        </div>
          <div class="footer_column">
        <h4>HỖ TRỢ</h4>
        <a href="#">Chính sách đổi - trả - hoàn tiền</a>
        <a href="#">Chính sách bảo hành - bồi hoàn</a>
        <a href="#">Chính sách vận chuyển</a>
        <a href="#">Chính sách khách sỉ</a>
      </div>

      <div class="footer_column">
        <h4>Tài khoản của tôi</h4>
        <a href="#">Đăng nhập / Tạo mới tài khoản </a>
        <a href="#">Thay đổi địa chỉ khách hàng</a>
        <a href="#">Chi tiết tài khoản</a>
        <a href="#">Lịch sử mua hàng</a>

        <div class="footer_contact">
          <p><span>Địa chỉ:</span> 34 Trần Đại Nghĩa - Q.Hải Châu - TP.Đà Nẵng</p>
            <p><span>Email:</span> cskh@thBooks.com.vn</p>
            <a href="#"><span>Về chúng tôi</span></a>
            <p><span>Hotline:</span> 1900636467</p>
        </div>
      </div>

     
 </footer>

  <script src="../javascript/modal.js"></script>
  <script src="../javascript/main.js"></script>
  <script src="../javascript/heroCarousel.js"></script>
  <script src="../javascript/cart.js"></script>
  <script src="../javascript/orders.js"></script>
  <script src="../javascript/chatbot.js"></script>
</body>
<div id="lucky-btn" onclick="openWheel()">
    <i class="fas fa-gift fa-shake"></i>
</div>

<div id="wheelModal" class="wheel-modal">
    <div class="wheel-content">
        <span class="close-wheel" onclick="closeWheel()">&times;</span>
        <h3>VÒNG QUAY MAY MẮN</h3>
        
        <div class="wheel-container">
            <div class="marker"></div>
            <canvas id="canvas" width="500" height="500"></canvas>
            <div class="spin-btn" onclick="spin()">QUAY</div>
        </div>

        <p id="result-msg">Chúc bạn may mắn!</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script src="../javascript/wheel.js"></script>
</html>