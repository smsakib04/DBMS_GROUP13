-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 12:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tortoise_conservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `breeding_pairs`
--

CREATE TABLE `breeding_pairs` (
  `pair_id` int(11) NOT NULL,
  `pair_code` varchar(20) DEFAULT NULL,
  `male_tortoise_id` int(11) NOT NULL,
  `female_tortoise_id` int(11) NOT NULL,
  `pairing_date` date NOT NULL,
  `status` enum('paired','courting','incubating','hatched','separated') DEFAULT 'paired',
  `notes` text DEFAULT NULL
) ;

--
-- Dumping data for table `breeding_pairs`
--

INSERT INTO `breeding_pairs` (`pair_id`, `pair_code`, `male_tortoise_id`, `female_tortoise_id`, `pairing_date`, `status`, `notes`) VALUES
(1, 'BP-021', 1, 2, '2025-01-10', 'incubating', NULL),
(2, 'BP-022', 3, 4, '2025-02-03', 'incubating', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

CREATE TABLE `collections` (
  `collection_id` int(11) NOT NULL,
  `tortoise_id` int(11) DEFAULT NULL,
  `collection_date` date NOT NULL,
  `source_type` enum('Wild','Rescue','Donation') NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `initial_health` enum('Healthy','Weak','Injured') NOT NULL,
  `notes` text DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collections`
--

INSERT INTO `collections` (`collection_id`, `tortoise_id`, `collection_date`, `source_type`, `location`, `initial_health`, `notes`, `collected_by`) VALUES
(1, 5, '2026-04-10', 'Rescue', 'Dhaka', 'Weak', 'Found near construction site', 3);

-- --------------------------------------------------------

--
-- Table structure for table `dietary_items`
--

CREATE TABLE `dietary_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `amount_grams` decimal(8,2) DEFAULT NULL,
  `for_species_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enclosures`
--

CREATE TABLE `enclosures` (
  `enclosure_id` int(11) NOT NULL,
  `enclosure_code` varchar(20) NOT NULL,
  `enclosure_name` varchar(100) NOT NULL,
  `habitat_type` enum('Desert','Rainforest','Grassland','Wetland','Mediterranean','Savanna') NOT NULL,
  `size_sq_meters` decimal(8,2) DEFAULT NULL,
  `capacity` int(11) DEFAULT 1,
  `current_occupancy` int(11) DEFAULT 0,
  `last_cleaning` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `status` enum('Active','Maintenance','Quarantine') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enclosures`
--

INSERT INTO `enclosures` (`enclosure_id`, `enclosure_code`, `enclosure_name`, `habitat_type`, `size_sq_meters`, `capacity`, `current_occupancy`, `last_cleaning`, `next_maintenance`, `status`) VALUES
(1, 'E-Arid-01', 'Arid Zone E1', 'Desert', 45.00, 4, 2, '2025-03-24', '2025-04-03', 'Active'),
(2, 'E-Trop-03', 'Rainforest E3', 'Rainforest', 78.00, 6, 3, '2025-03-25', '2025-04-01', 'Active'),
(3, 'E-Grass-07', 'Savanna E7', 'Savanna', 60.00, 5, 2, '2025-03-26', '2025-04-02', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_schedules`
--

CREATE TABLE `feeding_schedules` (
  `schedule_id` int(11) NOT NULL,
  `tortoise_id` int(11) NOT NULL,
  `feeding_time` time NOT NULL,
  `food_type` varchar(100) NOT NULL,
  `amount_grams` decimal(6,2) DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT 0,
  `scheduled_date` date NOT NULL,
  `feeder_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feeding_schedules`
--

INSERT INTO `feeding_schedules` (`schedule_id`, `tortoise_id`, `feeding_time`, `food_type`, `amount_grams`, `is_done`, `scheduled_date`, `feeder_id`, `notes`) VALUES
(1, 1, '08:00:00', 'Vegetables', 500.00, 0, '2026-04-10', 4, NULL),
(2, 2, '10:30:00', 'Mixed Greens', 800.00, 0, '2026-04-10', 4, NULL),
(3, 3, '13:00:00', 'Fruits & Vegetables', 600.00, 0, '2026-04-10', 4, NULL),
(4, 4, '16:00:00', 'Hay & Supplements', 700.00, 0, '2026-04-10', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `health_assessments`
--

CREATE TABLE health_assessments (
  assessment_id INT NOT NULL AUTO_INCREMENT,
  assessment_code VARCHAR(50) NOT NULL, -- This is where you type "ASS-2025-001"
  assessment_date DATE NOT NULL,
  remarks TEXT,
  tortoise_id INT NOT NULL,
  PRIMARY KEY (assessment_id),
  CONSTRAINT fk_tortoise FOREIGN KEY (tortoise_id) REFERENCES tortoises(tortoise_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `health_assessments`
--

INSERT INTO `health_assessments` (`assessment_id`, `tortoise_id`, `vet_id`, `assessment_date`, `diagnosis`, `treatment`, `remarks`, `next_checkup_date`, `created_at`) VALUES
(1, 3, 7, '2026-04-10', 'Minor respiratory infection', 'Antibiotics for 5 days', 'Isolate and monitor', NULL, '2026-04-10 08:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `incubation_readings`
--

CREATE TABLE `incubation_readings` (
  `reading_id` int(11) NOT NULL,
  `incubator_id` int(11) NOT NULL,
  `nest_id` int(11) DEFAULT NULL,
  `reading_date` date NOT NULL,
  `day_of_incubation` int(11) DEFAULT NULL,
  `temperature_c` decimal(4,2) DEFAULT NULL,
  `humidity_percent` int(11) DEFAULT NULL,
  `co2_ppm` int(11) DEFAULT NULL,
  `egg_turn_cycle` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incubators`
--

CREATE TABLE `incubators` (
  `incubator_id` int(11) NOT NULL,
  `incubator_code` varchar(20) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance','offline') DEFAULT 'active',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incubators`
--

INSERT INTO `incubators` (`incubator_id`, `incubator_code`, `location`, `status`, `notes`) VALUES
(1, 'INC-01', 'Hatchery Room A', 'active', NULL),
(2, 'INC-02', 'Hatchery Room B', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` enum('food','medical','cleaning','equipment','other') DEFAULT 'other',
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `reorder_level` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `managed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `item_name`, `category`, `quantity`, `unit`, `reorder_level`, `supplier`, `last_updated`, `managed_by`) VALUES
(1, 'Calcium blocks', 'food', 3.80, 'kg', 2.00, 'ReptiSupply', '2025-03-01', NULL),
(2, 'Water Quality Kit', 'medical', 5.00, 'pieces', 2.00, 'Lab Supplies Inc.', '2025-03-15', NULL),
(3, 'Temperature Sensor', 'equipment', 3.00, 'pieces', 1.00, 'Tech Equip Ltd.', '2025-03-10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `iot_alerts`
--

CREATE TABLE `iot_alerts` (
  `alert_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `parameter` varchar(50) DEFAULT NULL,
  `actual_value` varchar(50) DEFAULT NULL,
  `threshold_value` varchar(50) DEFAULT NULL,
  `severity` enum('warning','critical','maintenance') DEFAULT 'warning',
  `alert_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iot_devices`
--

CREATE TABLE `iot_devices` (
  `device_id` int(11) NOT NULL,
  `device_code` varchar(50) NOT NULL,
  `device_type` enum('Temp/Humidity','Water quality','Incubator','Light','CO2','Other') NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `battery_level` int(11) DEFAULT NULL CHECK (`battery_level` between 0 and 100),
  `last_ping` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('online','offline','low battery','maintenance') DEFAULT 'online',
  `installed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iot_devices`
--

INSERT INTO `iot_devices` (`device_id`, `device_code`, `device_type`, `location`, `battery_level`, `last_ping`, `status`, `installed_date`) VALUES
(1, 'IOT-TH-012', 'Temp/Humidity', 'Enclosure A3', 87, '2026-04-10 08:28:20', 'online', NULL),
(2, 'IOT-WQ-003', 'Water quality', 'Marsh pond', 91, '2026-04-10 08:28:20', 'online', NULL),
(3, 'IOT-INC-09', 'Incubator', 'Hatchery #2', 74, '2026-04-10 08:28:20', 'online', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `iot_readings`
--

CREATE TABLE `iot_readings` (
  `reading_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `reading_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `temperature_c` decimal(4,2) DEFAULT NULL,
  `humidity_percent` int(11) DEFAULT NULL,
  `uv_index` decimal(3,1) DEFAULT NULL,
  `co2_ppm` int(11) DEFAULT NULL,
  `water_ph` decimal(3,1) DEFAULT NULL,
  `ammonia_ppm` decimal(5,3) DEFAULT NULL,
  `nitrate_ppm` decimal(5,2) DEFAULT NULL,
  `turbidity` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iot_readings`
--

INSERT INTO `iot_readings` (`reading_id`, `device_id`, `reading_time`, `temperature_c`, `humidity_percent`, `uv_index`, `co2_ppm`, `water_ph`, `ammonia_ppm`, `nitrate_ppm`, `turbidity`) VALUES
(1, 1, '2026-04-10 08:28:20', 29.80, 34, 5.2, NULL, NULL, NULL, NULL, NULL),
(2, 2, '2026-04-10 08:28:20', NULL, NULL, NULL, NULL, 7.2, NULL, NULL, NULL),
(3, 3, '2026-04-10 08:28:20', 29.40, 74, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nests`
--

CREATE TABLE `nests` (
  `nest_id` int(11) NOT NULL,
  `nest_code` varchar(20) DEFAULT NULL,
  `pair_id` int(11) NOT NULL,
  `nesting_date` date NOT NULL,
  `egg_count` int(11) NOT NULL,
  `fertile_eggs` int(11) DEFAULT NULL,
  `incubator_id` int(11) DEFAULT NULL,
  `estimated_hatch_date` date DEFAULT NULL,
  `actual_hatch_date` date DEFAULT NULL,
  `hatch_success_rate` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nests`
--

INSERT INTO `nests` (`nest_id`, `nest_code`, `pair_id`, `nesting_date`, `egg_count`, `fertile_eggs`, `incubator_id`, `estimated_hatch_date`, `actual_hatch_date`, `hatch_success_rate`, `notes`) VALUES
(1, 'Nest-AA4', 1, '2025-01-12', 8, 7, 1, '2025-04-14', NULL, NULL, NULL),
(2, 'Nest-BG2', 2, '2025-02-07', 12, 10, 2, '2025-06-02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `observations`
--

CREATE TABLE `observations` (
  `observation_id` int(11) NOT NULL,
  `tortoise_id` int(11) DEFAULT NULL,
  `enclosure_id` int(11) DEFAULT NULL,
  `observer_id` int(11) NOT NULL,
  `observation_date` date NOT NULL,
  `category` enum('behavior','health','feeding','nesting','general') DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `request_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity_needed` decimal(10,2) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `needed_by_date` date DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `status` enum('pending','approved','ordered','received') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `special_dietary_needs`
--

CREATE TABLE `special_dietary_needs` (
  `need_id` int(11) NOT NULL,
  `tortoise_id` int(11) NOT NULL,
  `restriction` text NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `species`
--

CREATE TABLE `species` (
  `species_id` int(11) NOT NULL,
  `common_name` varchar(100) NOT NULL,
  `scientific_name` varchar(150) DEFAULT NULL,
  `iucn_status` enum('Critically Endangered','Endangered','Vulnerable','Near Threatened','Least Concern','Data Deficient','Not Assessed') DEFAULT 'Not Assessed',
  `avg_lifespan_years` int(11) DEFAULT NULL,
  `avg_weight_kg` decimal(5,2) DEFAULT NULL,
  `conservation_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `species`
--

INSERT INTO `species` (`species_id`, `common_name`, `scientific_name`, `iucn_status`, `avg_lifespan_years`, `avg_weight_kg`, `conservation_notes`) VALUES
(1, 'Aldabra Giant Tortoise', 'Aldabrachelys gigantea', 'Vulnerable', 150, 250.00, NULL),
(2, 'African Spurred Tortoise', 'Centrochelys sulcata', 'Endangered', 80, 100.00, NULL),
(3, 'Leopard Tortoise', 'Stigmochelys pardalis', 'Least Concern', 50, 40.00, NULL),
(4, 'Galapagos Tortoise', 'Chelonoidis niger', 'Vulnerable', 100, 300.00, NULL),
(5, 'Indian Star Tortoise', 'Geochelone elegans', 'Vulnerable', 60, 15.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('breeding_officer','caretaker','collecting_officer','feeder','iot_tech','supervisor','veterinarian') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `shift` varchar(20) DEFAULT NULL,
  `assigned_zone` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `username`, `password_hash`, `full_name`, `role`, `phone`, `email`, `shift`, `assigned_zone`, `hire_date`, `status`, `created_at`) VALUES
(1, 'breeding1', 'dummy_hash', 'Dr. Emily Reed', 'breeding_officer', '555-0101', NULL, 'day', 'Hatchery', '2024-01-10', 'active', '2026-04-10 08:28:20'),
(2, 'caretaker1', 'dummy_hash', 'John Miller', 'caretaker', '555-0102', NULL, 'morning', 'Zone A', '2024-02-15', 'active', '2026-04-10 08:28:20'),
(3, 'collector1', 'dummy_hash', 'Sarah Khan', 'collecting_officer', '555-0103', NULL, 'flexible', NULL, '2024-03-01', 'active', '2026-04-10 08:28:20'),
(4, 'feeder1', 'dummy_hash', 'Mike Chen', 'feeder', '555-0104', NULL, 'morning', 'Kitchen', '2024-01-20', 'active', '2026-04-10 08:28:20'),
(5, 'iot1', 'dummy_hash', 'Alex Rivera', 'iot_tech', '555-0105', NULL, 'rotating', 'Server Room', '2024-02-10', 'active', '2026-04-10 08:28:20'),
(6, 'supervisor1', 'dummy_hash', 'Laura Smith', 'supervisor', '555-0106', NULL, 'day', 'Admin', '2023-12-01', 'active', '2026-04-10 08:28:20'),
(7, 'vet1', 'dummy_hash', 'Dr. James Wilson', 'veterinarian', '555-0107', NULL, 'day', 'Clinic', '2024-01-05', 'active', '2026-04-10 08:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(200) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `due_date` date NOT NULL,
  `status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `completion_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`task_id`, `task_name`, `assigned_to`, `assigned_by`, `due_date`, `status`, `completion_notes`, `created_at`) VALUES
(1, 'Deep clean Arid Zone E1', 2, 6, '2026-04-10', 'In Progress', NULL, '2026-04-10 08:28:20'),
(2, 'Medical check for tortoise MIC-003', 2, 6, '2026-04-11', 'Pending', NULL, '2026-04-10 08:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `tortoises`
--

CREATE TABLE `tortoises` (
  `tortoise_id` int(11) NOT NULL,
  `microchip_id` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `species_id` int(11) NOT NULL,
  `sex` enum('Male','Female','Unknown') DEFAULT 'Unknown',
  `date_of_birth` date DEFAULT NULL,
  `estimated_age_years` int(11) DEFAULT NULL,
  `weight_grams` decimal(8,2) DEFAULT NULL,
  `health_status` enum('Healthy','Under observation','Recovering','Critical','Minor injury') DEFAULT 'Healthy',
  `enclosure_id` int(11) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `acquisition_source` enum('Wild','Rescue','Donation','Bred in captivity') DEFAULT 'Bred in captivity',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tortoises`
--

INSERT INTO `tortoises` (`tortoise_id`, `microchip_id`, `name`, `species_id`, `sex`, `date_of_birth`, `estimated_age_years`, `weight_grams`, `health_status`, `enclosure_id`, `acquisition_date`, `acquisition_source`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'MIC-001', 'Thor', 2, 'Male', NULL, 8, 45000.00, 'Healthy', 1, NULL, 'Donation', NULL, '2026-04-10 08:28:20', '2026-04-10 08:28:20'),
(2, 'MIC-002', 'Shelly', 1, 'Female', NULL, 12, 120000.00, 'Healthy', 2, NULL, 'Rescue', NULL, '2026-04-10 08:28:20', '2026-04-10 08:28:20'),
(3, 'MIC-003', 'Groot', 2, 'Male', NULL, 7, 38000.00, 'Under observation', 1, NULL, 'Wild', NULL, '2026-04-10 08:28:20', '2026-04-10 08:28:20'),
(4, 'MIC-004', 'Tess', 1, 'Female', NULL, 15, 135000.00, 'Healthy', 2, NULL, 'Donation', NULL, '2026-04-10 08:28:20', '2026-04-10 08:28:20'),
(5, 'MIC-099', 'New Rescue', 3, 'Unknown', NULL, 4, 12500.00, '', NULL, NULL, 'Rescue', NULL, '2026-04-10 08:28:20', '2026-04-10 08:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `transport_logs`
--

CREATE TABLE `transport_logs` (
  `transport_id` int(11) NOT NULL,
  `tortoise_id` int(11) NOT NULL,
  `vehicle_id` varchar(50) DEFAULT NULL,
  `from_location` varchar(200) DEFAULT NULL,
  `to_location` varchar(200) DEFAULT NULL,
  `transport_date` date NOT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Delayed') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_logs`
--

INSERT INTO `transport_logs` (`transport_id`, `tortoise_id`, `vehicle_id`, `from_location`, `to_location`, `transport_date`, `status`, `notes`) VALUES
(1, 5, 'Truck-02', 'Dhaka', 'Center', '2026-04-10', 'Completed', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `water_quality_logs`
--

CREATE TABLE `water_quality_logs` (
  `log_id` int(11) NOT NULL,
  `source` varchar(100) NOT NULL,
  `ph` decimal(3,1) DEFAULT NULL,
  `ammonia_ppm` decimal(5,3) DEFAULT NULL,
  `nitrate_ppm` decimal(5,2) DEFAULT NULL,
  `turbidity` varchar(20) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `measured_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `breeding_pairs`
--
ALTER TABLE `breeding_pairs`
  ADD PRIMARY KEY (`pair_id`),
  ADD UNIQUE KEY `pair_code` (`pair_code`),
  ADD KEY `male_tortoise_id` (`male_tortoise_id`),
  ADD KEY `female_tortoise_id` (`female_tortoise_id`);

--
-- Indexes for table `collections`
--
ALTER TABLE `collections`
  ADD PRIMARY KEY (`collection_id`),
  ADD UNIQUE KEY `tortoise_id` (`tortoise_id`),
  ADD KEY `collected_by` (`collected_by`);

--
-- Indexes for table `dietary_items`
--
ALTER TABLE `dietary_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `for_species_id` (`for_species_id`);

--
-- Indexes for table `enclosures`
--
ALTER TABLE `enclosures`
  ADD PRIMARY KEY (`enclosure_id`),
  ADD UNIQUE KEY `enclosure_code` (`enclosure_code`);

--
-- Indexes for table `feeding_schedules`
--
ALTER TABLE `feeding_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `feeder_id` (`feeder_id`),
  ADD KEY `idx_feeding_tortoise` (`tortoise_id`);

--
-- Indexes for table `health_assessments`
--
ALTER TABLE `health_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `vet_id` (`vet_id`),
  ADD KEY `idx_health_tortoise` (`tortoise_id`);

--
-- Indexes for table `incubation_readings`
--
ALTER TABLE `incubation_readings`
  ADD PRIMARY KEY (`reading_id`),
  ADD KEY `incubator_id` (`incubator_id`),
  ADD KEY `nest_id` (`nest_id`);

--
-- Indexes for table `incubators`
--
ALTER TABLE `incubators`
  ADD PRIMARY KEY (`incubator_id`),
  ADD UNIQUE KEY `incubator_code` (`incubator_code`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `managed_by` (`managed_by`);

--
-- Indexes for table `iot_alerts`
--
ALTER TABLE `iot_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `acknowledged_by` (`acknowledged_by`);

--
-- Indexes for table `iot_devices`
--
ALTER TABLE `iot_devices`
  ADD PRIMARY KEY (`device_id`),
  ADD UNIQUE KEY `device_code` (`device_code`);

--
-- Indexes for table `iot_readings`
--
ALTER TABLE `iot_readings`
  ADD PRIMARY KEY (`reading_id`),
  ADD KEY `idx_iot_device` (`device_id`);

--
-- Indexes for table `nests`
--
ALTER TABLE `nests`
  ADD PRIMARY KEY (`nest_id`),
  ADD UNIQUE KEY `nest_code` (`nest_code`),
  ADD KEY `incubator_id` (`incubator_id`),
  ADD KEY `idx_nest_pair` (`pair_id`);

--
-- Indexes for table `observations`
--
ALTER TABLE `observations`
  ADD PRIMARY KEY (`observation_id`),
  ADD KEY `tortoise_id` (`tortoise_id`),
  ADD KEY `enclosure_id` (`enclosure_id`),
  ADD KEY `observer_id` (`observer_id`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `special_dietary_needs`
--
ALTER TABLE `special_dietary_needs`
  ADD PRIMARY KEY (`need_id`),
  ADD KEY `tortoise_id` (`tortoise_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `species`
--
ALTER TABLE `species`
  ADD PRIMARY KEY (`species_id`),
  ADD UNIQUE KEY `scientific_name` (`scientific_name`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_tasks_assigned` (`assigned_to`);

--
-- Indexes for table `tortoises`
--
ALTER TABLE `tortoises`
  ADD PRIMARY KEY (`tortoise_id`),
  ADD UNIQUE KEY `microchip_id` (`microchip_id`),
  ADD KEY `idx_tortoise_species` (`species_id`),
  ADD KEY `idx_tortoise_enclosure` (`enclosure_id`);

--
-- Indexes for table `transport_logs`
--
ALTER TABLE `transport_logs`
  ADD PRIMARY KEY (`transport_id`),
  ADD KEY `idx_transport_tortoise` (`tortoise_id`);

--
-- Indexes for table `water_quality_logs`
--
ALTER TABLE `water_quality_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `measured_by` (`measured_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `breeding_pairs`
--
ALTER TABLE `breeding_pairs`
  MODIFY `pair_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `collections`
--
ALTER TABLE `collections`
  MODIFY `collection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dietary_items`
--
ALTER TABLE `dietary_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enclosures`
--
ALTER TABLE `enclosures`
  MODIFY `enclosure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feeding_schedules`
--
ALTER TABLE `feeding_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `health_assessments`
--
ALTER TABLE `health_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `incubation_readings`
--
ALTER TABLE `incubation_readings`
  MODIFY `reading_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incubators`
--
ALTER TABLE `incubators`
  MODIFY `incubator_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `iot_alerts`
--
ALTER TABLE `iot_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iot_devices`
--
ALTER TABLE `iot_devices`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `iot_readings`
--
ALTER TABLE `iot_readings`
  MODIFY `reading_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nests`
--
ALTER TABLE `nests`
  MODIFY `nest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `observations`
--
ALTER TABLE `observations`
  MODIFY `observation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `special_dietary_needs`
--
ALTER TABLE `special_dietary_needs`
  MODIFY `need_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `species`
--
ALTER TABLE `species`
  MODIFY `species_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tortoises`
--
ALTER TABLE `tortoises`
  MODIFY `tortoise_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transport_logs`
--
ALTER TABLE `transport_logs`
  MODIFY `transport_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `water_quality_logs`
--
ALTER TABLE `water_quality_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `breeding_pairs`
--
ALTER TABLE `breeding_pairs`
  ADD CONSTRAINT `breeding_pairs_ibfk_1` FOREIGN KEY (`male_tortoise_id`) REFERENCES `tortoises` (`tortoise_id`),
  ADD CONSTRAINT `breeding_pairs_ibfk_2` FOREIGN KEY (`female_tortoise_id`) REFERENCES `tortoises` (`tortoise_id`);

--
-- Constraints for table `collections`
--
ALTER TABLE `collections`
  ADD CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `collections_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `dietary_items`
--
ALTER TABLE `dietary_items`
  ADD CONSTRAINT `dietary_items_ibfk_1` FOREIGN KEY (`for_species_id`) REFERENCES `species` (`species_id`) ON DELETE SET NULL;

--
-- Constraints for table `feeding_schedules`
--
ALTER TABLE `feeding_schedules`
  ADD CONSTRAINT `feeding_schedules_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feeding_schedules_ibfk_2` FOREIGN KEY (`feeder_id`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `health_assessments`
--
ALTER TABLE `health_assessments`
  ADD CONSTRAINT `health_assessments_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `health_assessments_ibfk_2` FOREIGN KEY (`vet_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `incubation_readings`
--
ALTER TABLE `incubation_readings`
  ADD CONSTRAINT `incubation_readings_ibfk_1` FOREIGN KEY (`incubator_id`) REFERENCES `incubators` (`incubator_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incubation_readings_ibfk_2` FOREIGN KEY (`nest_id`) REFERENCES `nests` (`nest_id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`managed_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `iot_alerts`
--
ALTER TABLE `iot_alerts`
  ADD CONSTRAINT `iot_alerts_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `iot_devices` (`device_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `iot_alerts_ibfk_2` FOREIGN KEY (`acknowledged_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `iot_readings`
--
ALTER TABLE `iot_readings`
  ADD CONSTRAINT `iot_readings_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `iot_devices` (`device_id`) ON DELETE CASCADE;

--
-- Constraints for table `nests`
--
ALTER TABLE `nests`
  ADD CONSTRAINT `nests_ibfk_1` FOREIGN KEY (`pair_id`) REFERENCES `breeding_pairs` (`pair_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nests_ibfk_2` FOREIGN KEY (`incubator_id`) REFERENCES `incubators` (`incubator_id`) ON DELETE SET NULL;

--
-- Constraints for table `observations`
--
ALTER TABLE `observations`
  ADD CONSTRAINT `observations_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `observations_ibfk_2` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `observations_ibfk_3` FOREIGN KEY (`observer_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD CONSTRAINT `restock_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `special_dietary_needs`
--
ALTER TABLE `special_dietary_needs`
  ADD CONSTRAINT `special_dietary_needs_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `special_dietary_needs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;

--
-- Constraints for table `tortoises`
--
ALTER TABLE `tortoises`
  ADD CONSTRAINT `tortoises_ibfk_1` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`),
  ADD CONSTRAINT `tortoises_ibfk_2` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON DELETE SET NULL;

--
-- Constraints for table `transport_logs`
--
ALTER TABLE `transport_logs`
  ADD CONSTRAINT `transport_logs_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE;

--
-- Constraints for table `water_quality_logs`
--
ALTER TABLE `water_quality_logs`
  ADD CONSTRAINT `water_quality_logs_ibfk_1` FOREIGN KEY (`measured_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
