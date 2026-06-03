-- JAQ Meatshop database backup
-- Database: `informationmanagement`
-- Created: 2026-05-20 11:54:56
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `category`;
CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`, `Status`) VALUES ('1', 'Meat', 'Beef', 'Chicken');
INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`, `Status`) VALUES ('2', 'Pork', NULL, 'Active');
INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`, `Status`) VALUES ('3', 'Beef', NULL, 'Active');
INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`, `Status`) VALUES ('4', 'Chicken', NULL, 'Active');
INSERT INTO `category` (`CategoryID`, `CategoryName`, `Description`, `Status`) VALUES ('5', 'Processed Foods', NULL, 'Active');

DROP TABLE IF EXISTS `inventorylog`;
CREATE TABLE `inventorylog` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `ChangeType` varchar(10) DEFAULT NULL,
  `WeightChanged` decimal(10,2) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `Remarks` text DEFAULT NULL,
  PRIMARY KEY (`LogID`),
  KEY `ProductID` (`ProductID`),
  KEY `id` (`id`),
  CONSTRAINT `inventorylog_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `inventorylog_ibfk_2` FOREIGN KEY (`id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductName` varchar(100) NOT NULL,
  `ProductPart` varchar(100) DEFAULT NULL,
  `ProductType` varchar(50) DEFAULT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `PricePerKg` decimal(10,2) NOT NULL,
  `StockWeight` decimal(10,2) DEFAULT 0.00,
  `DateAdded` datetime DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Available',
  `ProductImage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ProductID`),
  KEY `CategoryID` (`CategoryID`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('1', 'Pig', NULL, NULL, '1', '350.00', '4.00', '2026-04-22 10:38:41', 'Unavailable', NULL);
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('8', 'Beef', NULL, NULL, '1', '450.00', '7.00', '2026-05-20 09:31:00', 'Unavailable', 'uploads/products/product_6a0d0ed459ff41.58745897.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('11', 'Pig', NULL, NULL, '1', '260.00', '0.00', '2026-05-20 13:41:18', 'Unavailable', 'uploads/products/product_6a0d497e3c8994.16966384.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('15', 'Pork Belly', 'Pork Belly', 'Meat', '2', '320.00', '5.00', '2026-05-20 16:38:54', 'Available', 'uploads/products/product_6a0d731eb09b68.50031293.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('16', 'Whole Chicken', 'Whole Chicken', 'Meat', '4', '200.00', '6.00', '2026-05-20 16:39:18', 'Available', 'uploads/products/product_6a0d7336e7c155.98837993.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('17', 'Tenderloin', 'Tenderloin', 'Meat', '3', '400.00', '4.00', '2026-05-20 16:39:54', 'Available', 'uploads/products/product_6a0d735a743443.76431192.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('18', 'Leg', 'Leg', 'Meat', '2', '250.00', '4.00', '2026-05-20 16:54:41', 'Available', 'uploads/products/product_6a0d76d12d3975.33423737.jpg');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('19', 'Hotdog', 'Hotdog', 'Processed Foods', '5', '20.00', '7.00', '2026-05-20 16:55:08', 'Available', 'uploads/products/product_6a0d76ec28a1e1.83021197.webp');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('20', 'Thigh', 'Thigh', 'Meat', '4', '200.00', '6.00', '2026-05-20 17:20:33', 'Available', 'uploads/products/product_6a0d7ce17b8e75.76451526.webp');
INSERT INTO `product` (`ProductID`, `ProductName`, `ProductPart`, `ProductType`, `CategoryID`, `PricePerKg`, `StockWeight`, `DateAdded`, `Status`, `ProductImage`) VALUES ('21', 'Organs', 'Organs', 'Meat', '2', '150.00', '5.00', '2026-05-20 17:21:53', 'Available', 'uploads/products/product_6a0d7d310149e9.56655843.webp');

DROP TABLE IF EXISTS `supplier`;
CREATE TABLE `supplier` (
  `SupplierID` int(11) NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(100) NOT NULL,
  `ContactPerson` varchar(120) DEFAULT NULL,
  `ContactNumber` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `MeatType` varchar(100) DEFAULT NULL,
  `DeliverySchedule` varchar(100) DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'Active',
  `Phone` varchar(40) DEFAULT NULL,
  `DateAdded` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`SupplierID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `supplier` (`SupplierID`, `SupplierName`, `ContactPerson`, `ContactNumber`, `Address`, `MeatType`, `DeliverySchedule`, `Status`, `Phone`, `DateAdded`) VALUES ('2', 'adad', 'adad', NULL, 'adad', NULL, NULL, 'Active', '09511915227', '2026-05-20 15:47:16');

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `TransactionID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` int(11) DEFAULT NULL,
  `id` int(11) DEFAULT NULL,
  `WeightSold` decimal(10,2) DEFAULT NULL,
  `TotalPrice` decimal(10,2) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`TransactionID`),
  KEY `ProductID` (`ProductID`),
  KEY `id` (`id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `transactions` (`TransactionID`, `ProductID`, `id`, `WeightSold`, `TotalPrice`, `DateTime`, `PaymentMethod`) VALUES ('1', '1', NULL, '1.00', '1000.00', '2026-05-08 10:02:06', NULL);
INSERT INTO `transactions` (`TransactionID`, `ProductID`, `id`, `WeightSold`, `TotalPrice`, `DateTime`, `PaymentMethod`) VALUES ('2', '1', NULL, '2.00', '700.00', '2026-05-20 09:28:10', NULL);
INSERT INTO `transactions` (`TransactionID`, `ProductID`, `id`, `WeightSold`, `TotalPrice`, `DateTime`, `PaymentMethod`) VALUES ('3', '11', NULL, '2.00', '520.00', '2026-05-20 14:03:25', NULL);
INSERT INTO `transactions` (`TransactionID`, `ProductID`, `id`, `WeightSold`, `TotalPrice`, `DateTime`, `PaymentMethod`) VALUES ('4', '11', NULL, '3.00', '780.00', '2026-05-20 14:47:03', NULL);
INSERT INTO `transactions` (`TransactionID`, `ProductID`, `id`, `WeightSold`, `TotalPrice`, `DateTime`, `PaymentMethod`) VALUES ('5', '8', NULL, '4.00', '1800.00', '2026-05-20 14:59:56', NULL);

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cashier_name` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `cashier_name`, `username`, `password`, `role`) VALUES ('1', NULL, 'admin', '$2y$10$RtGXhsZ6R1cP6v/g3pPplOUFaBSXl0Pd1Td76UDSoB24nRUujxMmS', 'admin');
INSERT INTO `users` (`id`, `cashier_name`, `username`, `password`, `role`) VALUES ('2', 'Jbits', 'cashier1', '$2y$10$zmXWoGJiZ6EomHbJh6AoQOY1oIZUKOpdPXJHIUzNm6eYgJ7e3Kryq', 'cashier');
INSERT INTO `users` (`id`, `cashier_name`, `username`, `password`, `role`) VALUES ('3', 'JBITTTSSS', 'cashier2', '$2y$10$tvPWZ.J0uk8Lr2O5R.jn8uJkeGhC8fmlFf7tcwCFmsfGSnQiIL79S', 'cashier');

SET FOREIGN_KEY_CHECKS=1;