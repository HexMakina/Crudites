-- crudites / d&ujB%Cf}!ze>mM-U?n.8W
-- Create the test database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `crudites`;
USE `crudites`;

-- Create a table containing each SQL data type (all nullable)
CREATE TABLE `data_types_table` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    
    -- Numeric types
    `tinyint` TINYINT NULL,
    `smallint` SMALLINT NULL,
    `mediumint` MEDIUMINT NULL,
    `int` INT NULL,
    `bigint` BIGINT NULL,
    `float` FLOAT NULL,
    `double` DOUBLE NULL,
    `decimal` DECIMAL(10, 2) NULL,
    `numeric` NUMERIC(10, 2) NULL,
    
    -- String types
    `char` CHAR(10) NULL,
    `varchar` VARCHAR(255) NULL,
    `text` TEXT NULL,
    `tinytext` TINYTEXT NULL,
    `mediumtext` MEDIUMTEXT NULL,
    `longtext` LONGTEXT NULL,
    `blob` BLOB NULL,
    `tinyblob` TINYBLOB NULL,
    `mediumblob` MEDIUMBLOB NULL,
    `longblob` LONGBLOB NULL,
    
    -- Date and Time types
    `date` DATE NULL,
    `datetime` DATETIME NULL,
    `timestamp` TIMESTAMP NULL,
    `time` TIME NULL,
    `year` YEAR NULL,
    
    -- Other types
    `enum_example` ENUM('value1', 'value2', 'value3') NULL,
    `set_example` SET('a', 'b', 'c') NULL,
    `json_example` JSON NULL,
    `bit_example` BIT(1) NULL,
    
    -- Spatial types
    `geometry_example` GEOMETRY NULL,
    `point_example` POINT NULL,
    `linestring_example` LINESTRING NULL,
    `polygon_example` POLYGON NULL
);

-- Insert some test data into `data_types_table`
INSERT INTO `data_types_table` (
    `tinyint`, `smallint`, `mediumint`, `int`, `bigint`, `float`, `double`, 
    `decimal`, `numeric`, `char`, `varchar`, `text`, `tinytext`, `mediumtext`, 
    `longtext`, `blob`, `tinyblob`, `mediumblob`, `longblob`, `date`, `datetime`, 
    `timestamp`, `time`, `year`, `enum_example`, `set_example`, `json_example`, 
    `bit_example`, `geometry_example`, `point_example`, `linestring_example`, `polygon_example`
) VALUES 
(
    1, 100, 5000, 100000, 1000000000, 3.14, 3.1415926535, 123.45, 678.90, 'A', 'Test String', 
    'This is a long text', 'Tiny Text', 'Medium Text', 'Long Text Example', 
    'binary data', 'tiny binary', 'medium binary', 'long binary', '2024-01-01', '2024-01-01 12:00:00',
    '2024-01-01 12:00:00', '12:30:00', 2024, 'value1', 'a,b,c', '{"key": "value"}', b'1',
    ST_GeomFromText('POINT(1 1)'), ST_GeomFromText('POINT(2 2)'), ST_GeomFromText('LINESTRING(0 0, 1 1)'), ST_GeomFromText('POLYGON((0 0, 1 1, 1 0, 0 0))')
);


-- Create `users` table with some columns
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_unique` (`username`),
    UNIQUE KEY `email_unique` (`email`)
);

-- Create `orders` table with some columns
CREATE TABLE `orders` (
    `order_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`order_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE NO ACTION ON UPDATE CASCADE
);

-- Create `products` table with some columns
CREATE TABLE `products` (
    `product_id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `stock_quantity` INT DEFAULT 0,
    PRIMARY KEY (`product_id`)
);

-- Create `order_items` table with foreign key references to `orders` and `products`
CREATE TABLE `order_items` (
    `order_item_id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price_at_purchase` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`order_item_id`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Insert test data into `users`
INSERT INTO `users` (`username`, `email`) VALUES
('john_doe', 'john@example.com'),
('alice_smith', 'alice@example.com'),
('bob_jones', 'bob@example.com');

-- Insert test data into `orders`
INSERT INTO `orders` (`user_id`, `amount`, `status`) VALUES
(1, 100.50, 'completed'),
(2, 50.75, 'pending'),
(3, 30.00, 'cancelled');

-- Insert test data into `products`
INSERT INTO `products` (`name`, `price`, `category`, `stock_quantity`) VALUES
('Product A', 20.00, 'Category 1', 100),
('Product B', 15.00, 'Category 2', 50),
('Product C', 30.00, 'Category 1', 200);

-- Insert test data into `order_items`
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 1, 2, 20.00),
(1, 3, 1, 30.00),
(2, 2, 3, 15.00),
(3, 1, 1, 20.00);

-- Add a nullable column to `orders` to test the nullable behavior
ALTER TABLE `orders` ADD `is_paid` BOOLEAN DEFAULT NULL;


-- Create another table to test ENUM data types and constraints
CREATE TABLE `product_reviews` (
    `review_id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `review` TEXT,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`review_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
);

-- Insert some test data into `product_reviews`
INSERT INTO `product_reviews` (`product_id`, `rating`, `review`) VALUES
(1, 5, 'Excellent product, highly recommend!'),
(2, 3, 'Good product, but could be better.'),
(3, 1, 'Not satisfied, it broke after a week.');
