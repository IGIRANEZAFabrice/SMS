
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
CREATE TABLE tbl_categories (
    cat_id INT AUTO_INCREMENT PRIMARY KEY,
    cat_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================
-- ITEMS
-- ================================
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
