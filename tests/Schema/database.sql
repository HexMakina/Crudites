

CREATE TABLE `data_types_table` (
  `id` int NOT NULL,
  `tinyint` tinyint DEFAULT NULL,
  `smallint` smallint DEFAULT NULL,
  `mediumint` mediumint DEFAULT NULL,
  `int` int DEFAULT NULL,
  `bigint` bigint DEFAULT NULL,
  `float` float DEFAULT NULL,
  `double` double DEFAULT NULL,
  `decimal` decimal(10,2) DEFAULT NULL,
  `numeric` decimal(10,2) DEFAULT NULL,
  `char` char(10) DEFAULT NULL,
  `varchar` varchar(255) DEFAULT NULL,
  `text` text,
  `tinytext` tinytext,
  `mediumtext` mediumtext,
  `longtext` longtext,
  `blob` blob,
  `tinyblob` tinyblob,
  `mediumblob` mediumblob,
  `longblob` longblob,
  `date` date DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `time` time DEFAULT NULL,
  `year` year DEFAULT NULL,
  `enum_example` enum('value1','value2','value3') DEFAULT NULL,
  `set_example` set('a','b','c') DEFAULT NULL,
  `json_example` json DEFAULT NULL,
  `bit_example` bit(1) DEFAULT NULL,
  `geometry_example` geometry DEFAULT NULL,
  `point_example` point DEFAULT NULL,
  `linestring_example` linestring DEFAULT NULL,
  `polygon_example` polygon DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `data_types_table`
--

INSERT INTO `data_types_table` (`id`, `tinyint`, `smallint`, `mediumint`, `int`, `bigint`, `float`, `double`, `decimal`, `numeric`, `char`, `varchar`, `text`, `tinytext`, `mediumtext`, `longtext`, `blob`, `tinyblob`, `mediumblob`, `longblob`, `date`, `datetime`, `timestamp`, `time`, `year`, `enum_example`, `set_example`, `json_example`, `bit_example`, `geometry_example`, `point_example`, `linestring_example`, `polygon_example`) VALUES
(1, 1, 100, 5000, 100000, 1000000000, 3.14, 3.1415926535, '123.45', '678.90', 'A', 'Test String', 'This is a long text', 'Tiny Text', 'Medium Text', 'Long Text Example', 0x62696e6172792064617461, 0x74696e792062696e617279, 0x6d656469756d2062696e617279, 0x6c6f6e672062696e617279, '2024-01-01', '2024-01-01 12:00:00', '2024-01-01 11:00:00', '12:30:00', 2024, 'value1', 'a,b,c', '{\"key\": \"value\"}', b'1', 0x000000000101000000000000000000f03f000000000000f03f, 0x00000000010100000000000000000000400000000000000040, 0x0000000001020000000200000000000000000000000000000000000000000000000000f03f000000000000f03f, 0x000000000103000000010000000400000000000000000000000000000000000000000000000000f03f000000000000f03f000000000000f03f000000000000000000000000000000000000000000000000);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_paid` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `amount`, `status`, `created_at`, `is_paid`) VALUES
(1, 1, '100.50', 'completed', '2024-11-25 21:23:46', NULL),
(2, 2, '50.75', 'pending', '2024-11-25 21:23:46', NULL),
(3, 3, '30.00', 'cancelled', '2024-11-25 21:23:46', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 1, 1, 2, '20.00'),
(2, 1, 3, 1, '30.00'),
(3, 2, 2, 3, '15.00'),
(4, 3, 1, 1, '20.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) NOT NULL,
  `stock_quantity` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `price`, `category`, `stock_quantity`) VALUES
(1, 'Product A', '20.00', 'Category 1', 100),
(2, 'Product B', '15.00', 'Category 2', 50),
(3, 'Product C', '30.00', 'Category 1', 200);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int NOT NULL,
  `product_id` int NOT NULL,
  `rating` int NOT NULL,
  `review` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `product_id`, `rating`, `review`, `created_at`) VALUES
(1, 1, 5, 'Excellent product, highly recommend!', '2024-11-25 21:25:44'),
(2, 2, 3, 'Good product, but could be better.', '2024-11-25 21:25:44'),
(3, 3, 1, 'Not satisfied, it broke after a week.', '2024-11-25 21:25:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `created_at`) VALUES
(1, 'john_doe', 'john@example.com', '2024-11-25 21:23:46'),
(2, 'alice_smith', 'alice@example.com', '2024-11-25 21:23:46'),
(3, 'bob_jones', 'bob@example.com', '2024-11-25 21:23:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `data_types_table`
--
ALTER TABLE `data_types_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_unique` (`username`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `data_types_table`
--
ALTER TABLE `data_types_table`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;