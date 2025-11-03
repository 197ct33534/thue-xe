CREATE TABLE vehicles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    year INT,
    color VARCHAR(50),
    seats INT,
    transmission ENUM('manual', 'automatic') DEFAULT 'manual',
    fuel_type ENUM('gasoline', 'diesel', 'electric', 'hybrid') DEFAULT 'gasoline',
    price_per_day DECIMAL(10,2) NOT NULL,
    price_per_hour DECIMAL(10,2) DEFAULT 0,
    deposit DECIMAL(10,2) DEFAULT 0,
    mileage_limit INT DEFAULT 0,
    description TEXT,
    features JSON,
    thumbnail VARCHAR(500),
    status ENUM('available', 'rented', 'maintenance', 'inactive') DEFAULT 'available',
    rating_avg DECIMAL(2,1) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id) ON DELETE RESTRICT
);

-- Index tối ưu
CREATE INDEX idx_category_status ON vehicles(category_id, status);
CREATE INDEX idx_license_plate ON vehicles(license_plate);
CREATE INDEX idx_status ON vehicles(status);

-- Dữ liệu mẫu
INSERT INTO vehicles (category_id, name, slug, license_plate, brand, model, year, color, seats, 
                     transmission, fuel_type, price_per_day, price_per_hour, deposit, mileage_limit, 
                     thumbnail, status, is_featured) VALUES
(1, 'Honda Wave Alpha 110', 'honda-wave-alpha-110', '29A-12345', 'Honda', 'Wave Alpha', 2023, 'Đỏ', 2, 
 'manual', 'gasoline', 150000, 25000, 500000, 100, 'wave-alpha.jpg', 'available', 1),
(2, 'Honda Vision 125', 'honda-vision-125', '29A-12346', 'Honda', 'Vision', 2023, 'Trắng', 2, 
 'automatic', 'gasoline', 180000, 30000, 600000, 100, 'vision-125.jpg', 'available', 1),
(3, 'Toyota Vios 1.5G', 'toyota-vios-15g', '30A-67890', 'Toyota', 'Vios', 2024, 'Bạc', 5, 
 'automatic', 'gasoline', 1200000, 150000, 5000000, 200, 'vios.jpg', 'available', 1);