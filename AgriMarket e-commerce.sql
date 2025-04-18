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
  `quantity` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts` (`cart_id`),
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
INSERT INTO `knowledge_base` VALUES (1,'Sustainable Crop Rotation Techniques','Crop rotation is a time-tested agricultural practice that improves soil health, reduces pest problems, and enhances crop yields...','crops',1,'2024-03-15 09:30:00','2025-04-18 19:06:04',250,1,'[\"https://plus.unsplash.com/premium_photo-1731356517948-db579c112cf2?q=80&w=1933&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(2,'Organic Poultry Farming Best Practices','Organic poultry farming requires adherence to strict standards to ensure the health of the Jamaicas...','livestock',2,'2024-02-28 14:15:00','2025-04-17 23:28:37',178,1,'[\"https://images.unsplash.com/photo-1513258419489-57f9e66da32b?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(3,'Precision Agriculture Technologies for Small Farms','Even small farms can benefit from precision agriculture technologies, which allow farmers to optimize resources...','technology',3,'2024-01-10 10:00:00','2025-04-17 23:28:37',312,0,'[\"https://images.unsplash.com/photo-1475948164756-9a56289068fb?q=80&w=2020&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]'),(4,'2024 Global Grain Market Outlook','The 2024 global grain market is being shaped by several key trends that farmers, traders, and policymakers need to understand...','market_trends',1,'2024-03-01 08:00:00','2025-04-17 23:28:37',421,1,'[\"https://images.unsplash.com/photo-1451187580459-43490279c0fa\"]'),(5,'Regenerative Grazing Methods','Regenerative grazing mimics the natural movements of wild herds to improve soil health, increase biodiversity...','livestock',2,'2023-11-20 11:30:00','2025-04-17 23:36:35',157,0,'[\"https://images.unsplash.com/photo-1500595046743-cd271d694d30\"]');
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
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notification_type` enum('order','promotion','system','stock') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
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
  `product_name` varchar(100) NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(6,2) DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  `related_entity_type` varchar(255) DEFAULT 'product',
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,4,'Fresh Goat Milk',2,6.40,3.20,12.80,'product'),(2,1,12,'Fresh Beef Cuts',1,28.00,7.00,28.00,'product');
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
  `order_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(6,2) NOT NULL,
  `tax_amount` decimal(6,2) NOT NULL,
  `payment_method` enum('credit_card','bank_transfer','mobile_payment','cod') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `billing_address` varchar(255) NOT NULL,
  `customer_notes` text,
  `product_name` varchar(255) DEFAULT NULL,
  `related_entity_type` varchar(255) DEFAULT 'customer_order',
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,14,'2025-04-19 04:27:05',49.24,5.99,2.45,'cod','pending','cancelled','dfdfdf, fddf, dfdf fddf','dfdfdf, fddf, dfdf fddf','','Fresh Goat Milk and 1 more','customer_order');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_views`
--

