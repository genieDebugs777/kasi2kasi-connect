-- ============================================================
-- Kasi2Kasi Connect — MySQL Schema
-- C2C Marketplace Database
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS message, conversation, review, payment, order_item, orders,
  cart_item, cart, product, category, verification_request, user, role;
SET FOREIGN_KEY_CHECKS = 1;

-- Roles (RBAC)
CREATE TABLE role (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

-- Users
CREATE TABLE user (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone VARCHAR(20),
  role_id INT NOT NULL DEFAULT 4,
  status ENUM('active','suspended','pending') DEFAULT 'active',
  is_verified TINYINT(1) DEFAULT 0,
  is_trusted TINYINT(1) DEFAULT 0,
  avatar_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES role(role_id)
) ENGINE=InnoDB;

-- Categories
CREATE TABLE category (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Products (listings)
CREATE TABLE product (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  category_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  image_url VARCHAR(500),
  status ENUM('active','sold','removed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES user(user_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES category(category_id),
  INDEX idx_seller (seller_id),
  INDEX idx_category (category_id)
) ENGINE=InnoDB;

-- Cart
CREATE TABLE cart (
  cart_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_item (
  cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
  cart_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_cart_product (cart_id, product_id)
) ENGINE=InnoDB;

-- Orders
CREATE TABLE orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
  delivery_address VARCHAR(500),
  delivery_phone VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (buyer_id) REFERENCES user(user_id),
  INDEX idx_buyer (buyer_id)
) ENGINE=InnoDB;

CREATE TABLE order_item (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id)
) ENGINE=InnoDB;

-- Payments
CREATE TABLE payment (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  method ENUM('eft','cash','mobile_money','card') NOT NULL,
  status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
  amount DECIMAL(10,2) NOT NULL,
  reference VARCHAR(100),
  paid_at TIMESTAMP NULL,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reviews
CREATE TABLE review (
  review_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_user_product_review (user_id, product_id)
) ENGINE=InnoDB;

-- Messaging
CREATE TABLE conversation (
  conversation_id INT AUTO_INCREMENT PRIMARY KEY,
  user1_id INT NOT NULL,
  user2_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user1_id) REFERENCES user(user_id) ON DELETE CASCADE,
  FOREIGN KEY (user2_id) REFERENCES user(user_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_pair (user1_id, user2_id)
) ENGINE=InnoDB;

CREATE TABLE message (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT NOT NULL,
  sender_id INT NOT NULL,
  content TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conversation_id) REFERENCES conversation(conversation_id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES user(user_id) ON DELETE CASCADE,
  INDEX idx_conv (conversation_id)
) ENGINE=InnoDB;

-- Verification requests
CREATE TABLE verification_request (
  request_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  id_number VARCHAR(20) NOT NULL,
  id_document_url VARCHAR(500),
  selfie_url VARCHAR(500),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  reviewed_by INT NULL,
  reviewed_at TIMESTAMP NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES user(user_id)
) ENGINE=InnoDB;

-- Notifications table for seller alerts
CREATE TABLE IF NOT EXISTS notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NULL,
    type ENUM('low_stock', 'out_of_stock', 'new_order', 'order_update', 'verification') DEFAULT 'order_update',
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;