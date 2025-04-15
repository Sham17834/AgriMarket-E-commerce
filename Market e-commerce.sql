-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: localhost    Database: agrimarketdb
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `cart_item_id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  KEY `cart_items_ibfk_1` (`cart_id`),
  KEY `cart_items_ibfk_2` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `knowledge_base`
--

DROP TABLE IF EXISTS `knowledge_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_base` (
  `article_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` enum('crops','livestock','technology','market_trends') NOT NULL,
  `author_id` int NOT NULL,
  `published_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `view_count` int NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `image_url` json DEFAULT NULL,
  PRIMARY KEY (`article_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `knowledge_base_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_base`
--

LOCK TABLES `knowledge_base` WRITE;
/*!40000 ALTER TABLE `knowledge_base` DISABLE KEYS */;
INSERT INTO `knowledge_base` VALUES (1,'Sustainable Crop Rotation Techniques','Crop rotation is a time-tested agricultural practice that improves soil health and reduces pest problems. The basic principle involves growing different types of crops in sequential seasons on the same land. A typical 4-year rotation might include: 1) Corn (heavy nitrogen user), 2) Soybeans (nitrogen fixer), 3) Wheat (small grain), and 4) Cover crops like clover. Benefits include improved soil fertility, reduced erosion, and natural pest control without excessive chemical use.','crops',1,'2024-03-15 09:30:00','2025-04-01 18:07:07',245,1,'[\"https://plus.unsplash.com/premium_photo-1731356517948-db579c112cf2?q=80&w=1933&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(2,'Organic Poultry Farming Best Practices','Organic poultry farming requires adherence to strict standards including: 1) 100% organic feed free from antibiotics and GMOs, 2) Access to outdoor space (minimum 2.5 acres per 1,000 birds), 3) Natural light and ventilation in housing, 4) Prohibition of forced molting. Key challenges include higher feed costs (30-50% more than conventional) and predator control. Successful organic producers emphasize strong biosecurity measures and rotational grazing patterns to maintain flock health.','livestock',2,'2024-02-28 14:15:00','2025-04-01 18:07:07',178,1,'[\"https://images.unsplash.com/photo-1513258419489-57f9e66da32b?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(3,'Precision Agriculture Technologies for Small Farms','Even small farms can benefit from precision ag tech: 1) Soil sensors ($200-500) monitor moisture/nutrients in real-time, 2) GPS-guided equipment (available as retrofit kits) reduces overlap in planting/spraying, 3) Drone imagery ($100-300/flight) identifies pest/disease hotspots, 4) Farm management software tracks inputs/yields. Case studies show 15-30% input cost reduction and 10-20% yield increases. Start with one technology and scale up as you see ROI.','technology',3,'2024-01-10 10:00:00','2025-04-01 18:07:07',312,0,'[\"https://images.unsplash.com/photo-1475948164756-9a56289068fb?q=80&w=2020&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(4,'2024 Global Grain Market Outlook','Key trends shaping grain markets: 1) Wheat prices volatile due to Black Sea supply uncertainties (projected $6.50-$8.50/bu), 2) Corn demand rising for ethanol (+7% YOY), 3) Soybean crush capacity expanding in Midwest, 4) Climate disruptions causing regional shortages. Storage strategies: Sell 40% at harvest, store 30% for spring rally, forward contract 30%. Monitor USDA WASDE reports on 12th of each month for updates.','market_trends',1,'2024-03-01 08:00:00','2024-03-20 13:10:00',421,1,'[\"https://images.unsplash.com/photo-1451187580459-43490279c0fa\"]'),(5,'Regenerative Grazing Methods','Regenerative grazing mimics natural herd movements: 1) High stock density (100+ animals/acre) for short durations (1-3 days), 2) Long rest periods (60-90 days) for pasture recovery, 3) Multi-species grazing (cattle+sheep+poultry) for pest control. Results from 5-year study: Soil organic matter increased from 2.1% to 3.8%, forage production doubled, and vet costs reduced by 40%. Start with small paddocks and monitor grass height (move at 4\" height, return at 8\").','livestock',2,'2023-11-20 11:30:00','2024-02-15 09:45:00',156,0,'[\"https://images.unsplash.com/photo-1500595046743-cd271d694d30\"]');
/*!40000 ALTER TABLE `knowledge_base` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `notification_type` enum('order','promotion','system','stock') COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(6,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) GENERATED ALWAYS AS ((`quantity` * (`unit_price` - `discount_amount`))) STORED,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(6,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(6,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('credit_card','bank_transfer','mobile_payment') COLLATE utf8mb4_general_ci NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','delivered','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `shipping_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `billing_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_notes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1001,3,'2025-04-04 14:00:00',24.95,5.00,1.25,'credit_card','completed','delivered','123 Main St, Farmville, CA','123 Main St, Farmville, CA',NULL),(1002,4,'2025-04-06 10:30:00',19.98,5.00,1.00,'bank_transfer','completed','delivered','456 Oak Ave, Greenfield, NY','456 Oak Ave, Greenfield, NY',NULL),(1003,5,'2025-03-27 09:15:00',32.50,7.50,1.63,'credit_card','completed','delivered','789 Pine Rd, Harvestville, TX','789 Pine Rd, Harvestville, TX',NULL),(1005,7,'2025-04-09 11:45:00',28.75,5.00,1.44,'mobile_payment','completed','delivered','321 Elm Blvd, Orchard City, WA','321 Elm Blvd, Orchard City, WA',NULL),(1007,9,'2025-04-14 08:20:00',24.95,5.00,1.25,'credit_card','completed','shipped','654 Maple Ln, Groveside, OR','654 Maple Ln, Groveside, OR',NULL),(1008,10,'2025-04-17 13:10:00',35.20,7.50,1.76,'credit_card','completed','processing','987 Cedar St, Farmdale, VT','987 Cedar St, Farmdale, VT',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `parent_category_id` int DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `parent_category_id` (`parent_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'Livestock','Animals and animal products',NULL),(2,'Crops','Plant-based agricultural products',NULL),(3,'Forestry','Tree-derived products',NULL),(4,'Dairy','Milk and milk products',1),(5,'Poultry','Chickens, ducks, and eggs',1),(6,'Grains','Wheat, corn, rice, etc.',2),(7,'Nuts','Almonds, walnuts, etc.',3);
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `order_id` int DEFAULT NULL COMMENT 'Verify purchase',
  `rating` tinyint(1) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Moderation flag',
  PRIMARY KEY (`review_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `product_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,1,3,1001,5,'Absolutely delicious eggs!','The yolks are so rich and vibrant orange. You can really taste the difference from commercial eggs. My family loves them!','2025-04-05 09:15:00',1),(2,1,4,1002,4,'Great farm-fresh eggs','Consistent quality and excellent taste. Only giving 4 stars because one egg was cracked in delivery last time.','2025-04-07 14:30:00',1),(3,2,5,1003,5,'Perfect for artisan bread','This wheat flour has transformed my sourdough baking. The gluten development is amazing and the flavor is nutty and complex.','2025-03-28 11:20:00',1),(4,2,6,NULL,3,'Good but pricey','Quality is excellent but the price is about 20% higher than my local co-op. Still convenient for delivery though.','2025-04-02 16:45:00',0),(5,3,7,1005,5,'Best almonds I\'ve ever had','So fresh and flavorful! I use them for almond milk and the difference is night and day compared to store-bought.','2025-04-10 08:15:00',1),(6,3,8,NULL,2,'Disappointing packaging','Almonds tasted good but the bag arrived torn with about 1/4 spilled in the box. Need better packaging for shipping.','2025-04-12 13:50:00',0),(7,1,9,1007,5,'Eggcellent quality!','These eggs make the fluffiest omelets and richest custards. Worth every penny for the quality.','2025-04-15 10:05:00',1),(8,3,10,1008,4,'Great for snacking','Perfect for my keto diet. Would give 5 stars if they offered a larger bulk discount option.','2025-04-18 17:25:00',1);
