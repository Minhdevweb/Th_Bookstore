<!-- blog.php - PHIÊN BẢN NÂNG CẤP -->
<?php include "config.php"; 

// Xử lý tìm kiếm
$search = trim($_GET['s'] ?? '');
$search_sql = '';
if ($search !== '') {
    $search = $conn->real_escape_string($search);
    $search_sql = " AND (title LIKE '%$search%' OR author LIKE '%$search%') ";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $search ? "Tìm kiếm: $search - " : "" ?>Sách Hay - TH Bookstore Blog</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/blog.css?v=<?= time() ?>">
</head>
<body>
    <header class="blog-header">
        <div class="container">
            <h1><a href="blog.php">Sách Hay</a></h1>
            <p class="slogan">Những cuốn sách đáng đọc nhất – Đánh giá chân thực từ cộng đồng</p>

            <!-- Ô tìm kiếm -->
            <form class="search-form" method="GET">
                <input type="text" name="s" placeholder="Tìm tên sách, tác giả..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </header>

    <div class="container main-content">
        <?php include "blog_sidebar.php"; ?>

        <div class="book-grid">
            <h2>
                <?php if ($search): ?>
                    Kết quả tìm kiếm cho: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                <?php elseif(isset($_GET['cat'])): ?>
                    Thể loại: <?= htmlspecialchars($_GET['cat']) ?>
                <?php else: ?>
                    Blog về sách
                <?php endif; ?>
            </h2>

            <div class="books">
                <?php
                $sql = "SELECT * FROM products WHERE is_active = 1 $search_sql";
                if (isset($_GET['cat']) && !empty($_GET['cat'])) {
                    $cat = $conn->real_escape_string($_GET['cat']);
                    $sql .= " AND category = '$cat'";
                }
                $sql .= " ORDER BY id DESC";
                $result = $conn->query($sql);

                if ($result->num_rows == 0) {
                    echo "<p class='no-results'>Không tìm thấy sách nào phù hợp 😔</p>";
                } else {
                    while ($p = $result->fetch_assoc()) {
                        $img = !empty($p['image']) ? (str_starts_with($p['image'], 'http') ? $p['image'] : "../uploads/" . basename($p['image'])) : '../images/no-image.jpg';
                        $title = htmlspecialchars($p['title']);
                        $author = htmlspecialchars($p['author'] ?? 'Không rõ');
                ?>
                        <div class="book-card">
                            <img src="<?= $img ?>" alt="<?= $title ?>">
                            <div class="book-info">
                                <h3><?= $title ?></h3>
                                <p class="author">Tác giả: <?= $author ?></p>
                                <div class="meta">
                                    <span class="rating"><i class="fas fa-star"></i> <?= number_format($p['rating'],1) ?></span>
                                    <span class="price">$<?= number_format($p['price'], 2, '.', ',') ?></span>
                                </div>
                                <p class="category-tag"><?= htmlspecialchars($p['category']) ?></p>
                            </div>
                            <!-- Nút chuyển về trang sản phẩm chính và tự động thêm vào giỏ hàng -->
                            <a href="index.php?add_to_cart=<?= $p['id'] ?>" class="buy-link">
                                <i class="fas fa-shopping-cart"></i> Mua ngay
                            </a>
                            <!-- Link xem review trong blog -->
                            <a href="blog_detail.php?id=<?= $p['id'] ?>" class="review-link">
                                <i class="fas fa-comment-dots"></i> Đọc review
                            </a>
                        </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <footer class="blog-footer">
        <p>© 2025 Sách Hay - TH Bookstore Blog</p>
    </footer>
</body>
</html>