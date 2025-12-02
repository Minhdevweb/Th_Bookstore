<!-- blog_sidebar.php -->
<div class="sidebar">
    <h3>Sắp xếp theo</h3>
    <select id="sortSelect" onchange="location.href='blog.php?s=<?= urlencode($search) ?>'">
        <option value="">Mới nhất</option>
        <option value="">Đánh giá cao</option>
    </select>

    <h3>Thể loại</h3>
    <ul class="category-list">
        <li><a href="blog.php">Tất cả</a></li>
        <?php
        $cats = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND is_active = 1 ORDER BY category");
        while ($row = $cats->fetch_assoc()) {
            $cat_raw = $row['category'];
            $cat = htmlspecialchars($cat_raw);
            $isActive = isset($_GET['cat']) && $_GET['cat'] === $cat_raw;
            $activeClass = $isActive ? 'active-category' : '';
            echo "<li><a class='$activeClass' href='blog.php?cat=" . urlencode($cat_raw) . "'>$cat</a></li>";
        }
        ?>
    </ul>

    <div class="sidebar-info">
        <p><i class="fas fa-globe"></i> Độ uy tín: <strong>31</strong></p>
        <p><i class="fas fa-eye"></i> ~4.000 lượt/tháng</p>
        <p><i class="fas fa-star"></i> Chấp nhận sách indie</p>
    </div>
</div>