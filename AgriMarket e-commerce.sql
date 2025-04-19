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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_reviews`
--

LOCK TABLES `product_reviews` WRITE;
/*!40000 ALTER TABLE `product_reviews` DISABLE KEYS */;
INSERT INTO `product_reviews` VALUES (1,1,8,1001,5,'Absolutely delicious eggs!','The yolks are so rich and vibrant orange. You can really taste the difference from commercial eggs. My family loves them!','2025-04-05 09:15:00',1),(2,1,8,1002,4,'Great farm-fresh eggs','Consistent quality and excellent taste. Only giving 4 stars because one egg was cracked in delivery last time.','2025-04-07 14:30:00',1),(3,2,9,1003,5,'Perfect for artisan bread','This wheat flour has transformed my sourdough baking. The gluten development is amazing and the flavor is nutty and complex.','2025-03-28 11:20:00',1),(5,3,9,1005,5,'Best almonds I\'ve ever had','So fresh and flavorful! I use them for almond milk and the difference is night and day compared to store-bought.','2025-04-10 08:15:00',1),(7,1,10,1007,5,'Eggcellent quality!','These eggs make the fluffiest omelets and richest custards. Worth every penny for the quality.','2025-04-15 10:05:00',1),(8,3,14,1008,4,'Great for snacking','Perfect for my keto diet. Would give 5 stars if they offered a larger bulk discount option.','2025-04-18 17:25:00',1),(12,4,11,1009,5,'Creamy and Fresh Goat Milk!','This organic goat milk is a game-changer! It’s so creamy and easy to digest compared to cow’s milk. I used it for smoothies and baking, and the flavor is rich without being overpowering. The fact that it’s bottled within 2 hours of milking really shows in the freshness. Packaged perfectly in a gallon jug with no leaks during delivery. Highly recommend for anyone looking for a healthier dairy option!','2025-04-10 10:30:00',1),(13,6,12,1010,4,'Tasty Tilapia Fillets','The fresh tilapia is mild and versatile, perfect for grilling or baking. I love that it’s sustainably farmed with low mercury, which gives me peace of mind. The fillets were well-packaged in a box, though one was slightly smaller than expected, so I gave 4 stars. High in protein and great for quick dinners. Will definitely order again for my family!','2025-04-12 14:45:00',1),(14,7,13,1011,5,'Pure Honey Perfection','This raw forest honey is absolutely divine! The floral notes are unique and add a special touch to my tea and desserts. I appreciate that it’s unfiltered and packed with natural pollen. The glass jar packaging keeps it fresh and eco-friendly. You can tell it’s harvested with care from wild beehives. Worth every penny for this quality!','2025-04-14 09:20:00',1),(15,8,14,1012,5,'Vibrant Spinach Bunches','These pesticide-free spinach bunches are so fresh and crisp! The leaves are deep green and packed with nutrients, making them perfect for salads and smoothies. I was impressed by how well they were bundled to avoid damage during shipping. Great value for the price, and my kids love it in their meals. Highly recommend for health-conscious shoppers!','2025-04-15 11:10:00',1),(16,9,15,1013,4,'Juicy Mangoes','These sweet mangoes are bursting with flavor and juiciness! They’re perfect for snacking or blending into smoothies, and the vitamin C boost is a bonus. The crate packaging ensured no bruising, though a couple of mangoes were slightly overripe, so I gave 4 stars. Still, the tree-ripened quality shines through. Will order again for summer recipes!','2025-04-16 16:25:00',1),(17,11,16,1014,5,'Sweet and Crunchy Carrots','These organic carrots are the sweetest I’ve ever tasted! The beta-carotene content makes them a great addition to my family’s diet, and they’re perfect for roasting or snacking raw. Grown in mineral-rich soil, you can taste the quality. The bag packaging kept them fresh, and delivery was flawless. A must-have for any kitchen!','2025-04-17 08:50:00',1),(18,12,17,1015,5,'Tender Grass-Fed Beef','The grass-fed beef cuts are exceptional! The marbling gives such rich flavor, and they’re so tender after cooking. I love that they’re high in omega-3s and dry-aged for 21 days. The vacuum pack kept them fresh during delivery. Perfect for a special dinner. I’ll be ordering more for my next barbecue!','2025-04-18 12:15:00',1),(19,15,18,1016,4,'Rich Maple Syrup','This pure maple syrup has a deep, robust flavor that elevates my pancakes and desserts. The amber color is beautiful, and I appreciate the small-batch boiling process. The bottle is sturdy, but I wish it came in a larger size for the price, so 4 stars. Still, the 54 beneficial compounds make it a healthy choice!','2025-04-18 15:30:00',1),(20,17,19,1017,5,'Fresh and Nutritious Broccoli','This organic broccoli is top-notch! The vibrant green heads are packed with vitamin C and fiber, making it a staple in my meal prep. Grown without pesticides, it tastes clean and fresh. The bundle packaging was secure, and the broccoli stayed crisp for days. Perfect for steaming or stir-fries. Highly recommend!','2025-04-19 09:45:00',1),(21,5,8,1021,5,'Perfect Organic Cornmeal!','This stone-ground organic cornmeal is fantastic for cornbread! The medium grind gives a great texture, and the flavor is rich and authentic. I love that it’s made from non-GMO heirloom varieties and grown with regenerative farming. The 2kg bag is perfect for my baking needs, and it arrived in great condition. Highly recommend for anyone who loves homemade baked goods!','2025-04-19 17:00:00',1),(22,10,8,1022,4,'Tasty Yams','These white yams have a creamy texture when boiled or roasted, perfect for stews. They’re a great source of fiber, and I appreciate the traditional growing methods. The sack packaging kept them fresh, though one tuber had a small blemish, so I gave 4 stars. Still, great quality for the price, and I’ll order again for family meals!','2025-04-19 18:30:00',1),(23,13,9,1023,5,'Delicious Honeycomb!','This raw honeycomb is a treat! The natural pollen and propolis add such depth to the flavor, and it’s perfect on cheese boards or with yogurt. The jar packaging is secure and eco-friendly. I love that it’s cut from hives traditionally. It’s a bit pricey, but the quality is unmatched. A must-try for honey lovers!','2025-04-19 19:15:00',1),(24,14,9,1024,4,'Fresh Wild Mushrooms','These wild-foraged mushrooms are a delight! I got a mix of chanterelles and porcini, and they added amazing flavor to my risotto. The delicate texture was preserved, thanks to careful packaging. I docked a star because the pack was smaller than expected for the price. Still, the quality is top-notch, and I’ll order again for special dishes!','2025-04-19 20:00:00',1),(25,16,10,1025,5,'Buttery Pine Nuts','These wild-harvested pine nuts are so buttery and flavorful! They’re perfect for pesto and salads, and the high magnesium content is a bonus. Hand-shelled and well-sorted, they arrived in great condition in a sturdy bag. The quality justifies the price. I’m thrilled with this purchase and will definitely reorder!','2025-04-19 21:30:00',1),(26,18,10,1026,5,'Tender Lamb Chops','These grass-fed lamb chops are incredible! The rich, tender flavor is perfect for grilling, and they’re packed with omega-3s. Dry-aged for 14 days, they melt in your mouth. The vacuum pack ensured freshness during delivery. A bit expensive, but worth it for a special meal. Highly recommend for meat lovers!','2025-04-19 22:15:00',1),(27,20,14,1027,4,'Fresh Rainbow Trout','The rainbow trout fillets are fresh and high in omega-3s, making them a healthy choice for dinners. The mild flavor is great for baking or pan-searing. The box packaging kept them frozen, but one fillet was slightly thinner, so I gave 4 stars. Still, sustainable aquaculture and great quality make this a solid buy!','2025-04-20 08:00:00',1),(28,21,14,1028,5,'Energizing Bee Pollen','This organic bee pollen is a fantastic addition to my smoothies! It’s packed with vitamins and gives me a natural energy boost. The glass jar keeps it fresh, and I love that it’s sourced from wildflower hives. The flavor is subtle and pleasant. Great value for the quality, and I’ll be ordering more!','2025-04-20 09:30:00',1),(29,22,16,1029,5,'Flavorful Free-Range Chicken','This organic free-range chicken is so tender and flavorful! Raised with plenty of space and a natural diet, you can taste the difference. The vacuum pack kept it fresh, and it was perfect for roasting. High in protein and worth the price for the quality. My family loved it, and I’ll order again!','2025-04-20 10:15:00',1),(30,23,16,1030,3,'Good Walnuts, But...','These wild-harvested walnuts are rich in omega-3s and great for baking. The flavor is fresh, but a few nuts were slightly bitter, which affected my batch of cookies, so I gave 3 stars. The 1kg bag is convenient, and the hand-shelled quality is noticeable. I hope the next batch is more consistent!','2025-04-20 11:00:00',1),(31,27,17,1031,5,'Rich Organic Duck Eggs','These organic duck eggs are amazing! The yolks are so rich and perfect for baking or gourmet dishes. I love that the ducks have access to natural ponds, which adds to the quality. The recycled carton packaging is sturdy, and all eggs arrived intact. A bit pricey, but worth it for the flavor!','2025-04-20 12:30:00',1),(32,30,17,1032,5,'Sharp and Creamy Cheddar','This organic cheddar cheese is sharp and creamy, with a bold flavor that’s perfect for sandwiches or melting. Aged for 12 months, it’s rich in calcium and tastes amazing. The eco-friendly wax packaging is a nice touch, and it arrived in perfect condition. Great value for the quality, and I’ll buy again!','2025-04-20 13:15:00',1),(33,1,8,1033,5,'Amazing Organic Eggs!','These organic free-range eggs are phenomenal! The vibrant orange yolks make my omelets and baked goods taste incredible. I love that the hens have 108 sq ft of space each, and the recycled pulp cartons are a great touch. Always fresh and well-packaged. A staple in my kitchen, highly recommend!','2025-04-20 14:00:00',1),(34,3,9,1034,4,'Flavorful Almonds','These California almonds are fresh and packed with flavor. The jumbo kernels are perfect for snacking or making almond milk. I appreciate the low-temp dry-roasting to preserve nutrients. Only gave 4 stars because the bag could be resealable for convenience. Still, great quality and will buy again!','2025-04-20 15:30:00',1),(35,11,10,1035,5,'Super Sweet Carrots!','These organic carrots are incredibly sweet and crunchy! Perfect for salads or roasting, and the beta-carotene boost is great for health. Grown in mineral-rich soil, you can taste the care put into them. The bag kept them fresh during delivery. My kids love them too!','2025-04-20 16:15:00',1),(36,24,14,1036,4,'Tasty Strawberries','These organic strawberries are juicy and full of flavor, perfect for smoothies or desserts. The vitamin C content is a bonus! The eco-friendly carton is great, but a couple of berries were slightly soft, so 4 stars. Still, they’re grown without pesticides, and I’ll order again!','2025-04-20 17:00:00',1),(37,26,16,1037,5,'Lovely Beeswax Candles','These organic beeswax candles are fantastic! They burn cleanly for hours and have a subtle honey scent that’s so calming. The recyclable packaging is a nice touch, and they arrived in perfect condition. Great for cozy evenings or gifting. Worth every penny!','2025-04-20 18:30:00',1),(38,28,17,1038,5,'Perfect Maple Sugar!','This organic maple sugar is a game-changer for baking and coffee! The rich caramel flavor is divine, and it’s packed with antioxidants. The resealable 500g bag is convenient, and the quality from sustainably tapped maples shines through. Highly recommend for natural sweetness!','2025-04-20 19:15:00',1),(39,29,22,1039,4,'Great Sweet Potatoes','These organic sweet potatoes are vibrant and naturally sweet, perfect for roasting or mashing. High in beta-carotene, they’re a healthy addition to my meals. The bag packaging was secure, but one potato had a small spot, so 4 stars. Still, great quality and value!','2025-04-20 20:00:00',1),(40,31,23,1040,5,'Delicate Quail Eggs','These organic quail eggs are a delight! Perfect for gourmet dishes or small appetizers, with rich yolks and high protein. The protective carton ensured no cracks during delivery. I love that the quails are free-range with a natural diet. A bit pricey but worth it!','2025-04-20 21:30:00',1),(41,1,24,1041,4,'Really Good Eggs','These free-range eggs are excellent, with bright orange yolks that make my baking stand out. The omega-3 content is a plus. One egg was slightly cracked in the carton, so I gave 4 stars. Still, the organic quality and sustainable packaging make them a great choice!','2025-04-20 22:15:00',1),(42,3,25,1042,5,'Top-Notch Almonds!','These raw California almonds are the best I’ve tried! The 6g of plant protein per ounce is great for my diet, and they’re perfect for homemade almond butter. The 1kg bag is well-packaged, and the freshness is unbeatable. Highly recommend for snacking or cooking!','2025-04-21 08:00:00',1),(43,11,8,1043,3,'Decent Carrots','These organic carrots are sweet and good for juicing, with high beta-carotene. However, a few were smaller than expected and one was damaged, so I gave 3 stars. The bag kept most fresh, and I appreciate the organic farming. I hope the next batch is more consistent!','2025-04-21 09:30:00',1),(44,24,9,1044,5,'Fantastic Strawberries!','These organic strawberries are absolutely delicious! So juicy and packed with flavor, they’re perfect for breakfast or desserts. The carton packaging is eco-friendly, and all berries arrived fresh. The fact that they’re grown without pesticides makes them even better. A must-buy!','2025-04-21 10:15:00',1),(45,26,10,1045,4,'Nice Candles','These beeswax candles burn beautifully and have a subtle honey aroma. They’re great for creating a cozy atmosphere. The pack was well-protected, but one candle was slightly misshapen, so 4 stars. Still, the organic quality and clean burn make them worth it!','2025-04-21 11:00:00',1),(46,28,14,1046,5,'Love This Maple Sugar!','This organic maple sugar adds such a rich flavor to my baking! It’s perfect for cookies and tea, and the antioxidants are a bonus. The 500g bag is easy to store, and the sustainable sourcing is impressive. Arrived in perfect condition, highly recommend!','2025-04-21 12:30:00',1),(47,29,16,1047,5,'Awesome Sweet Potatoes!','These organic sweet potatoes are fantastic! The flesh is so sweet and perfect for soups or fries. High in fiber and grown in regenerative soils, they’re both healthy and delicious. The bag was sturdy, and all potatoes were flawless. Will definitely reorder!','2025-04-21 13:15:00',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,3,'Organic Free-Range Eggs',5,'Certified organic eggs from pasture-raised hens enjoying 108 sq ft of roaming space each. Rich in omega-3s (200mg per egg) with vibrant orange yolks from their natural diet. Hand-collected daily and packaged in recycled pulp cartons.',12.00,9.60,47,1,'Dozen',0.60,1,'2025-02-01','2025-04-18 20:28:50',1,'[\"https://plus.unsplash.com/premium_photo-1676409351533-58b63e3cbce3?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\",\"https://public.readdy.ai/ai/img_res/eggs_organic_carton.jpg\"]','2025-03-30 10:59:24'),(2,3,'Whole Grain Wheat',2,'Premium whole grain wheat stone-ground to preserve all nutrients. High in protein (13g per serving) and fiber, perfect for artisan breads. Sourced from sustainable family farms using traditional growing methods.',6.00,5.00,200,1,'5kg bag',5.00,0,'2025-02-01','2025-04-19 18:06:24',1,'[\"https://images.unsplash.com/photo-1537200275355-4f0c0714f777?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(3,4,'California Almonds',6,'Premium California almonds, raw and unsalted. These jumbo kernels contain 6g plant protein and 37% daily vitamin E per ounce. Dry-roasted at low temps to preserve nutrients. Ideal for snacks or homemade almond milk.',25.00,18.75,75,1,'1kg bag',1.00,1,'2025-02-01','2025-04-16 20:14:42',1,'[\"https://images.unsplash.com/photo-1602948750761-97ea79ee42ec?q=80&w=2080&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-30 10:59:24'),(4,3,'Fresh Goat Milk',4,'Fresh organic goat milk from free-range goats grazing on diverse pastures. Naturally homogenized with smaller fat globules for easier digestion. Rich in calcium and probiotics, bottled within 2 hours of milking.',8.00,6.40,9,1,'Gallon',1.00,1,'2025-02-01','2025-04-19 04:27:05',1,'[\"https://images.unsplash.com/photo-1704369291921-a1abffc09768?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00'),(5,3,'Organic Cornmeal',2,'Stone-ground organic cornmeal from non-GMO heirloom varieties. Medium grind perfect for cornbread and polenta. Packed with antioxidants and fiber. Grown using regenerative farming practices.',5.00,NULL,150,1,'2kg bag',NULL,1,'2025-02-01','2025-04-19 18:06:24',1,'[\"https://plus.unsplash.com/premium_photo-1725635594762-ef1badbede67?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-03-31 10:00:00'),(6,2,'Fresh Tilapia Fish',8,'Fresh, mild-flavored tilapia farmed in sustainable recirculating systems. High in lean protein (26g per fillet) with low mercury. Harvested at peak freshness and flash-frozen to preserve quality.',8.50,NULL,80,5,'box',5.00,0,'2025-04-01','2025-04-19 04:29:35',1,'[\"https://images.unsplash.com/photo-1498654200943-1088dd4438ae?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(7,4,'Pure Forest Honey',9,'Raw forest honey harvested from wild beehives in protected woodlands. Unprocessed and unfiltered, containing natural pollen and enzymes. Distinct floral notes vary by season. Packed in glass jars to preserve purity.',12.00,NULL,50,1,'jar',0.50,1,'2025-03-15','2025-04-16 18:54:19',1,'[\"https://plus.unsplash.com/premium_photo-1726704133644-bd521a727cf8?q=80&w=2017&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(8,2,'Fresh Spinach Bunch',2,'Fresh, pesticide-free spinach bunches with deep green leaves. Packed with iron (15% DV per serving), vitamin K, and antioxidants. Harvested at peak freshness and carefully bundled to prevent damage.',3.00,NULL,200,2,'bundle',0.30,1,'2025-04-10','2025-04-19 18:07:08',1,'[\"https://images.unsplash.com/photo-1694989032963-d7615dc91965?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(9,2,'Sweet Mangoes',2,'Ripe, sweet mangoes with juicy orange flesh. Each fruit provides 100% of your daily vitamin C needs. Tree-ripened for maximum flavor and carefully packed to prevent bruising during transport.',5.00,NULL,147,3,'crate',3.00,1,'2025-04-05','2025-04-18 14:57:01',1,'[\"https://images.unsplash.com/photo-1685478677113-8c4a58503230?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(10,3,'Yam Tubers',2,'Locally grown white yams with creamy texture when cooked. Excellent source of complex carbs and fiber. Grown using traditional methods without synthetic fertilizers. Each tuber hand-selected for quality.',6.00,NULL,120,1,'sack',10.00,0,'2025-04-03','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1663763025643-42569bdc41b7?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(11,3,'Organic Carrots',2,'Certified organic carrots with exceptional sweetness. Rich in beta-carotene (428% DV per serving) and vitamin A. Grown in mineral-rich soil and hand-harvested at peak sweetness.',4.50,NULL,180,2,'bag',1.00,1,'2025-04-08','2025-04-19 18:06:24',1,'[\"https://plus.unsplash.com/premium_photo-1664277022334-de9ab23c0ee2?q=80&w=2047&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 13:59:12'),(12,4,'Fresh Beef Cuts',1,'Premium grass-fed beef cuts from humanely raised cattle. Marbled for perfect flavor, rich in CLA and omega-3s. Dry-aged for 21 days to enhance tenderness. Butchered to exacting standards.',35.00,28.00,9,1,'Vacuum Pack',1.00,0,'2025-04-12','2025-04-19 04:27:05',1,'[\"https://images.unsplash.com/photo-1705557101766-0d550be69071?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 09:00:00'),(13,3,'Honeycomb Pieces',7,'Natural honeycomb sections filled with raw, unfiltered honey. Contains propolis and bee pollen for added benefits. Perfect for cheese boards or as a natural sweetener. Cut from hives using traditional methods.',18.00,15.00,30,1,'Jar',0.40,1,'2025-03-12','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1642067958024-1a2d9f836920?q=80&w=2094&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 09:00:00'),(14,3,'Wild Forest Mushrooms',3,'Wild-foraged mushrooms sustainably harvested from old-growth forests. Varieties may include chanterelles, morels, and porcini depending on season. Carefully cleaned and packaged to preserve delicate textures.',20.00,16.00,40,1,'Pack',0.30,1,'2025-04-10','2025-04-19 18:07:08',1,'[\"https://images.unsplash.com/photo-1634326598999-1ac816b82223?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00'),(15,2,'Maple Syrup',3,'Pure maple syrup tapped from mature sugar maples. Grade A amber color with robust flavor. Contains 54 beneficial compounds. Boiled slowly in small batches to achieve perfect consistency.',25.00,NULL,30,1,'Bottle',0.50,1,'2025-03-15','2025-04-18 15:15:50',1,'[\"https://images.unsplash.com/photo-1552314971-d2feb3513949?q=80&w=1922&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00'),(16,3,'Pine Nuts',3,'Wild-harvested pine nuts from sustainable sources. Buttery flavor perfect for pesto or salads. Rich in healthy fats (19g per ounce) and magnesium. Hand-shelled and carefully sorted for quality.',30.00,24.00,25,1,'Bag',0.20,1,'2025-03-20','2025-04-16 18:54:19',1,'[\"https://images.unsplash.com/photo-1679740972074-b1ba3049f173?q=80&w=1935&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-16 03:14:00'),(17,2,'Organic Broccoli Heads',2,'Certified organic broccoli with vibrant green heads, rich in vitamin C (135% DV per serving) and fiber. Grown in nutrient-rich soil using no synthetic pesticides. Hand-harvested to ensure freshness.',4.00,3.20,160,2,'Bundle',0.50,1,'2025-04-12','2025-04-19 18:07:08',1,'[\"https://plus.unsplash.com/premium_photo-1702082809453-3fe5a3bd335b?q=80&w=1976&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:00:00'),(18,4,'Grass-Fed Lamb Chops',1,'Premium grass-fed lamb chops from pasture-raised sheep. High in omega-3s and iron, with a rich, tender flavor. Dry-aged for 14 days and vacuum-packed for freshness.',40.00,32.00,15,1,'Vacuum Pack',0.80,0,'2025-04-15','2025-04-19 17:49:31',1,'[\"https://plus.unsplash.com/premium_photo-1661826051876-10b1d7916f42?q=80&w=2091&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:00:00'),(19,3,'Organic Goat Cheese',4,'Creamy organic goat cheese made from fresh, free-range goat milk. Aged for 3 weeks for a mild, tangy flavor. Rich in probiotics and calcium. Packaged in eco-friendly wax.',15.00,12.00,25,1,'Wax-Wrapped',0.25,1,'2025-04-10','2025-04-19 17:47:11',1,'[\"https://plus.unsplash.com/premium_photo-1700836214483-5f546a29d65f?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:00:00'),(20,2,'Rainbow Trout Fillets',8,'Fresh rainbow trout fillets from sustainable aquaculture systems. High in omega-3s (1.2g per fillet) and low in mercury. Flash-frozen to lock in flavor and texture.',10.00,NULL,60,4,'Box',0.50,0,'2025-04-14','2025-04-19 17:47:11',1,'[\"https://images.unsplash.com/photo-1611214774777-3d997a9d0e35?q=80&w=1932&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:00:00'),(21,4,'Organic Bee Pollen',9,'Pure organic bee pollen collected from wildflower hives. Packed with vitamins, minerals, and antioxidants. Perfect for smoothies or as a natural energy booster. Packaged in glass jars.',20.00,16.00,40,1,'Jar',0.20,1,'2025-04-01','2025-04-19 17:47:11',1,'[\"https://images.unsplash.com/photo-1570723811540-0f4d1836ef58?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:00:00'),(22,3,'Organic Free-Range Chicken',5,'Whole organic free-range chickens raised on open pastures with 108 sq ft per bird. Fed a natural diet, resulting in tender, flavorful meat high in protein (25g per serving). Vacuum-packed for freshness.',22.00,17.60,20,1,'Vacuum Pack',1.50,1,'2025-04-15','2025-04-19 17:59:19',1,'[\"https://plus.unsplash.com/premium_photo-1661812833073-7b47478ffafe?q=80&w=2072&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:30:00'),(23,4,'Wild-Harvested Walnuts',7,'Premium wild-harvested walnuts, raw and unsalted. Rich in omega-3s (2.5g per ounce) and antioxidants. Hand-shelled and sorted for quality. Perfect for baking or snacking.',20.00,15.00,60,1,'1kg Bag',1.00,1,'2025-03-25','2025-04-19 17:56:29',1,'[\"https://images.unsplash.com/photo-1606984909580-97248354eb5f?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:30:00'),(24,3,'Organic Strawberries',2,'Juicy organic strawberries, hand-picked at peak ripeness. Packed with vitamin C (150% DV per serving) and antioxidants. Grown without synthetic pesticides and packed in eco-friendly cartons.',6.00,4.80,100,1,'Carton',0.50,1,'2025-04-10','2025-04-19 18:06:24',1,'[\"https://plus.unsplash.com/premium_photo-1667049292891-22a01a767f5e?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:30:00'),(25,2,'Fresh Farmed Shrimp',8,'Sustainably farmed shrimp, peeled and deveined for convenience. High in protein (20g per 100g) and low in fat. Flash-frozen to preserve freshness and flavor. Sourced from eco-friendly aquaculture systems.',15.00,NULL,50,2,'Box',1.00,0,'2025-04-12','2025-04-19 17:56:29',1,'[\"https://images.unsplash.com/photo-1700659393124-ef499dde9411?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:30:00'),(26,4,'Organic Beeswax Candles',9,'Hand-poured organic beeswax candles from sustainably managed hives. Naturally scented with a subtle honey aroma, burns cleanly for 20+ hours. Packaged in recyclable materials.',10.00,8.00,80,2,'Pack',0.20,1,'2025-03-20','2025-04-19 17:56:29',1,'[\"https://images.unsplash.com/photo-1668090728011-dc9ffc555825?q=80&w=1931&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:30:00'),(27,3,'Organic Duck Eggs',5,'Certified organic duck eggs from free-range ducks with access to natural ponds. Larger than chicken eggs, with richer yolks high in omega-3s (250mg per egg). Packaged in recycled cartons.',14.00,11.20,40,1,'Dozen',0.80,1,'2025-04-16','2025-04-19 18:14:55',1,'[\"https://images.unsplash.com/photo-1583219691003-11f9a428e212?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:45:00'),(28,2,'Organic Maple Sugar',3,'Pure organic maple sugar made from sustainably tapped sugar maples. Naturally sweet with a rich caramel flavor, perfect for baking or coffee. Packed with antioxidants, packaged in resealable bags.',18.00,14.40,50,1,'500g Bag',0.50,1,'2025-03-20','2025-04-19 18:14:55',1,'[\"https://images.unsplash.com/photo-1702297197382-6e3f7c3cd145?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:45:00'),(29,4,'Organic Sweet Potatoes',2,'Vibrant organic sweet potatoes with naturally sweet flesh. High in beta-carotene (400% DV per serving) and fiber. Grown in regenerative soils, hand-selected for quality.',5.50,4.40,120,2,'Bag',1.00,1,'2025-04-14','2025-04-19 18:14:55',1,'[\"https://images.unsplash.com/photo-1730815048561-45df6f7f331d?q=80&w=1931&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:45:00'),(30,3,'Organic Cheddar Cheese',4,'Sharp organic cheddar cheese aged for 12 months, made from pasture-raised cow’s milk. Rich in calcium and protein, with a bold, tangy flavor. Packaged in eco-friendly wax.',16.00,12.80,30,1,'Wax-Wrapped',0.30,1,'2025-04-10','2025-04-19 18:14:55',1,'[\"https://images.unsplash.com/photo-1668104129962-66e931ec9a61?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:45:00'),(31,3,'Organic Quail Eggs',5,'Delicate organic quail eggs from free-range quails fed a natural diet. High in protein and B vitamins, perfect for gourmet dishes. Packaged in protective cartons.',10.00,8.00,60,2,'Dozen',0.20,1,'2025-04-17','2025-04-19 18:14:55',1,'[\"https://images.unsplash.com/photo-1740476371489-835643ee9791?q=80&w=1971&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D\"]','2025-04-19 10:45:00');
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shopping_carts`
--

