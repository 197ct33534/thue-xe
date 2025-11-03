CREATE TABLE vehicle_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dữ liệu mẫu
INSERT INTO vehicle_categories (name, slug, description, icon, display_order, is_active) VALUES
('Xe số', 'xe-so', 'Xe máy côn tay, số sàn', 'icon-xe-so.png', 1, 1),
('Xe ga', 'xe-ga', 'Xe tay ga tự động', 'icon-xe-ga.png', 2, 1),
('Ô tô du lịch', 'o-to-du-lich', 'Xe 4-16 chỗ', 'icon-o-to.png', 3, 1),
('Bán tải', 'ban-tai', 'Xe pickup, chở hàng', 'icon-ban-tai.png', 4, 1);