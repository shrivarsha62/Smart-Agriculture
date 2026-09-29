-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 29, 2026 at 06:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_agriculture`
--
CREATE DATABASE IF NOT EXISTS `smart_agriculture` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `smart_agriculture`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `GetFarmerCrops`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetFarmerCrops` (IN `f_id` INT)   BEGIN
    SELECT 
        Farmer.name,
        Crop.crop_name,
        Crop.season,
        Crop.duration
    FROM Farmer
    JOIN Field
        ON Farmer.farmer_id = Field.farmer_id
    JOIN Crop
        ON Field.field_id = Crop.field_id
    WHERE Farmer.farmer_id = f_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `crop`
--

DROP TABLE IF EXISTS `crop`;
CREATE TABLE `crop` (
  `crop_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `crop_name` varchar(100) NOT NULL,
  `season` varchar(50) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop`
--

INSERT INTO `crop` (`crop_id`, `field_id`, `crop_name`, `season`, `duration`) VALUES
(1, 1, 'Tomato', 'Summer', 90),
(2, 2, 'Rice', 'Monsoon', 120),
(3, 3, 'Groundnut', 'Summer', 100),
(4, 4, 'Cotton', 'Winter', 180);

-- --------------------------------------------------------

--
-- Table structure for table `disease`
--

DROP TABLE IF EXISTS `disease`;
CREATE TABLE `disease` (
  `disease_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `disease_name` varchar(100) NOT NULL,
  `symptoms` varchar(255) DEFAULT NULL,
  `recommended_treatment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disease`
--

INSERT INTO `disease` (`disease_id`, `crop_id`, `disease_name`, `symptoms`, `recommended_treatment`) VALUES
(1, 1, 'Leaf Spot', 'Brown spots on leaves', 'Use suitable fungicide'),
(2, 2, 'Blast Disease', 'Spots on leaves and stems', 'Apply recommended fungicide'),
(3, 3, 'Rust', 'Orange spots on leaves', 'Use appropriate fungicide'),
(4, 4, 'Wilt', 'Leaves become yellow and wilt', 'Improve soil drainage');

-- --------------------------------------------------------

--
-- Table structure for table `farmer`
--

DROP TABLE IF EXISTS `farmer`;
CREATE TABLE `farmer` (
  `farmer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer`
--

INSERT INTO `farmer` (`farmer_id`, `name`, `phone`, `location`) VALUES
(1, 'Arun Kumar', '9876543210', 'Coimbatore'),
(2, 'Priya Devi', '9876543211', 'Erode'),
(3, 'Ravi Kumar', '9876543212', 'Salem'),
(5, 'Karthik Raj', '9000000001', 'Madurai'),
(6, 'Somu', '4363737288', 'Avinashi');

--
-- Triggers `farmer`
--
DROP TRIGGER IF EXISTS `after_farmer_insert`;
DELIMITER $$
CREATE TRIGGER `after_farmer_insert` AFTER INSERT ON `farmer` FOR EACH ROW BEGIN
    INSERT INTO Farmer_Log (farmer_id, action)
    VALUES (NEW.farmer_id, 'New farmer added');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `farmer_crop_details`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `farmer_crop_details`;
CREATE TABLE `farmer_crop_details` (
`name` varchar(100)
,`field_id` int(11)
,`area` decimal(10,2)
,`soil_type` varchar(50)
,`crop_name` varchar(100)
,`season` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `farmer_log`
--

DROP TABLE IF EXISTS `farmer_log`;
CREATE TABLE `farmer_log` (
  `log_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `log_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer_log`
--

INSERT INTO `farmer_log` (`log_id`, `farmer_id`, `action`, `log_date`) VALUES
(1, 5, 'New farmer added', '2026-09-12 03:38:05'),
(2, 6, 'New farmer added', '2026-09-29 06:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `fertilizer`
--

DROP TABLE IF EXISTS `fertilizer`;
CREATE TABLE `fertilizer` (
  `fertilizer_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `fertilizer_name` varchar(100) NOT NULL,
  `recommended_quantity` decimal(10,2) DEFAULT NULL,
  `application_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fertilizer`
--

INSERT INTO `fertilizer` (`fertilizer_id`, `crop_id`, `fertilizer_name`, `recommended_quantity`, `application_date`) VALUES
(1, 1, 'NPK Fertilizer', 5.00, '2026-09-05'),
(2, 2, 'Urea', 10.00, '2026-09-05'),
(3, 3, 'DAP Fertilizer', 6.00, '2026-09-06'),
(4, 4, 'Potassium Fertilizer', 8.00, '2026-09-06');

-- --------------------------------------------------------

--
-- Table structure for table `field`
--

DROP TABLE IF EXISTS `field`;
CREATE TABLE `field` (
  `field_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `soil_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `field`
--

INSERT INTO `field` (`field_id`, `farmer_id`, `area`, `soil_type`) VALUES
(1, 1, 2.80, 'Red Soil'),
(2, 1, 1.50, 'Black Soil'),
(3, 2, 3.00, 'Sandy Soil'),
(4, 3, 2.00, 'Loamy Soil');

-- --------------------------------------------------------

--
-- Table structure for table `irrigation`
--

DROP TABLE IF EXISTS `irrigation`;
CREATE TABLE `irrigation` (
  `irrigation_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `irrigation_date` date DEFAULT NULL,
  `water_quantity` decimal(10,2) DEFAULT NULL,
  `irrigation_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `irrigation`
--

INSERT INTO `irrigation` (`irrigation_id`, `field_id`, `irrigation_date`, `water_quantity`, `irrigation_method`) VALUES
(1, 1, '2026-09-01', 50.00, 'Drip'),
(2, 2, '2026-09-02', 80.00, 'Flood'),
(3, 3, '2026-09-03', 60.00, 'Sprinkler'),
(4, 4, '2026-09-04', 70.00, 'Drip');

-- --------------------------------------------------------

--
-- Table structure for table `market`
--

DROP TABLE IF EXISTS `market`;
CREATE TABLE `market` (
  `market_id` int(11) NOT NULL,
  `crop_id` int(11) NOT NULL,
  `market_name` varchar(100) NOT NULL,
  `price_per_kg` decimal(10,2) DEFAULT NULL,
  `price_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market`
--

INSERT INTO `market` (`market_id`, `crop_id`, `market_name`, `price_per_kg`, `price_date`) VALUES
(1, 1, 'Coimbatore Market', 35.00, '2026-09-08'),
(2, 2, 'Erode Market', 45.00, '2026-09-08'),
(3, 3, 'Salem Market', 70.00, '2026-09-08'),
(4, 4, 'Coimbatore Market', 85.00, '2026-09-08');

-- --------------------------------------------------------

--
-- Structure for view `farmer_crop_details`
--
DROP TABLE IF EXISTS `farmer_crop_details`;

DROP VIEW IF EXISTS `farmer_crop_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `farmer_crop_details`  AS SELECT `farmer`.`name` AS `name`, `field`.`field_id` AS `field_id`, `field`.`area` AS `area`, `field`.`soil_type` AS `soil_type`, `crop`.`crop_name` AS `crop_name`, `crop`.`season` AS `season` FROM ((`farmer` join `field` on(`farmer`.`farmer_id` = `field`.`farmer_id`)) join `crop` on(`field`.`field_id` = `crop`.`field_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crop`
--
ALTER TABLE `crop`
  ADD PRIMARY KEY (`crop_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `disease`
--
ALTER TABLE `disease`
  ADD PRIMARY KEY (`disease_id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `farmer`
--
ALTER TABLE `farmer`
  ADD PRIMARY KEY (`farmer_id`);

--
-- Indexes for table `farmer_log`
--
ALTER TABLE `farmer_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `fertilizer`
--
ALTER TABLE `fertilizer`
  ADD PRIMARY KEY (`fertilizer_id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- Indexes for table `field`
--
ALTER TABLE `field`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `farmer_id` (`farmer_id`);

--
-- Indexes for table `irrigation`
--
ALTER TABLE `irrigation`
  ADD PRIMARY KEY (`irrigation_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `market`
--
ALTER TABLE `market`
  ADD PRIMARY KEY (`market_id`),
  ADD KEY `crop_id` (`crop_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `crop`
--
ALTER TABLE `crop`
  MODIFY `crop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `disease`
--
ALTER TABLE `disease`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `farmer`
--
ALTER TABLE `farmer`
  MODIFY `farmer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `farmer_log`
--
ALTER TABLE `farmer_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fertilizer`
--
ALTER TABLE `fertilizer`
  MODIFY `fertilizer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `field`
--
ALTER TABLE `field`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `irrigation`
--
ALTER TABLE `irrigation`
  MODIFY `irrigation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `market`
--
ALTER TABLE `market`
  MODIFY `market_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crop`
--
ALTER TABLE `crop`
  ADD CONSTRAINT `crop_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `field` (`field_id`);

--
-- Constraints for table `disease`
--
ALTER TABLE `disease`
  ADD CONSTRAINT `disease_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`);

--
-- Constraints for table `fertilizer`
--
ALTER TABLE `fertilizer`
  ADD CONSTRAINT `fertilizer_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`);

--
-- Constraints for table `field`
--
ALTER TABLE `field`
  ADD CONSTRAINT `field_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`);

--
-- Constraints for table `irrigation`
--
ALTER TABLE `irrigation`
  ADD CONSTRAINT `irrigation_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `field` (`field_id`);

--
-- Constraints for table `market`
--
ALTER TABLE `market`
  ADD CONSTRAINT `market_ibfk_1` FOREIGN KEY (`crop_id`) REFERENCES `crop` (`crop_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