DROP TABLE IF EXISTS `page_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_views` (
  `view_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `view_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`view_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `page_views_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `page_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_views`
--

LOCK TABLES `page_views` WRITE;
/*!40000 ALTER TABLE `page_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `parent_category_id` int DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `parent_category_id` (`parent_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'Livestock','Animals and animal products',NULL),(2,'Crops','Plant-based agricultural products including grains, soybeans, hay, vegetables, fruits, and tubers',NULL),(3,'Forestry','Tree-derived products',NULL),(4,'Dairy','Milk and milk products',1),(5,'Poultry','Chickens, ducks, and eggs',1),(7,'Nuts','Almonds, walnuts, etc.',3),(8,'Fish Farming','Farm-raised aquatic products',NULL),(9,'Honey Products','Beekeeping and honey-derived products',NULL);
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
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Moderation flag',
  PRIMARY KEY (`review_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `product_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,1,3,1001,5,'Absolutely delicious eggs!','The yolks are so rich and vibrant orange. You can really taste the difference from commercial eggs. My family loves them!','2025-04-05 09:15:00',1),(2,1,4,1002,4,'Great farm-fresh eggs','Consistent quality and excellent taste. Only giving 4 stars because one egg was cracked in delivery last time.','2025-04-07 14:30:00',1),(3,2,5,1003,5,'Perfect for artisan bread','This wheat flour has transformed my sourdough baking. The gluten development is amazing and the flavor is nutty and complex.','2025-03-28 11:20:00',1),(5,3,7,1005,5,'Best almonds I\'ve ever had','So fresh and flavorful! I use them for almond milk and the difference is night and day compared to store-bought.','2025-04-10 08:15:00',1),(7,1,9,1007,5,'Eggcellent quality!','These eggs make the fluffiest omelets and richest custards. Worth every penny for the quality.','2025-04-15 10:05:00',1),(8,3,10,1008,4,'Great for snacking','Perfect for my keto diet. Would give 5 stars if they offered a larger bulk discount option.','2025-04-18 17:25:00',1),(9,1,14,NULL,5,'ffdf','fdfd','2025-04-16 20:06:01',1),(10,12,14,NULL,5,'hahaha','saaas','2025-04-17 02:04:40',1);
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
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT '0',
  `minimum_order_quantity` int NOT NULL DEFAULT '1',
  `packaging_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `is_organic` tinyint(1) NOT NULL DEFAULT '0',
  `harvest_date` date DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `category_id` (`category_id`),
  FULLTEXT KEY `product_search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,3,'Organic Free-Range Eggs',5,'Certified organic eggs from pasture-raised hens enjoying 108 sq ft of roaming space each. Rich in omega-3s (200mg per egg) with vibrant orange yolks from their natural diet. Hand-collected daily and packaged in recycled pulp cartons.',12.00,9.60,47,1,'Dozen',0.60,1,'2025-02-01','2025-04-18 20:28:50',1,'[\"https://plus.unsplash.com/premium_photo-1676409351533-58b63e3cbce3?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\",\"https://public.readdy.ai/ai/img_res/eggs_organic_carton.jpg\"]','2025-03-30 10:59:24'),(2,5,'Whole Grain Wheat',2,'Premium whole grain wheat stone-ground to preserve all nutrients. High in protein (13g per serving) and fiber, perfect for artisan breads. Sourced from sustainable family farms using traditional growing methods.',6.00,5.00,200,1,'5kg bag',5.00,0,'2025-02-01','2025-04-16 21:29:36',1,'[\"https://images.unsplash.com/photo-1537200275355-4f0c0714f777?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(3,4,'California Almonds',6,'Premium California almonds, raw and unsalted. These jumbo kernels contain 6g plant protein and 37% daily vitamin E per ounce. Dry-roasted at low temps to preserve nutrients. Ideal for snacks or homemade almond milk.',25.00,18.75,75,1,'1kg bag',1.00,1,'2025-02-01','2025-04-16 20:14:42',1,'[\"https://images.unsplash.com/photo-1602948750761-97ea79ee42ec?q=80&w=2080&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(4,3,'Fresh Goat Milk',4,'Fresh organic goat milk from free-range goats grazing on diverse pastures. Naturally homogenized with smaller fat globules for easier digestion. Rich in calcium and probiotics, bottled within 2 hours of milking.',8.00,6.40,9,1,'Gallon',1.00,1,'2025-02-01','2025-04-19 04:27:05',1,'[\"https://images.unsplash.com/photo-1704369291921-a1abffc09768?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00'),(5,5,'Organic Cornmeal',2,'Stone-ground organic cornmeal from non-GMO heirloom varieties. Medium grind perfect for cornbread and polenta. Packed with antioxidants and fiber. Grown using regenerative farming practices.',5.00,NULL,150,1,'2kg bag',NULL,1,'2025-02-01','2025-04-16 20:14:42',1,'[\"https://plus.unsplash.com/premium_photo-1725635594762-ef1badbede67?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00'),(6,2,'Fresh Tilapia Fish',8,'Fresh, mild-flavored tilapia farmed in sustainable recirculating systems. High in lean protein (26g per fillet) with low mercury. Harvested at peak freshness and flash-frozen to preserve quality.',8.50,NULL,80,5,'box',5.00,0,'2025-04-01','2025-04-19 04:29:35',1,'[\"https://images.unsplash.com/photo-1498654200943-1088dd4438ae?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(7,4,'Pure Forest Honey',9,'Raw forest honey harvested from wild beehives in protected woodlands. Unprocessed and unfiltered, containing natural pollen and enzymes. Distinct floral notes vary by season. Packed in glass jars to preserve purity.',12.00,NULL,50,1,'jar',0.50,1,'2025-03-15','2025-04-16 18:54:19',1,'[\"https://plus.unsplash.com/premium_photo-1726704133644-bd521a727cf8?q=80&w=2017&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(8,1,'Fresh Spinach Bunch',2,'Fresh, pesticide-free spinach bunches with deep green leaves. Packed with iron (15% DV per serving), vitamin K, and antioxidants. Harvested at peak freshness and carefully bundled to prevent damage.',3.00,NULL,200,2,'bundle',0.30,1,'2025-04-10','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1694989032963-d7615dc91965?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(9,2,'Sweet Mangoes',2,'Ripe, sweet mangoes with juicy orange flesh. Each fruit provides 100% of your daily vitamin C needs. Tree-ripened for maximum flavor and carefully packed to prevent bruising during transport.',5.00,NULL,147,3,'crate',3.00,1,'2025-04-05','2025-04-18 14:57:01',1,'[\"https://images.unsplash.com/photo-1685478677113-8c4a58503230?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(10,3,'Yam Tubers',2,'Locally grown white yams with creamy texture when cooked. Excellent source of complex carbs and fiber. Grown using traditional methods without synthetic fertilizers. Each tuber hand-selected for quality.',6.00,NULL,120,1,'sack',10.00,0,'2025-04-03','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1663763025643-42569bdc41b7?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(11,5,'Organic Carrots',2,'Certified organic carrots with exceptional sweetness. Rich in beta-carotene (428% DV per serving) and vitamin A. Grown in mineral-rich soil and hand-harvested at peak sweetness.',4.50,NULL,180,2,'bag',1.00,1,'2025-04-08','2025-04-16 18:54:19',1,'[\"https://plus.unsplash.com/premium_photo-1664277022334-de9ab23c0ee2?q=80&w=2047&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(12,4,'Fresh Beef Cuts',1,'Premium grass-fed beef cuts from humanely raised cattle. Marbled for perfect flavor, rich in CLA and omega-3s. Dry-aged for 21 days to enhance tenderness. Butchered to exacting standards.',35.00,28.00,9,1,'Vacuum Pack',1.00,0,'2025-04-12','2025-04-19 04:27:05',1,'[\"https://images.unsplash.com/photo-1705557101766-0d550be69071?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 09:00:00'),(13,3,'Honeycomb Pieces',7,'Natural honeycomb sections filled with raw, unfiltered honey. Contains propolis and bee pollen for added benefits. Perfect for cheese boards or as a natural sweetener. Cut from hives using traditional methods.',18.00,15.00,30,1,'Jar',0.40,1,'2025-03-12','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1642067958024-1a2d9f836920?q=80&w=2094&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 09:00:00'),(14,1,'Wild Forest Mushrooms',3,'Wild-foraged mushrooms sustainably harvested from old-growth forests. Varieties may include chanterelles, morels, and porcini depending on season. Carefully cleaned and packaged to preserve delicate textures.',20.00,16.00,40,1,'Pack',0.30,1,'2025-04-10','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1634326598999-1ac816b82223?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00'),(15,2,'Maple Syrup',3,'Pure maple syrup tapped from mature sugar maples. Grade A amber color with robust flavor. Contains 54 beneficial compounds. Boiled slowly in small batches to achieve perfect consistency.',25.00,NULL,30,1,'Bottle',0.50,1,'2025-03-15','2025-04-18 15:15:50',1,'[\"https://images.unsplash.com/photo-1552314971-d2feb3513949?q=80&w=1922&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00'),(16,3,'Pine Nuts',3,'Wild-harvested pine nuts from sustainable sources. Buttery flavor perfect for pesto or salads. Rich in healthy fats (19g per ounce) and magnesium. Hand-shelled and carefully sorted for quality.',30.00,24.00,25,1,'Bag',0.20,1,'2025-03-20','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1679740972074-b1ba3049f173?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_logs`
--

DROP TABLE IF EXISTS `search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` int DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `product_id` (`product_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `search_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `search_logs_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`),
  CONSTRAINT `search_logs_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_logs`
--

LOCK TABLES `search_logs` WRITE;
/*!40000 ALTER TABLE `search_logs` DISABLE KEYS */;
INSERT INTO `search_logs` VALUES (1,'XSX',NULL,NULL,1,'2025-04-17 04:00:14'),(2,'XSX',NULL,NULL,1,'2025-04-17 04:00:32'),(3,'ss',NULL,NULL,NULL,'2025-04-17 09:36:56'),(4,'dsds',NULL,NULL,14,'2025-04-17 14:44:27'),(5,'egg',1,NULL,14,'2025-04-17 15:07:04'),(6,'egg',1,NULL,14,'2025-04-17 15:07:27'),(7,'egg',1,NULL,14,'2025-04-17 15:07:28'),(8,'egg',1,NULL,14,'2025-04-17 15:07:30'),(9,'fdf',NULL,NULL,14,'2025-04-17 15:26:12');
/*!40000 ALTER TABLE `search_logs` ENABLE KEYS */;
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
  `cart_data` text,
  `related_entity_type` varchar(255) DEFAULT 'customer_cart',
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `idx_customer_id` (`customer_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `shopping_carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shopping_carts`
--

LOCK TABLES `shopping_carts` WRITE;
/*!40000 ALTER TABLE `shopping_carts` DISABLE KEYS */;
INSERT INTO `shopping_carts` VALUES (27,14,'2025-04-19 04:26:41','2025-04-19 04:27:05','[]','customer_cart');
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
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Use password_hash() in PHP',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','vendor','customer','staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_otp` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin1','$2y$10$Bw0/U7IHaMor5pM3UwtYoem.krL/caxkQbA86XttLwftD3C4cIDg2','admin@agrimarket.test','admin','016-1234567',NULL,NULL),(2,'farmer_john','$2y$10$rajnHudllQWC.0R5kE02B.PKh6NvvKK73KoSBZ/03CPE7JMG5cVGS','john@greenfarms.test','vendor','017-2345678',NULL,NULL),(3,'customer_mary','$2y$10$hashed_customer1','mary@example.com','vendor','012-3456789',NULL,NULL),(4,'john_doe','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','john@example.com','vendor','013-4567890',NULL,NULL),(5,'jane_smith','$2y$10$6rS13bgB.zY2c/nS.bDmQOdDzk/Sh2SkdDE1j8WBhqkuKx/6EudgO','jane@example.com','staff','014-5678901',NULL,NULL),(6,'mike_jones','$2y$10$bfkX9NkfV1AJ.oZJ7dY0iuJAhOqmiOX.133PRYT/k4EsiqB9orToa','mike@example.com','staff','015-6789012',NULL,NULL),(7,'sarah_williams','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','sarah@example.com','vendor','018-7890123',NULL,NULL),(8,'david_brown','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','david@example.com','customer','019-8901234',NULL,NULL),(9,'emily_davis','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','emily@example.com','customer','011-9012345',NULL,NULL),(10,'robert_wilson','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','robert@example.com','customer','010-0123456',NULL,NULL),(14,'soon _yong','$2y$10$APcIxsv30vU22HUi.QDMtuIpDtWSvmP6yGUI.naA7KciM3Fv2Kv4a','yong178344@gmail.com','customer','016-7239840','416167','2025-04-15 20:07:43'),(15,'admin2','$2y$10$YOUR_GENERATED_HASH_HERE','admin2@agrimarket.test','admin','016-12345678',NULL,NULL);
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
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `review_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_review_id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `vendor_reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_reviews`
--

LOCK TABLES `vendor_reviews` WRITE;
/*!40000 ALTER TABLE `vendor_reviews` DISABLE KEYS */;
INSERT INTO `vendor_reviews` VALUES (1,3,14,5,'fdfdfd','2025-04-18 20:17:01'),(2,3,14,5,'very good','2025-04-18 20:26:27');
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
  `business_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `business_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `verified_status` tinyint(1) NOT NULL DEFAULT '0',
  `verification_documents` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subscription_level` enum('basic','premium','enterprise') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'basic',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (2,'Green Farms Co-op','123 Farm Road, Agricultural Zone',1,NULL,'basic','Green Farms Co-op is a community-driven cooperative dedicated to sustainable farming...'),(3,'Sunny Orchard Farms','456 Orchard Lane, Rural County',1,NULL,'basic','Sunny Orchard Farms is a family-owned orchard located in Rural County...'),(4,'Evergreen Timber Co.','789 Forest Drive, Woodland Area',0,'[\"/uploads/docs/evergreen_license.pdf\"]','premium','Evergreen Timber Co., based in the Woodland Area, specializes in sustainably sourced timber...');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `wishlist_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_wishlist` (`customer_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-19  4:35:39
