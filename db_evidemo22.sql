-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 03, 2026 at 08:20 AM
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
-- Database: `db_evidemo22`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblcaseregister`
--

CREATE TABLE `tblcaseregister` (
  `CaseId` int(11) NOT NULL,
  `CaseUId` varchar(100) NOT NULL,
  `CaseTitle` varchar(255) NOT NULL,
  `CaseType` enum('Criminal','Civil','CyberCrime') NOT NULL,
  `DateOfIncident` date NOT NULL,
  `LocationOfIncident` varchar(255) NOT NULL,
  `CaseDescription` text NOT NULL,
  `ComplainantName` varchar(255) NOT NULL,
  `ComplainantPhone` varchar(20) NOT NULL,
  `ComplainantEmail` varchar(255) NOT NULL,
  `DocumentPath` text DEFAULT NULL,
  `CaseStatus` varchar(50) DEFAULT 'Active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcaseregister`
--

INSERT INTO `tblcaseregister` (`CaseId`, `CaseUId`, `CaseTitle`, `CaseType`, `DateOfIncident`, `LocationOfIncident`, `CaseDescription`, `ComplainantName`, `ComplainantPhone`, `ComplainantEmail`, `DocumentPath`, `CaseStatus`, `CreatedAt`, `UpdatedAt`) VALUES
(121, '6eeddd8a-13b5-11f1-ac97-fcaa145acf02', 'demo', 'Criminal', '2026-02-27', 'Solapur', 'No Description', 'Omkar', '9021775071', 'demo@gmail.com', '[\"CaseFolder/6eebb537-13b5-11f1-ac97-fcaa145acf02/1772180539_0_1771849851_BJP_emblom__1_-removebg-preview.png\"]', 'Deleted', '2026-02-27 08:22:19', '2026-02-28 10:19:05'),
(345, 'e756c7b2-13b5-11f1-ac97-fcaa145acf02', 'Criminal', 'CyberCrime', '2026-02-27', 'Akkalkot', 'Description', 'Aditya', '8745555555', 'adi@gmail.com', '[\"CaseFolder/e753739c-13b5-11f1-ac97-fcaa145acf02/1772180741_0_1771849851_BJP_emblom__1_-removebg-preview.png\",\"CaseFolder/e753739c-13b5-11f1-ac97-fcaa145acf02/1772180741_1_Screenshot2026-02-12131435.png\"]', 'Active', '2026-02-27 08:25:41', '2026-03-02 05:02:45'),
(346, '16ab72ad-1473-11f1-8753-48ba4eabcfbc', 'enffffni', 'Criminal', '2026-02-28', 'kanbfkjasf', 'Solapur', 'Aditya Dilpake', '7459621584', 'adiii@gmail.com', NULL, 'Deleted', '2026-02-28 06:59:55', '2026-02-28 07:02:30'),
(347, '11f7d5a4-15f9-11f1-8da8-48ba4eabcfbc', 'enffffni', 'Civil', '2026-03-02', 'dmkasmasd', 'jhjb b j  jb j', 'ELN_SY_18_Omkar Mungale', '7877456985', 'ormungale@gmail.com', NULL, 'Active', '2026-03-02 05:31:31', '2026-03-02 05:31:31'),
(348, '35b1b52e-15fb-11f1-8da8-48ba4eabcfbc', 'fafhuih', 'Criminal', '2026-03-01', 'dmkasmasd', 'uhofaiffhhuhuuf', 'djasjdaso', '5585444848', 'ormungale@gmail.com', '[\"CaseFolder\\/35a533e1-15fb-11f1-8da8-48ba4eabcfbc\\/1772430410_0_WhatsAppImage2026-02-09at12.13.47PM.jpeg\"]', 'Active', '2026-03-02 05:46:50', '2026-03-02 05:46:50');

-- --------------------------------------------------------

--
-- Table structure for table `tblevidence`
--

