CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL
);


CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,

    is_active TINYINT(1) DEFAULT 1, -- 1 active, 0 disabled

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ================================
-- SUPPLIERS
-- ================================
CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    address VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ================================
-- CATEGORIES
-- ================================

-- ================================
-- ITEMS
-- ================================

-- ================================
-- STOCK (LIVE QUANTITY ONLY)
-- ================================
CREATE TABLE tbl_item_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    qty DOUBLE NOT NULL DEFAULT 0,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id)
);

-- ================================
-- PURCHASE REQUEST
-- ================================
CREATE TABLE purchase_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    request_date DATE NOT NULL,

    status ENUM('pending','approved','received') DEFAULT 'pending',

    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE purchase_request_items (
    pri_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    qty_requested DOUBLE NOT NULL,

    FOREIGN KEY (request_id) REFERENCES purchase_request(request_id),
    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id)
);

-- ================================
-- PROGRESS (ALL MOVEMENTS: IN/OUT)
-- ================================
CREATE TABLE tbl_progress (
    prog_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,

    date DATE NOT NULL,

    in_qty DOUBLE DEFAULT 0,     -- Added stock
    out_qty DOUBLE DEFAULT 0,    -- Sold / removed

    last_qty DOUBLE DEFAULT 0,   -- Before movement
    end_qty DOUBLE NOT NULL,     -- After movement

    new_price DOUBLE DEFAULT NULL,
    remark TEXT,

    created_by INT NOT NULL,     -- Who did it
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ================================
-- STOCK TAKE (PHYSICAL COUNT)
-- ================================
CREATE TABLE stock_take (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    qty DOUBLE NOT NULL,
    status INT NOT NULL,

    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ================================
-- RECEIPT HEADER / MAIN TABLE
-- ================================
CREATE TABLE receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(50) NOT NULL UNIQUE,

    total_amount DOUBLE NOT NULL DEFAULT 0,
    discount DOUBLE DEFAULT 0,
    grand_total DOUBLE NOT NULL DEFAULT 0,   -- total - discount

    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ================================
-- RECEIPT ITEMS (ITEMS OF THAT RECEIPT)
-- ================================
CREATE TABLE receipt_items (
    ri_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(50) NOT NULL,

    item_id INT NOT NULL,
    qty DOUBLE NOT NULL,
    price DOUBLE NOT NULL,              -- unit price at time of sale
    total DOUBLE NOT NULL,              -- qty * price

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id) REFERENCES tbl_items(item_id),
    FOREIGN KEY (receipt_code) REFERENCES receipts(receipt_code)
);









CREATE TABLE tbl_categories (
    cat_id INT AUTO_INCREMENT PRIMARY KEY,
    cat_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE tbl_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_unit VARCHAR(20),
    item_status INT NOT NULL DEFAULT 1,
    price DOUBLE DEFAULT NULL,
    cat_id INT,
    supplier_id INT,
    created_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cat_id) REFERENCES tbl_categories(cat_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);


CREATE TABLE tbl_units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_name VARCHAR(50) NOT NULL,
    status INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Modify tbl_items to use unit_id instead of item_unit
ALTER TABLE tbl_items 
    DROP COLUMN item_unit,
    ADD COLUMN unit_id INT AFTER item_name,
    ADD CONSTRAINT fk_item_unit FOREIGN KEY (unit_id) REFERENCES tbl_units(unit_id);



 ALTER TABLE tbl_items
ADD COLUMN min_price DOUBLE DEFAULT 0 AFTER price;
 CREATE TABLE `damaged` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `qty` float NOT NULL,
  `message` varchar(200) NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