LOCK TABLES `shopping_carts` WRITE;
/*!40000 ALTER TABLE `shopping_carts` DISABLE KEYS */;
INSERT INTO `shopping_carts` VALUES (27,14,'2025-04-19 04:26:41','2025-04-19 17:52:16','[]','customer_cart');
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin1','$2y$10$Bw0/U7IHaMor5pM3UwtYoem.krL/caxkQbA86XttLwftD3C4cIDg2','admin@agrimarket.test','admin','016-1234567',NULL,NULL),(2,'farmer_john','$2y$10$rajnHudllQWC.0R5kE02B.PKh6NvvKK73KoSBZ/03CPE7JMG5cVGS','john@greenfarms.test','vendor','017-2345678',NULL,NULL),(3,'customer_mary','$2y$10$hashed_customer1','mary@example.com','vendor','012-3456789',NULL,NULL),(4,'john_doe','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','john@example.com','vendor','013-4567890',NULL,NULL),(5,'jane_smith','$2y$10$6rS13bgB.zY2c/nS.bDmQOdDzk/Sh2SkdDE1j8WBhqkuKx/6EudgO','jane@example.com','staff','014-5678901',NULL,NULL),(6,'mike_jones','$2y$10$bfkX9NkfV1AJ.oZJ7dY0iuJAhOqmiOX.133PRYT/k4EsiqB9orToa','mike@example.com','staff','015-6789012',NULL,NULL),(7,'sarah_williams','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','sarah@example.com','vendor','018-7890123',NULL,NULL),(8,'david_brown','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','david@example.com','customer','019-8901234',NULL,NULL),(9,'emily_davis','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','emily@example.com','customer','011-9012345',NULL,NULL),(10,'robert_wilson','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','robert@example.com','customer','010-0123456',NULL,NULL),(14,'soon _yong','$2y$10$APcIxsv30vU22HUi.QDMtuIpDtWSvmP6yGUI.naA7KciM3Fv2Kv4a','yong178344@gmail.com','customer','016-7239840','416167','2025-04-15 20:07:43'),(15,'admin2','$2y$10$YOUR_GENERATED_HASH_HERE','admin2@agrimarket.test','admin','016-12345678',NULL,NULL),(16,'lisa_tan','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','lisa@example.com','customer','012-1234567',NULL,NULL),(17,'ahmad_zaki','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','ahmad@example.com','customer','013-2345678',NULL,NULL),(18,'siti_nur','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','siti@example.com','customer','014-3456789',NULL,NULL),(19,'wei_chen','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','wei@example.com','customer','015-4567890',NULL,NULL),(20,'nurul_aini','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','nurul@example.com','customer','016-5678901',NULL,NULL),(21,'raj_kumar','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','raj@example.com','customer','017-6789012',NULL,NULL),(22,'mei_ling','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','mei@example.com','customer','011-1234567',NULL,NULL),(23,'hafiz_rahman','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','hafiz@example.com','customer','012-2345678',NULL,NULL),(24,'zara_lee','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','zara@example.com','customer','013-3456789',NULL,NULL),(25,'kumar_singh','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','kumar@example.com','customer','014-4567890',NULL,NULL),(26,'fatima_zain','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','fatima@example.com','customer','015-5678901',NULL,NULL),(27,'chong_wei','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','chong@example.com','customer','016-6789012',NULL,NULL),(28,'aisha_bakar','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','aisha@example.com','customer','017-7890123',NULL,NULL),(29,'daniel_lim','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','daniel@example.com','customer','018-8901234',NULL,NULL),(30,'sofia_ong','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','sofia@example.com','customer','019-9012345',NULL,NULL),(31,'arjun_nair','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','arjun@example.com','customer','010-0123456',NULL,NULL);
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

-- Dump completed on 2025-04-19 19:17:21
