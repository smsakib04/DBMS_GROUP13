
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;
 
-- ------------------------------------------------------------
--  Create the database (safe: won't overwrite if it exists)
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `tortoise_conservation`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
 
USE `tortoise_conservation`;
 
-- ============================================================
--  TABLES  (drop order respects FK deps)
-- ============================================================
 
DROP TABLE IF EXISTS `water_quality_logs`;
DROP TABLE IF EXISTS `transport_logs`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `special_dietary_needs`;
DROP TABLE IF EXISTS `restock_requests`;
DROP TABLE IF EXISTS `observations`;
DROP TABLE IF EXISTS `iot_readings`;
DROP TABLE IF EXISTS `iot_alerts`;
DROP TABLE IF EXISTS `incubation_readings`;
DROP TABLE IF EXISTS `feeding_schedules`;
DROP TABLE IF EXISTS `health_assessments`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `nests`;
DROP TABLE IF EXISTS `breeding_pairs`;
DROP TABLE IF EXISTS `dietary_items`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `tortoises`;
DROP TABLE IF EXISTS `enclosures`;
DROP TABLE IF EXISTS `species`;
DROP TABLE IF EXISTS `iot_devices`;
DROP TABLE IF EXISTS `incubators`;
DROP TABLE IF EXISTS `staff`;
 
-- ------------------------------------------------------------
--  staff
-- ------------------------------------------------------------
CREATE TABLE `staff` (
  `staff_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `username`      varchar(50)  NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name`     varchar(100) NOT NULL,
  `role`          enum('breeding_officer','caretaker','collecting_officer','feeder',
                       'iot_tech','supervisor','veterinarian') NOT NULL,
  `phone`         varchar(20)  DEFAULT NULL,
  `email`         varchar(100) DEFAULT NULL,
  `shift`         varchar(20)  DEFAULT NULL,
  `assigned_zone` varchar(50)  DEFAULT NULL,
  `hire_date`     date         DEFAULT NULL,
  `status`        enum('active','inactive') DEFAULT 'active',
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `staff` VALUES
(1,'breeding1','dummy_hash','Dr. Emily Reed','breeding_officer','555-0101',NULL,'day','Hatchery','2024-01-10','active',NOW()),
(2,'caretaker1','dummy_hash','John Miller','caretaker','555-0102',NULL,'morning','Zone A','2024-02-15','active',NOW()),
(3,'collector1','dummy_hash','Sarah Khan','collecting_officer','555-0103',NULL,'flexible',NULL,'2024-03-01','active',NOW()),
(4,'feeder1','dummy_hash','Mike Chen','feeder','555-0104',NULL,'morning','Kitchen','2024-01-20','active',NOW()),
(5,'iot1','dummy_hash','Alex Rivera','iot_tech','555-0105',NULL,'rotating','Server Room','2024-02-10','active',NOW()),
(6,'supervisor1','dummy_hash','Laura Smith','supervisor','555-0106',NULL,'day','Admin','2023-12-01','active',NOW()),
(7,'vet1','dummy_hash','Dr. James Wilson','veterinarian','555-0107',NULL,'day','Clinic','2024-01-05','active',NOW());
 
-- ------------------------------------------------------------
--  species
-- ------------------------------------------------------------
CREATE TABLE `species` (
  `species_id`          int(11)      NOT NULL AUTO_INCREMENT,
  `common_name`         varchar(100) NOT NULL,
  `scientific_name`     varchar(150) DEFAULT NULL,
  `iucn_status`         enum('Critically Endangered','Endangered','Vulnerable',
                             'Near Threatened','Least Concern','Data Deficient','Not Assessed') DEFAULT 'Not Assessed',
  `avg_lifespan_years`  int(11)      DEFAULT NULL,
  `avg_weight_kg`       decimal(5,2) DEFAULT NULL,
  `conservation_notes`  text         DEFAULT NULL,
  PRIMARY KEY (`species_id`),
  UNIQUE KEY `scientific_name` (`scientific_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `species` VALUES
(1,'Aldabra Giant Tortoise','Aldabrachelys gigantea','Vulnerable',150,250.00,NULL),
(2,'African Spurred Tortoise','Centrochelys sulcata','Endangered',80,100.00,NULL),
(3,'Leopard Tortoise','Stigmochelys pardalis','Least Concern',50,40.00,NULL),
(4,'Galapagos Tortoise','Chelonoidis niger','Vulnerable',100,300.00,NULL),
(5,'Indian Star Tortoise','Geochelone elegans','Vulnerable',60,15.00,NULL);
 
-- ------------------------------------------------------------
--  enclosures
-- ------------------------------------------------------------
CREATE TABLE `enclosures` (
  `enclosure_id`       int(11)      NOT NULL AUTO_INCREMENT,
  `enclosure_code`     varchar(20)  NOT NULL,
  `enclosure_name`     varchar(100) NOT NULL,
  `habitat_type`       enum('Desert','Rainforest','Grassland','Wetland','Mediterranean','Savanna') NOT NULL,
  `size_sq_meters`     decimal(8,2) DEFAULT NULL,
  `capacity`           int(11)      DEFAULT 1,
  `current_occupancy`  int(11)      DEFAULT 0,
  `last_cleaning`      date         DEFAULT NULL,
  `next_maintenance`   date         DEFAULT NULL,
  `status`             enum('Active','Maintenance','Quarantine') DEFAULT 'Active',
  PRIMARY KEY (`enclosure_id`),
  UNIQUE KEY `enclosure_code` (`enclosure_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `enclosures` VALUES
(1,'E-Arid-01','Arid Zone E1','Desert',45.00,4,2,'2025-03-24','2025-04-03','Active'),
(2,'E-Trop-03','Rainforest E3','Rainforest',78.00,6,3,'2025-03-25','2025-04-01','Active'),
(3,'E-Grass-07','Savanna E7','Savanna',60.00,5,2,'2025-03-26','2025-04-02','Active');
 
-- ------------------------------------------------------------
--  tortoises
-- ------------------------------------------------------------
CREATE TABLE `tortoises` (
  `tortoise_id`        int(11)      NOT NULL AUTO_INCREMENT,
  `microchip_id`       varchar(50)  DEFAULT NULL,
  `name`               varchar(100) DEFAULT NULL,
  `species_id`         int(11)      NOT NULL,
  `sex`                enum('Male','Female','Unknown') DEFAULT 'Unknown',
  `date_of_birth`      date         DEFAULT NULL,
  `estimated_age_years` int(11)     DEFAULT NULL,
  `weight_grams`       decimal(8,2) DEFAULT NULL,
  `health_status`      enum('Healthy','Under observation','Recovering','Critical','Minor injury') DEFAULT 'Healthy',
  `enclosure_id`       int(11)      DEFAULT NULL,
  `acquisition_date`   date         DEFAULT NULL,
  `acquisition_source` enum('Wild','Rescue','Donation','Bred in captivity') DEFAULT 'Bred in captivity',
  `notes`              text         DEFAULT NULL,
  `created_at`         timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`         timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tortoise_id`),
  UNIQUE KEY `microchip_id` (`microchip_id`),
  KEY `idx_tortoise_species` (`species_id`),
  KEY `idx_tortoise_enclosure` (`enclosure_id`),
  CONSTRAINT `tortoises_ibfk_1` FOREIGN KEY (`species_id`)   REFERENCES `species`    (`species_id`),
  CONSTRAINT `tortoises_ibfk_2` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `tortoises` (`tortoise_id`,`microchip_id`,`name`,`species_id`,`sex`,`estimated_age_years`,`weight_grams`,`health_status`,`enclosure_id`,`acquisition_source`) VALUES
(1,'MIC-001','Thor',  2,'Male',  8, 45000.00,'Healthy',        1,'Donation'),
(2,'MIC-002','Shelly',1,'Female',12,120000.00,'Healthy',        2,'Rescue'),
(3,'MIC-003','Groot', 2,'Male',  7, 38000.00,'Under observation',1,'Wild'),
(4,'MIC-004','Tess',  1,'Female',15,135000.00,'Healthy',        2,'Donation'),
(5,'MIC-099','New Rescue',3,'Unknown',4,12500.00,'Healthy',     NULL,'Rescue');
 
-- ------------------------------------------------------------
--  health_assessments  ← FIXED: full schema matching all PHP
-- ------------------------------------------------------------
CREATE TABLE `health_assessments` (
  `assessment_id`     int(11)      NOT NULL AUTO_INCREMENT,
  `assessment_code`   varchar(50)  NOT NULL,
  `assessment_date`   date         NOT NULL,
  `tortoise_id`       int(11)      NOT NULL,
  `vet_id`            int(11)      DEFAULT NULL,
  `health_condition`  varchar(100) DEFAULT NULL,
  `diagnosis`         text         DEFAULT NULL,
  `treatment`         text         DEFAULT NULL,
  `remarks`           text         DEFAULT NULL,
  `next_checkup_date` date         DEFAULT NULL,
  `created_at`        timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assessment_id`),
  KEY `vet_id`               (`vet_id`),
  KEY `idx_health_tortoise`  (`tortoise_id`),
  CONSTRAINT `health_assessments_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  CONSTRAINT `health_assessments_ibfk_2` FOREIGN KEY (`vet_id`)      REFERENCES `staff`     (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- Sample row matching existing data
INSERT INTO `health_assessments`
  (`assessment_id`,`assessment_code`,`assessment_date`,`tortoise_id`,`vet_id`,`health_condition`,`diagnosis`,`treatment`,`remarks`)
VALUES
  (1,'ASS-2026-001','2026-04-10',3,7,'Under observation','Minor respiratory infection','Antibiotics for 5 days','Isolate and monitor');
 
-- ------------------------------------------------------------
--  breeding_pairs
-- ------------------------------------------------------------
CREATE TABLE `breeding_pairs` (
  `pair_id`            int(11)     NOT NULL AUTO_INCREMENT,
  `pair_code`          varchar(20) DEFAULT NULL,
  `male_tortoise_id`   int(11)     NOT NULL,
  `female_tortoise_id` int(11)     NOT NULL,
  `pairing_date`       date        NOT NULL,
  `status`             enum('paired','courting','incubating','hatched','separated') DEFAULT 'paired',
  `notes`              text        DEFAULT NULL,
  PRIMARY KEY (`pair_id`),
  UNIQUE KEY `pair_code` (`pair_code`),
  KEY `male_tortoise_id`   (`male_tortoise_id`),
  KEY `female_tortoise_id` (`female_tortoise_id`),
  CONSTRAINT `breeding_pairs_ibfk_1` FOREIGN KEY (`male_tortoise_id`)   REFERENCES `tortoises` (`tortoise_id`),
  CONSTRAINT `breeding_pairs_ibfk_2` FOREIGN KEY (`female_tortoise_id`) REFERENCES `tortoises` (`tortoise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `breeding_pairs` VALUES
(1,'BP-021',1,2,'2025-01-10','incubating',NULL),
(2,'BP-022',3,4,'2025-02-03','incubating',NULL);
 
-- ------------------------------------------------------------
--  incubators
-- ------------------------------------------------------------
CREATE TABLE `incubators` (
  `incubator_id`   int(11)     NOT NULL AUTO_INCREMENT,
  `incubator_code` varchar(20) NOT NULL,
  `location`       varchar(100) DEFAULT NULL,
  `status`         enum('active','maintenance','offline') DEFAULT 'active',
  `notes`          text         DEFAULT NULL,
  PRIMARY KEY (`incubator_id`),
  UNIQUE KEY `incubator_code` (`incubator_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `incubators` VALUES (1,'INC-01','Hatchery Room A','active',NULL),(2,'INC-02','Hatchery Room B','active',NULL);
 
-- ------------------------------------------------------------
--  nests
-- ------------------------------------------------------------
CREATE TABLE `nests` (
  `nest_id`               int(11)      NOT NULL AUTO_INCREMENT,
  `nest_code`             varchar(20)  DEFAULT NULL,
  `pair_id`               int(11)      NOT NULL,
  `nesting_date`          date         NOT NULL,
  `egg_count`             int(11)      NOT NULL,
  `fertile_eggs`          int(11)      DEFAULT NULL,
  `incubator_id`          int(11)      DEFAULT NULL,
  `estimated_hatch_date`  date         DEFAULT NULL,
  `actual_hatch_date`     date         DEFAULT NULL,
  `hatch_success_rate`    decimal(5,2) DEFAULT NULL,
  `notes`                 text         DEFAULT NULL,
  PRIMARY KEY (`nest_id`),
  UNIQUE KEY `nest_code` (`nest_code`),
  KEY `incubator_id`  (`incubator_id`),
  KEY `idx_nest_pair` (`pair_id`),
  CONSTRAINT `nests_ibfk_1` FOREIGN KEY (`pair_id`)       REFERENCES `breeding_pairs` (`pair_id`)   ON DELETE CASCADE,
  CONSTRAINT `nests_ibfk_2` FOREIGN KEY (`incubator_id`)  REFERENCES `incubators`     (`incubator_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `nests` VALUES
(1,'Nest-AA4',1,'2025-01-12',8,7,1,'2025-04-14',NULL,NULL,NULL),
(2,'Nest-BG2',2,'2025-02-07',12,10,2,'2025-06-02',NULL,NULL,NULL);
 
-- ------------------------------------------------------------
--  collections
-- ------------------------------------------------------------
CREATE TABLE `collections` (
  `collection_id`  int(11)      NOT NULL AUTO_INCREMENT,
  `tortoise_id`    int(11)      DEFAULT NULL,
  `collection_date` date        NOT NULL,
  `source_type`    enum('Wild','Rescue','Donation') NOT NULL,
  `location`       varchar(200) DEFAULT NULL,
  `initial_health` enum('Healthy','Weak','Injured') NOT NULL,
  `notes`          text         DEFAULT NULL,
  `collected_by`   int(11)      DEFAULT NULL,
  PRIMARY KEY (`collection_id`),
  UNIQUE KEY `tortoise_id` (`tortoise_id`),
  KEY `collected_by` (`collected_by`),
  CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`tortoise_id`)  REFERENCES `tortoises` (`tortoise_id`) ON DELETE SET NULL,
  CONSTRAINT `collections_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `staff`     (`staff_id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `collections` VALUES (1,5,'2026-04-10','Rescue','Dhaka','Weak','Found near construction site',3);
 
-- ------------------------------------------------------------
--  feeding_schedules
-- ------------------------------------------------------------
CREATE TABLE `feeding_schedules` (
  `schedule_id`    int(11)      NOT NULL AUTO_INCREMENT,
  `tortoise_id`    int(11)      NOT NULL,
  `feeding_time`   time         NOT NULL,
  `food_type`      varchar(100) NOT NULL,
  `amount_grams`   decimal(6,2) DEFAULT NULL,
  `is_done`        tinyint(1)   DEFAULT 0,
  `scheduled_date` date         NOT NULL,
  `feeder_id`      int(11)      DEFAULT NULL,
  `notes`          text         DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `feeder_id`           (`feeder_id`),
  KEY `idx_feeding_tortoise` (`tortoise_id`),
  CONSTRAINT `feeding_schedules_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  CONSTRAINT `feeding_schedules_ibfk_2` FOREIGN KEY (`feeder_id`)   REFERENCES `staff`     (`staff_id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `feeding_schedules` VALUES
(1,1,'08:00:00','Vegetables',500.00,0,'2026-04-10',4,NULL),
(2,2,'10:30:00','Mixed Greens',800.00,0,'2026-04-10',4,NULL),
(3,3,'13:00:00','Fruits & Vegetables',600.00,0,'2026-04-10',4,NULL),
(4,4,'16:00:00','Hay & Supplements',700.00,0,'2026-04-10',4,NULL);
 
-- ------------------------------------------------------------
--  iot_devices
-- ------------------------------------------------------------
CREATE TABLE `iot_devices` (
  `device_id`     int(11)     NOT NULL AUTO_INCREMENT,
  `device_code`   varchar(50) NOT NULL,
  `device_type`   enum('Temp/Humidity','Water quality','Incubator','Light','CO2','Other') NOT NULL,
  `location`      varchar(100) DEFAULT NULL,
  `battery_level` int(11)      DEFAULT NULL CHECK (`battery_level` BETWEEN 0 AND 100),
  `last_ping`     timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status`        enum('online','offline','low battery','maintenance') DEFAULT 'online',
  `installed_date` date        DEFAULT NULL,
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `device_code` (`device_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `iot_devices` VALUES
(1,'IOT-TH-012','Temp/Humidity','Enclosure A3',87,NOW(),'online',NULL),
(2,'IOT-WQ-003','Water quality','Marsh pond',91,NOW(),'online',NULL),
(3,'IOT-INC-09','Incubator','Hatchery #2',74,NOW(),'online',NULL);
 
-- ------------------------------------------------------------
--  iot_readings
-- ------------------------------------------------------------
CREATE TABLE `iot_readings` (
  `reading_id`       int(11)      NOT NULL AUTO_INCREMENT,
  `device_id`        int(11)      NOT NULL,
  `reading_time`     timestamp    NOT NULL DEFAULT current_timestamp(),
  `temperature_c`    decimal(4,2) DEFAULT NULL,
  `humidity_percent` int(11)      DEFAULT NULL,
  `uv_index`         decimal(3,1) DEFAULT NULL,
  `co2_ppm`          int(11)      DEFAULT NULL,
  `water_ph`         decimal(3,1) DEFAULT NULL,
  `ammonia_ppm`      decimal(5,3) DEFAULT NULL,
  `nitrate_ppm`      decimal(5,2) DEFAULT NULL,
  `turbidity`        varchar(20)  DEFAULT NULL,
  PRIMARY KEY (`reading_id`),
  KEY `idx_iot_device` (`device_id`),
  CONSTRAINT `iot_readings_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `iot_devices` (`device_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `iot_readings` (`device_id`,`temperature_c`,`humidity_percent`,`water_ph`) VALUES
(1,29.80,34,NULL),(2,NULL,NULL,7.2),(3,29.40,74,NULL);
 
-- ------------------------------------------------------------
--  iot_alerts
-- ------------------------------------------------------------
CREATE TABLE `iot_alerts` (
  `alert_id`         int(11)    NOT NULL AUTO_INCREMENT,
  `device_id`        int(11)    NOT NULL,
  `parameter`        varchar(50) DEFAULT NULL,
  `actual_value`     varchar(50) DEFAULT NULL,
  `threshold_value`  varchar(50) DEFAULT NULL,
  `severity`         enum('warning','critical','maintenance') DEFAULT 'warning',
  `alert_time`       timestamp  NOT NULL DEFAULT current_timestamp(),
  `is_acknowledged`  tinyint(1) DEFAULT 0,
  `acknowledged_by`  int(11)    DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `device_id`        (`device_id`),
  KEY `acknowledged_by`  (`acknowledged_by`),
  CONSTRAINT `iot_alerts_ibfk_1` FOREIGN KEY (`device_id`)       REFERENCES `iot_devices` (`device_id`) ON DELETE CASCADE,
  CONSTRAINT `iot_alerts_ibfk_2` FOREIGN KEY (`acknowledged_by`) REFERENCES `staff`       (`staff_id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  incubation_readings
-- ------------------------------------------------------------
CREATE TABLE `incubation_readings` (
  `reading_id`       int(11)      NOT NULL AUTO_INCREMENT,
  `incubator_id`     int(11)      NOT NULL,
  `nest_id`          int(11)      DEFAULT NULL,
  `reading_date`     date         NOT NULL,
  `day_of_incubation` int(11)     DEFAULT NULL,
  `temperature_c`    decimal(4,2) DEFAULT NULL,
  `humidity_percent` int(11)      DEFAULT NULL,
  `co2_ppm`          int(11)      DEFAULT NULL,
  `egg_turn_cycle`   varchar(50)  DEFAULT NULL,
  `notes`            text         DEFAULT NULL,
  PRIMARY KEY (`reading_id`),
  KEY `incubator_id` (`incubator_id`),
  KEY `nest_id`      (`nest_id`),
  CONSTRAINT `incubation_readings_ibfk_1` FOREIGN KEY (`incubator_id`) REFERENCES `incubators` (`incubator_id`) ON DELETE CASCADE,
  CONSTRAINT `incubation_readings_ibfk_2` FOREIGN KEY (`nest_id`)      REFERENCES `nests`      (`nest_id`)      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  inventory
-- ------------------------------------------------------------
CREATE TABLE `inventory` (
  `inventory_id` int(11)      NOT NULL AUTO_INCREMENT,
  `item_name`    varchar(100) NOT NULL,
  `category`     enum('food','medical','cleaning','equipment','other') DEFAULT 'other',
  `quantity`     decimal(10,2) DEFAULT NULL,
  `unit`         varchar(20)  DEFAULT NULL,
  `reorder_level` decimal(10,2) DEFAULT NULL,
  `supplier`     varchar(100) DEFAULT NULL,
  `last_updated` date         DEFAULT NULL,
  `managed_by`   int(11)      DEFAULT NULL,
  PRIMARY KEY (`inventory_id`),
  KEY `managed_by` (`managed_by`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`managed_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `inventory` VALUES
(1,'Calcium blocks','food',3.80,'kg',2.00,'ReptiSupply','2025-03-01',NULL),
(2,'Water Quality Kit','medical',5.00,'pieces',2.00,'Lab Supplies Inc.','2025-03-15',NULL),
(3,'Temperature Sensor','equipment',3.00,'pieces',1.00,'Tech Equip Ltd.','2025-03-10',NULL);
 
-- ------------------------------------------------------------
--  dietary_items
-- ------------------------------------------------------------
CREATE TABLE `dietary_items` (
  `item_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `item_name`    varchar(100) NOT NULL,
  `amount_grams` decimal(8,2) DEFAULT NULL,
  `for_species_id` int(11)    DEFAULT NULL,
  `notes`        text         DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `for_species_id` (`for_species_id`),
  CONSTRAINT `dietary_items_ibfk_1` FOREIGN KEY (`for_species_id`) REFERENCES `species` (`species_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  special_dietary_needs
-- ------------------------------------------------------------
CREATE TABLE `special_dietary_needs` (
  `need_id`     int(11)  NOT NULL AUTO_INCREMENT,
  `tortoise_id` int(11)  NOT NULL,
  `restriction` text     NOT NULL,
  `note`        text     DEFAULT NULL,
  `created_by`  int(11)  DEFAULT NULL,
  `created_at`  timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`need_id`),
  KEY `tortoise_id` (`tortoise_id`),
  KEY `created_by`  (`created_by`),
  CONSTRAINT `special_dietary_needs_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE,
  CONSTRAINT `special_dietary_needs_ibfk_2` FOREIGN KEY (`created_by`)  REFERENCES `staff`     (`staff_id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  observations
-- ------------------------------------------------------------
CREATE TABLE `observations` (
  `observation_id`   int(11)  NOT NULL AUTO_INCREMENT,
  `tortoise_id`      int(11)  DEFAULT NULL,
  `enclosure_id`     int(11)  DEFAULT NULL,
  `observer_id`      int(11)  NOT NULL,
  `observation_date` date     NOT NULL,
  `category`         enum('behavior','health','feeding','nesting','general') DEFAULT 'general',
  `description`      text     DEFAULT NULL,
  `created_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`observation_id`),
  KEY `tortoise_id`  (`tortoise_id`),
  KEY `enclosure_id` (`enclosure_id`),
  KEY `observer_id`  (`observer_id`),
  CONSTRAINT `observations_ibfk_1` FOREIGN KEY (`tortoise_id`)  REFERENCES `tortoises`  (`tortoise_id`)  ON DELETE SET NULL,
  CONSTRAINT `observations_ibfk_2` FOREIGN KEY (`enclosure_id`) REFERENCES `enclosures` (`enclosure_id`) ON DELETE SET NULL,
  CONSTRAINT `observations_ibfk_3` FOREIGN KEY (`observer_id`)  REFERENCES `staff`      (`staff_id`)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  restock_requests
-- ------------------------------------------------------------
CREATE TABLE `restock_requests` (
  `request_id`      int(11)      NOT NULL AUTO_INCREMENT,
  `item_name`       varchar(100) NOT NULL,
  `quantity_needed` decimal(10,2) DEFAULT NULL,
  `priority`        enum('low','medium','high','urgent') DEFAULT 'medium',
  `needed_by_date`  date         DEFAULT NULL,
  `requested_by`    int(11)      DEFAULT NULL,
  `status`          enum('pending','approved','ordered','received') DEFAULT 'pending',
  `request_date`    timestamp    NOT NULL DEFAULT current_timestamp(),
  `notes`           text         DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `requested_by` (`requested_by`),
  CONSTRAINT `restock_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- ------------------------------------------------------------
--  tasks
-- ------------------------------------------------------------
CREATE TABLE `tasks` (
  `task_id`          int(11)      NOT NULL AUTO_INCREMENT,
  `task_name`        varchar(200) NOT NULL,
  `assigned_to`      int(11)      NOT NULL,
  `assigned_by`      int(11)      DEFAULT NULL,
  `due_date`         date         NOT NULL,
  `status`           enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `completion_notes` text         DEFAULT NULL,
  `created_at`       timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`task_id`),
  KEY `assigned_by`         (`assigned_by`),
  KEY `idx_tasks_assigned`  (`assigned_to`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `tasks` VALUES
(1,'Deep clean Arid Zone E1',2,6,'2026-04-10','In Progress',NULL,NOW()),
(2,'Medical check for tortoise MIC-003',2,6,'2026-04-11','Pending',NULL,NOW());
 
-- ------------------------------------------------------------
--  transport_logs
-- ------------------------------------------------------------
CREATE TABLE `transport_logs` (
  `transport_id`  int(11)      NOT NULL AUTO_INCREMENT,
  `tortoise_id`   int(11)      NOT NULL,
  `vehicle_id`    varchar(50)  DEFAULT NULL,
  `from_location` varchar(200) DEFAULT NULL,
  `to_location`   varchar(200) DEFAULT NULL,
  `transport_date` date        NOT NULL,
  `status`        enum('Scheduled','Ongoing','Completed','Delayed') DEFAULT 'Scheduled',
  `notes`         text         DEFAULT NULL,
  PRIMARY KEY (`transport_id`),
  KEY `idx_transport_tortoise` (`tortoise_id`),
  CONSTRAINT `transport_logs_ibfk_1` FOREIGN KEY (`tortoise_id`) REFERENCES `tortoises` (`tortoise_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
INSERT INTO `transport_logs` VALUES (1,5,'Truck-02','Dhaka','Center','2026-04-10','Completed',NULL);
 
-- ------------------------------------------------------------
--  water_quality_logs
-- ------------------------------------------------------------
CREATE TABLE `water_quality_logs` (
  `log_id`       int(11)      NOT NULL AUTO_INCREMENT,
  `source`       varchar(100) NOT NULL,
  `ph`           decimal(3,1) DEFAULT NULL,
  `ammonia_ppm`  decimal(5,3) DEFAULT NULL,
  `nitrate_ppm`  decimal(5,2) DEFAULT NULL,
  `turbidity`    varchar(20)  DEFAULT NULL,
  `log_time`     timestamp    NOT NULL DEFAULT current_timestamp(),
  `measured_by`  int(11)      DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `measured_by` (`measured_by`),
  CONSTRAINT `water_quality_logs_ibfk_1` FOREIGN KEY (`measured_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
COMMIT;