CREATE TABLE `tblevidence` (
  `EvidenceUId` varchar(36) NOT NULL DEFAULT uuid(),
  `EvidenceID` varchar(50) NOT NULL,
  `CaseID` varchar(50) NOT NULL,
  `EvidenceType` varchar(30) NOT NULL,
  `EvidenceStatus` varchar(20) NOT NULL DEFAULT 'Pending',
  `Description` text DEFAULT NULL,
  `SubmittedBy` varchar(100) NOT NULL,
  `AuthorityName` varchar(100) DEFAULT NULL,
  `SubmissionDate` date NOT NULL,
  `LocationRecovered` varchar(200) DEFAULT NULL,
  `FilePaths` longtext DEFAULT NULL,
  `BlockchainHash` varchar(64) DEFAULT NULL,
  `RecordStatus` varchar(20) NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblevidence`
--

INSERT INTO `tblevidence` (`EvidenceUId`, `EvidenceID`, `CaseID`, `EvidenceType`, `EvidenceStatus`, `Description`, `SubmittedBy`, `AuthorityName`, `SubmissionDate`, `LocationRecovered`, `FilePaths`, `BlockchainHash`, `RecordStatus`, `CreatedAt`) VALUES
('3ff6bf34-1477-11f1-8753-48ba4eabcfbc', 'EVID-2026-001', 'CASE-2026-001', 'Document', 'Rejected', 'ffjsaoifasfnaisofjsapihfoisafsanfoisahfposkjgioegpesgihesfpoaegfpuajgv', 'uiuhfiuasfhaui', 'Sub-Inspector', '2026-02-28', 'kanfkalsnfklasfasfaj', NULL, '15e0bfd384a504868393c9d23f3cbe8ccdc0551112f2bcb0cf59383543a7f9f5', 'Deleted', '2026-02-28 12:59:43'),
('e5326d99-149d-11f1-8753-48ba4eabcfbc', 'EVID-2026-002', 'CASE-2026-001', 'Document', 'Pending', 'fdfeffgd', 'uiuhfiuasfhaui', 'Sub-Inspector', '2026-02-28', 'gfgfggfg', '[\"EvidenceFolder\\/e52ea967-149d-11f1-8753-48ba4eabcfbc\\/1772280381_0_IndiaMap.jpg\"]', '0be0dd85bdf075afb4284458980f198bbedcab1620bcc4b7663ce994271a9405', 'Active', '2026-02-28 17:36:21');

-- --------------------------------------------------------

--
-- Table structure for table `tblusers`
--

CREATE TABLE `tblusers` (
  `UserId` int(11) NOT NULL,
  `UserUId` varchar(100) NOT NULL,
  `LicenseUId` varchar(100) NOT NULL,
  `UserName` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `EmailId` varchar(255) DEFAULT NULL,
  `PhoneNo` varchar(20) DEFAULT NULL,
  `Role` varchar(50) DEFAULT 'User',
  `Status` enum('Active','Pending','Deleted') DEFAULT 'Active',
  `IsOnline` enum('Yes','No') DEFAULT 'No',
  `LastLoginDateTime` datetime DEFAULT NULL,
  `DateTimeInserted` datetime DEFAULT current_timestamp(),
  `ModifiedDateTime` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblusers`
--

INSERT INTO `tblusers` (`UserId`, `UserUId`, `LicenseUId`, `UserName`, `Password`, `FullName`, `EmailId`, `PhoneNo`, `Role`, `Status`, `IsOnline`, `LastLoginDateTime`, `DateTimeInserted`, `ModifiedDateTime`) VALUES
(1, '99b6aac9-12e9-11f1-b290-fcaa145acf02', 'da266b00-12e8-11f1-b290-fcaa145acf02', 'admin', '123', 'Aditya Dilpake', 'dilpakeaditya@gmail.com', '9373230081', 'Administrator', 'Active', 'Yes', '2026-02-27 10:59:43', '2026-02-26 13:33:14', '2026-03-02 12:57:43'),
(2, '99b7c324-12e9-11f1-b290-fcaa145acf02', 'da266b00-12e8-11f1-b290-fcaa145acf02', 'User', '1234', 'Omkar Mungale', 'ormungale@gmail.com', '9172880430', 'User', 'Active', 'Yes', '2026-02-27 10:49:23', '2026-02-26 13:33:14', '2026-03-02 12:58:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblcaseregister`
--
ALTER TABLE `tblcaseregister`
  ADD PRIMARY KEY (`CaseId`),
  ADD UNIQUE KEY `CaseUId` (`CaseUId`);

--
-- Indexes for table `tblevidence`
--
ALTER TABLE `tblevidence`
  ADD PRIMARY KEY (`EvidenceUId`),
  ADD UNIQUE KEY `uq_evidence_id` (`EvidenceID`),
  ADD KEY `idx_case_id` (`CaseID`),
  ADD KEY `idx_status` (`RecordStatus`);

--
-- Indexes for table `tblusers`
--
ALTER TABLE `tblusers`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `UserUId` (`UserUId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblcaseregister`
--
ALTER TABLE `tblcaseregister`
  MODIFY `CaseId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `tblusers`
--
ALTER TABLE `tblusers`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
