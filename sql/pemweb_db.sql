-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 04:54 PM
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
-- Database: `pemweb_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `prodi_tbl`
--

CREATE TABLE `prodi_tbl` (
  `prodi_id` int(11) NOT NULL,
  `nama_prodi` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prodi_tbl`
--

INSERT INTO `prodi_tbl` (`prodi_id`, `nama_prodi`) VALUES
(1, 'Ilmu Komputer'),
(2, 'Fisika'),
(3, 'Matematika'),
(4, 'Biologi'),
(5, 'Statistika'),
(13, 'Teknik');

-- --------------------------------------------------------

--
-- Table structure for table `user_tbl`
--

CREATE TABLE `user_tbl` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `prodi_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tbl`
--

INSERT INTO `user_tbl` (`user_id`, `email`, `password`, `prodi_id`) VALUES
(1, 'dikan42724@gmail.com', '12345678', 1),
(12, 'andi.saputra@gmail.com', '12345678', 1),
(13, 'budi.santoso@gmail.com', '12345678', 2),
(14, 'citra.lestari@gmail.com', '12345678', 3),
(15, 'dewi.anggraini@gmail.com', '12345678', 4),
(16, 'eko.pratama@gmail.com', '12345678', 5),
(17, 'fajar.nugroho@gmail.com', '12345678', 1),
(18, 'gina.marlina@gmail.com', '12345678', 2),
(19, 'hendra.wijaya@gmail.com', '12345678', 3),
(20, 'intan.permata@gmail.com', '12345678', 4),
(21, 'joko.susilo@gmail.com', '12345678', 5),
(25, 'dwa@dwadwas.cs', '12345678', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `prodi_tbl`
--
ALTER TABLE `prodi_tbl`
  ADD PRIMARY KEY (`prodi_id`);

--
-- Indexes for table `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `prodi_id` (`prodi_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `prodi_tbl`
--
ALTER TABLE `prodi_tbl`
  MODIFY `prodi_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
