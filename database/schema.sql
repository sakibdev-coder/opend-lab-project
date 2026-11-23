-- ====================================================================
-- Database: social_events_db
-- System: Social Development Events Platform (CommunityAid Dhaka)
-- Normalized: 3NF (Third Normal Form) compliant
-- Compatibility: MySQL 5.7+ / 8.0+ / MariaDB (XAMPP / phpMyAdmin)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `social_events_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `social_events_db`;

-- --------------------------------------------------------------------
-- Drop existing views and tables (respecting foreign key order)
-- --------------------------------------------------------------------
DROP VIEW IF EXISTS `vw_event_summary`;
DROP TABLE IF EXISTS `donations`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ====================================================================
-- Table 1: users
-- Stores organizers, volunteers, donors, and administrators
-- Normalized: 3NF
-- ====================================================================
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `role` ENUM('Admin', 'Organizer', 'Volunteer') NOT NULL DEFAULT 'Volunteer',
    `address` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- Table 2: categories
-- Categorization of community development activities
-- Normalized: 3NF
-- ====================================================================
CREATE TABLE `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE,
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- Table 3: events
-- Central entity for social events with location, date, and status
-- Foreign Keys: category_id -> categories, organizer_id -> users
-- Normalized: 3NF
-- ====================================================================
CREATE TABLE `events` (
    `event_id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT NOT NULL,
    `category_id` INT NOT NULL,
    `organizer_id` INT NOT NULL,
    `area` VARCHAR(100) NOT NULL,
    `exact_location` VARCHAR(255) NOT NULL,
    `event_date` DATETIME NOT NULL,
    `target_volunteers` INT NOT NULL DEFAULT 15,
    `status` ENUM('Upcoming', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Upcoming',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_events_category` 
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_events_organizer` 
        FOREIGN KEY (`organizer_id`) REFERENCES `users` (`user_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `chk_target_volunteers` 
        CHECK (`target_volunteers` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- Table 4: registrations
-- Associative table tracking volunteer enrollment per event (M:N)
-- Foreign Keys: event_id -> events, user_id -> users
-- Constraints: Unique composite key to prevent double registration
-- Normalized: 3NF
-- ====================================================================
CREATE TABLE `registrations` (
    `registration_id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role_type` ENUM('General Volunteer', 'Event Coordinator', 'First Aid / Medic', 'Logistics & Distribution') NOT NULL DEFAULT 'General Volunteer',
    `status` ENUM('Registered', 'Confirmed', 'Attended', 'Cancelled') NOT NULL DEFAULT 'Registered',
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` VARCHAR(255) NULL,
    CONSTRAINT `fk_registrations_event` 
        FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_registrations_user` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `uq_event_user` 
        UNIQUE (`event_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- Table 5: donations
-- Tracks material and financial supplies donated for specific events
-- Foreign Keys: event_id -> events
-- Normalized: 3NF
-- ====================================================================
CREATE TABLE `donations` (
    `donation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `donor_name` VARCHAR(100) NOT NULL,
    `donor_contact` VARCHAR(100) NOT NULL,
    `item_type` VARCHAR(100) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `unit` VARCHAR(30) NOT NULL,
    `donated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_donations_event` 
        FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `chk_quantity_positive` 
        CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SQL VIEW: vw_event_summary
-- Aggregates event details with category, organizer, registered volunteers count,
-- confirmed volunteers count, and total donation records.
-- ====================================================================
CREATE OR REPLACE VIEW `vw_event_summary` AS
SELECT 
    e.event_id,
    e.title,
    e.area,
    e.exact_location,
    e.event_date,
    e.status,
    e.target_volunteers,
    c.category_id,
    c.category_name,
    u.user_id AS organizer_id,
    u.full_name AS organizer_name,
    u.phone AS organizer_phone,
    COUNT(DISTINCT r.registration_id) AS total_volunteers,
    COUNT(DISTINCT CASE WHEN r.status IN ('Confirmed', 'Attended') THEN r.registration_id END) AS confirmed_volunteers,
    COUNT(DISTINCT d.donation_id) AS total_donations
FROM `events` e
INNER JOIN `categories` c ON e.category_id = c.category_id
INNER JOIN `users` u ON e.organizer_id = u.user_id
LEFT JOIN `registrations` r ON e.event_id = r.event_id
LEFT JOIN `donations` d ON e.event_id = d.event_id
GROUP BY 
    e.event_id, e.title, e.area, e.exact_location, e.event_date, 
    e.status, e.target_volunteers, c.category_id, c.category_name, 
    u.user_id, u.full_name, u.phone;

-- ====================================================================
-- SEED SAMPLE DATA
-- Realistic Dhaka community service and social development events
-- ====================================================================

-- 1. Seed Users (Organizers & Volunteers)
INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `role`, `address`) VALUES
(1, 'Tanvir Ahmed', 'tanvir.ahmed@example.com', '01711000001', 'Organizer', 'Mirpur 10, Dhaka'),
(2, 'Sadia Rahman', 'sadia.rahman@example.com', '01812000002', 'Organizer', 'Mohammadpur, Dhaka'),
(3, 'Rafiqul Islam', 'rafiq.islam@example.com', '01913000003', 'Organizer', 'Dhanmondi, Dhaka'),
(4, 'Farhana Akter', 'farhana.akter@example.com', '01714000004', 'Volunteer', 'Uttara Sector 7, Dhaka'),
(5, 'Mahmudul Hasan', 'mahmud.hasan@example.com', '01615000005', 'Volunteer', 'Old Dhaka, Dhaka'),
(6, 'Tasnim Jannat', 'tasnim.jannat@example.com', '01516000006', 'Volunteer', 'Banani, Dhaka'),
(7, 'Nusrat Jahan', 'nusrat.jahan@example.com', '01717000007', 'Volunteer', 'Mirpur 2, Dhaka'),
(8, 'Kabir Hossain', 'kabir.hossain@example.com', '01818000008', 'Volunteer', 'Mohammadpur Housing, Dhaka');

-- 2. Seed Categories
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Winter Relief & Warm Clothes', 'Distributing warm clothes, sweaters, and blankets during winter'),
(2, 'Healthcare & Medical Camps', 'Free health checkups, blood donation, and medicine distribution'),
(3, 'Food Security & Hunger Relief', 'Cooked meals and monthly grocery packets for low-income families'),
(4, 'Environment & Urban Greening', 'Tree plantation drives, waste cleaning, and air pollution awareness'),
(5, 'Education & Child Welfare', 'Free books, stationery, and tutoring for underprivileged street children');

-- 3. Seed Events
INSERT INTO `events` (`event_id`, `title`, `description`, `category_id`, `organizer_id`, `area`, `exact_location`, `event_date`, `target_volunteers`, `status`) VALUES
(1, 'Winter Clothes Distribution – Mirpur 10', 'Collecting and distributing winter jackets, sweaters, and shawls to rickshaw pullers and open-air street sleepers in Mirpur area.', 1, 1, 'Mirpur 10, Dhaka', 'Near Mirpur 10 Roundabout & Stadium Gate', '2026-12-05 09:00:00', 20, 'Upcoming'),
(2, 'Blanket Distribution for the Needy – Mohammadpur', 'Late evening emergency blanket distribution directly to sidewalk dwellers and elderly homeless individuals in Mohammadpur and Geneva Camp perimeter.', 1, 2, 'Mohammadpur, Dhaka', 'Town Hall Ground & Mohammadpur Bus Stand', '2026-12-10 20:30:00', 15, 'Upcoming'),
(3, 'Free Medical Camp & Blood Typing – Korail', 'Free consultation by MBBS volunteer doctors, basic diabetes screening, blood pressure monitoring, and essential medicine giveaway for slum residents.', 2, 3, 'Banani, Dhaka', 'Korail Community Primary School Yard', '2026-12-18 10:00:00', 25, 'Upcoming'),
(4, 'Dhanmondi Lake Tree Plantation & Cleanliness Drive', 'Planting 300 native fruit and shade saplings along Dhanmondi Lake walkway and collecting plastic waste with community youth.', 4, 3, 'Dhanmondi, Dhaka', 'Dhanmondi Lake Walkway (Rabindra Sarobar)', '2026-11-28 07:30:00', 30, 'Ongoing'),
(5, 'Warm Food & Dry Ration Packets – Old Dhaka', 'Serving hot khichuri meals and handing over weekly dry food packets to daily wage boatmen and laborers around Buriganga riverbank.', 3, 1, 'Old Dhaka, Dhaka', 'Sadarghat Launch Terminal Ghat 3', '2026-11-20 12:00:00', 18, 'Completed'),
(6, 'Street Children Education Kit Distribution – Farmgate', 'Handing out school bags, notebooks, pencils, and storybooks to street-connected children along with an inspiring storytelling hour.', 5, 2, 'Farmgate, Dhaka', 'Farmgate Park Corner & Overbridge Steps', '2026-12-25 15:00:00', 12, 'Upcoming');

-- 4. Seed Volunteer Registrations
INSERT INTO `registrations` (`registration_id`, `event_id`, `user_id`, `role_type`, `status`, `notes`) VALUES
(1, 1, 4, 'General Volunteer', 'Confirmed', 'Can assist sorting clothes by size'),
(2, 1, 5, 'Logistics & Distribution', 'Confirmed', 'Has a pickup van for 2 hours'),
(3, 1, 6, 'First Aid / Medic', 'Registered', 'First-aid kit ready'),
(4, 2, 7, 'General Volunteer', 'Confirmed', 'Available for night shift distribution'),
(5, 2, 8, 'Event Coordinator', 'Confirmed', 'Will supervise spot security and queues'),
(6, 3, 4, 'First Aid / Medic', 'Confirmed', 'Nursing student at Dhaka Medical College'),
(7, 3, 6, 'General Volunteer', 'Registered', 'Assisting doctor queues and prescription tokens'),
(8, 4, 5, 'Logistics & Distribution', 'Attended', 'Brought spades and watering cans'),
(9, 4, 7, 'General Volunteer', 'Attended', 'Helped plant 45 saplings'),
(10, 5, 8, 'General Volunteer', 'Attended', 'Managed food serving counter');

-- 5. Seed Donations
INSERT INTO `donations` (`donation_id`, `event_id`, `donor_name`, `donor_contact`, `item_type`, `quantity`, `unit`) VALUES
(1, 1, 'Ahsan Habib', '01700112233', 'Warm Blankets & Quilts', 120.00, 'Pcs'),
(2, 1, 'Mirpur Welfare Society', '01899112244', 'Winter Sweaters & Cardigans', 250.00, 'Pcs'),
(3, 2, 'Care Foundation BD', '01922334455', 'Heavy Fleece Blankets', 200.00, 'Pcs'),
(4, 2, 'Anisur Rahman', '01511223344', 'Relief Support Cash', 15000.00, 'BDT'),
(5, 3, 'Popular Pharmaceuticals Rep', '01711225566', 'Paracetamol & Antacid Strips', 500.00, 'Boxes'),
(6, 4, 'Green Dhaka Initiative', '01633445566', 'Native Fruit Tree Saplings', 300.00, 'Pcs'),
(7, 5, 'Haji Rustam Ali Trust', '01844556677', 'Miniket Rice & Lentils (Dal)', 150.00, 'Kg');