/*!40000 ALTER TABLE `product_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `minimum_order_quantity` int NOT NULL DEFAULT '1',
  `packaging_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `is_organic` tinyint(1) NOT NULL DEFAULT '0',
  `harvest_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `category_id` (`category_id`),
  FULLTEXT KEY `product_search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,3,'Organic Free-Range Eggs',5,'Certified organic eggs from pasture-raised hens',12.00,9.60,50,1,'Dozen',NULL,1,NULL,'2025-04-11 22:38:56',1,'[\"https://plus.unsplash.com/premium_photo-1676409351533-58b63e3cbce3?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\",\"https://public.readdy.ai/ai/img_res/eggs_organic_carton.jpg\"]','2025-03-30 10:59:24'),(2,5,'Whole Grain Wheat',6,'High-protein wheat for baking',6.00,5.10,200,1,'5kg bag',NULL,0,NULL,'2025-04-11 22:38:56',1,'[\"https://images.unsplash.com/photo-1537200275355-4f0c0714f777?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(3,4,'California Almonds',7,'Raw, unsalted almonds',25.00,18.75,75,1,'1kg bag',NULL,1,NULL,'2025-04-11 22:38:56',1,'[\"https://images.unsplash.com/photo-1602948750761-97ea79ee42ec?q=80&w=2080&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(4,3,'Fresh Goat Milk',4,'Organic goat milk from free-range goats',8.00,6.40,30,1,'Gallon',NULL,1,NULL,'2025-04-11 22:38:56',1,'[\"https://plus.unsplash.com/premium_photo-1695166780662-b9b168dc65af?q=80&w=1976&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00'),(5,5,'Organic Cornmeal',6,'Stone-ground organic cornmeal',5.00,4.50,150,1,'2kg bag',NULL,1,NULL,'2025-04-11 22:38:56',1,'[\"https://plus.unsplash.com/premium_photo-1725635594762-ef1badbede67?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shopping_carts`
--

DROP TABLE IF EXISTS `shopping_carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shopping_carts` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shopping_carts`
--

LOCK TABLES `shopping_carts` WRITE;
/*!40000 ALTER TABLE `shopping_carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shopping_carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Use password_hash() in PHP',
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','vendor','customer','staff') COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_otp` varchar(6) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin1','$2y$10$hashed_admin1','admin@agrimarket.test','admin','016-1234567',NULL,NULL),(2,'farmer_john','$2y$10$hashed_farmer1','john@greenfarms.test','vendor','017-2345678',NULL,NULL),(3,'customer_mary','$2y$10$hashed_customer1','mary@example.com','vendor','012-3456789',NULL,NULL),(4,'john_doe','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','john@example.com','vendor','013-4567890',NULL,NULL),(5,'jane_smith','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','jane@example.com','customer','014-5678901',NULL,NULL),(6,'mike_jones','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','mike@example.com','customer','015-6789012',NULL,NULL),(7,'sarah_williams','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','sarah@example.com','vendor','018-7890123',NULL,NULL),(8,'david_brown','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','david@example.com','customer','019-8901234',NULL,NULL),(9,'emily_davis','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','emily@example.com','customer','011-9012345',NULL,NULL),(10,'robert_wilson','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','robert@example.com','customer','010-0123456',NULL,NULL),(14,'soon _yong','$2y$10$sumgF32AAjqXCV95S0yvQuQ3V2ZHn3a9ikbWn5wnmyOp4VSB5rf2S','yong178344@gmail.com','customer','016-7239840','416167','2025-04-15 20:07:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_reviews`
--

DROP TABLE IF EXISTS `vendor_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_reviews` (
  `vendor_review_id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_review_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `vendor_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_reviews`
--

LOCK TABLES `vendor_reviews` WRITE;
/*!40000 ALTER TABLE `vendor_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `vendor_id` int NOT NULL,
  `business_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `business_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `verified_status` tinyint(1) NOT NULL DEFAULT '0',
  `verification_documents` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subscription_level` enum('basic','premium','enterprise') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'basic',
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (2,'Green Farms Co-op','123 Farm Road, Agricultural Zone',1,NULL,'basic'),(3,'Sunny Orchard Farms','456 Orchard Lane, Rural County',1,NULL,'basic'),(4,'Evergreen Timber Co.','789 Forest Drive, Woodland Area',0,'[\"/uploads/docs/evergreen_license.pdf\"]','premium'),(5,'Golden Grain Collective','321 Wheat Street, Plains Region',1,NULL,'basic');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-15 20:16:16
