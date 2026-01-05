<!-- hiển thị trang chi tiết một cuốn sách  kèm bình luận, -->
<?php
include "config.php";
// Lấy ID sản phẩm từ URL
$id = intval($_GET['id'] ?? 0);
// nếu id không hợp lệ thì chuyển về trang blog chính
if ($id <= 0) { 
    header("Location: blog.php"); 
    exit;
 }
// Truy vấn lấy thông tin sách theo id và chỉ lấy sách đang hoạt động
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $id);// Gán tham số id
$stmt->execute();
// Lấy kết quả
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();
// Nếu không tìm thấy sách, chuyển hướng về trang blog chính
if (!$book) { 
    header("Location: blog.php"); 
    exit;
 }
// Xử lý đường dẫn ảnh
$img = str_starts_with($book['image'],'http') ? $book['image'] : '../uploads/'.basename($book['image']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - Review</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/blog.css?v=3">
</head>
<body data-product-id="<?= $id ?>">
<div class="container detail-container">
    <p><a href="blog.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a></p>

    <div class="book-detail">
        <div class="detail-image">
            <img src="<?= $img ?>" alt="<?= htmlspecialchars($book['title']) ?>">
        </div>
        <div class="detail-content">
            <h1><?= htmlspecialchars($book['title']) ?></h1>
            <!-- thông tin tác giả -->
            <p><strong>Tác giả:</strong> <?= htmlspecialchars($book['author']) ?></p> 
            <p><strong>Thể loại:</strong> <?= htmlspecialchars($book['category']) ?></p>
            <p><strong>Giá:</strong> <span class="price">$<?= number_format($book['price'], 2, '.', ',') ?></span></p>
            <p><strong>Đánh giá:</strong> <i class="fas fa-star rating-star"></i> <?= $book['rating'] ?>/5.0</p>
            <!-- Nút mua sách -->
            <div class="actions">
                <a href="index.php?add_to_cart=<?= $book['id'] ?>" class="btn-buy">
                    <i class="fas fa-shopping-cart"></i> Mua ngay tại TH Bookstore
                </a>
            </div>

            <!-- Form bình luận (không reload trang) -->
            <div class="comment-form">
                <h3>Để lại bình luận của bạn</h3>
                <form id="commentForm">
                    <input type="text" id="commentName" placeholder="Tên của bạn" required>
                    <textarea id="commentContent" rows="4" placeholder="Viết cảm nhận của bạn..." required></textarea>
                    <button type="submit">Gửi bình luận</button>
                </form>
            </div>

            <!-- Danh sách bình luận (sẽ được JS fill) -->
            <div class="comments-section">
                <h3 id="commentCount">Bình luận (0)</h3>
                <div id="commentsList">Đang tải bình luận...</div>
            </div>
        </div>
    </div>
</div>
<script src="../javascript/comments.js"></script>

</body>
</html>