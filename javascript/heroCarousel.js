// ==========================================
// HERO CAROUSEL – Tự động chạy slide sách
// Phụ thuộc hàm qs / qsAll (trong main.js)
// ==========================================

let heroProducts = [];
let heroIndex = 0;
let heroIntervalId = null;

/**
 * Cập nhật nội dung slide đang hiển thị
 */
function updateHeroSlide() {
  if (!heroProducts.length) return;

  const p = heroProducts[heroIndex];
  if (!p) return;

  // ================================
  // Lấy các phần tử DOM
  // ================================
  const titleEl      = qs('#heroTitle');
  const authorEl     = qs('#heroAuthor');
  const catEl        = qs('#heroCategory');
  const tagPrimary   = qs('#heroTagPrimary');
  const tagSecondary = qs('#heroTagSecondary');
  const imgEl        = qs('#heroImage');

  // ================================
  // Gán nội dung text
  // ================================
  if (titleEl)      titleEl.textContent = p.title || '';
  if (authorEl)     authorEl.textContent = p.author || '';
  if (catEl)        catEl.textContent = p.category || 'Featured book';

  if (tagPrimary)   tagPrimary.textContent = p.category || 'Books';
  if (tagSecondary) tagSecondary.textContent = `Rating ${p.rating || ''}★`;

  // ================================
  // Gán hình ảnh
  // ================================
  if (imgEl) {
    let src = p.image || '';

    // Nếu có hàm xử lý đường dẫn ảnh thì dùng
    if (typeof formatImageSrc === 'function') {
      src = formatImageSrc(src);
    }

    // Ảnh fallback
    if (!src) {
      src = 'https://via.placeholder.com/300x400?text=No+Image';
    }

    imgEl.src = src;
    imgEl.alt = p.title || 'Book';
  }
}

/**
 * Khởi động hero carousel
 */
function startHeroCarousel(items) {
  const heroSection = qs('.hero-section');
  if (!heroSection || !items?.length) return;

  // Lưu danh sách sản phẩm
  heroProducts = items.slice();
  heroIndex = 0;

  updateHeroSlide();

  // Xóa interval cũ (nếu có) để tránh chạy 2 lần
  if (heroIntervalId) {
    clearInterval(heroIntervalId);
  }

  // Chạy slide mỗi 5s
  heroIntervalId = setInterval(() => {
    heroIndex = (heroIndex + 1) % heroProducts.length;
    updateHeroSlide();
  }, 5000);
}

/**
 * Tải toàn bộ sản phẩm và khởi chạy carousel
 */
(async function initHeroCarousel() {
  try {
    const res = await fetch('get_all_products.php');
    const data = await res.json();

    if (data.status !== 'success') return;
    if (!Array.isArray(data.products) || !data.products.length) return;

    startHeroCarousel(data.products);

  } catch (err) {
    console.error('Error loading hero carousel products:', err);
  }
})();
