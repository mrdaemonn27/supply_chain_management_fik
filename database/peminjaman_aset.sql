-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 06 Jul 2026 pada 10.46
-- Versi server: 10.11.6-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peminjaman_aset`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `aset`
--

CREATE TABLE `aset` (
  `id_aset` int(11) NOT NULL,
  `id_ruangan` int(11) NOT NULL,
  `nama_aset` varchar(200) NOT NULL,
  `kode_aset` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `jumlah_total` int(11) NOT NULL DEFAULT 0,
  `jumlah_tersedia` int(11) NOT NULL DEFAULT 0,
  `kondisi` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT 'Baik',
  `foto` varchar(255) DEFAULT NULL,
  `total_peminjaman` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `aset`
--

INSERT INTO `aset` (`id_aset`, `id_ruangan`, `nama_aset`, `kode_aset`, `deskripsi`, `gambar`, `jumlah_total`, `jumlah_tersedia`, `kondisi`, `foto`, `total_peminjaman`, `created_at`, `updated_at`) VALUES
(28, 23, 'PC Intel I7 2600, RAM 4GB, VGA AMD HD 6670, HDD 500 GB', 'PC-001', 'PC untuk editing dan desain grafis', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(29, 23, 'PC Intel I5, RAM 4GB, VGA NVIDIA GEFORCE GT 430', 'PC-002', 'PC untuk editing dan desain grafis', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(30, 23, 'PC HP Elitedesk 800 G6, Intel I5 10600K, RAM 16GB, VGA RTX 2060 Super, SSD 500GB', 'PC-003', 'PC high-end untuk rendering 3D dan editing video', NULL, 20, 20, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(31, 23, 'PC HP Omem, Intel I7 Gen II, RAM 16GB, VGA RTX 3060, SSD 512GB, HDD 2TB', 'PC-004', 'PC untuk editing video profesional', NULL, 5, 5, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(32, 23, 'PC Intel I5 4570, RAM 4-6GB, VGA GEFORCE 210, HDD 500GB', 'PC-005', 'PC untuk desain grafis dasar', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(33, 23, 'PC Intel I5 6600K, RAM 8GB, VGA GTX 750 Ti, HDD 500GB', 'PC-006', 'PC untuk multimedia', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(34, 23, 'PC Intel I5 6600, RAM 8GB, VGA Intel HD Graphics 530, HDD 500GB', 'PC-007', 'PC untuk multimedia', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(35, 23, 'iMac 2012, Intel I5, RAM 8GB, VGA Radeon Pro 570, HDD 1TB', 'MAC-001', 'iMac untuk desain grafis', NULL, 19, 19, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(36, 23, 'iMac 2011, Intel I5, RAM 4GB, VGA HD 6750M, HDD 500GB', 'MAC-002', 'iMac untuk desain grafis', NULL, 7, 7, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(37, 23, 'iMac 2008, Intel Core 2 Duo, RAM 4GB, VGA HD 2600 PRO, HDD 320GB', 'MAC-003', 'iMac legacy untuk keperluan dasar', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(38, 23, 'iMac 2013, Intel I5, RAM 8GB, VGA Pro 1536, HDD 1TB', 'MAC-004', 'iMac untuk desain grafis', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(39, 23, 'Monitor LG EIGOIS FLATRON', 'MON-001', 'Monitor LCD 22 inch', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(40, 23, 'Monitor HP P24', 'MON-002', 'Monitor HP 24 inch', NULL, 64, 64, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(41, 23, 'Monitor LG Flatron', 'MON-003', 'Monitor LG Flatron', NULL, 6, 6, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(42, 23, 'Monitor Samsung S22F350FH', 'MON-004', 'Monitor Samsung 22 inch', NULL, 50, 50, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(43, 23, 'Monitor Dell', 'MON-005', 'Monitor Dell 24 inch', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(44, 23, 'Projector', 'PROJ-001', 'Proyektor untuk presentasi', NULL, 8, 8, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(45, 23, 'Switch Hub 24 Port', 'SW-001', 'Switch jaringan 24 port', NULL, 3, 3, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(46, 23, 'Switch Hub 16 Port', 'SW-002', 'Switch jaringan 16 port', NULL, 6, 6, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(47, 23, 'Switch Hub 48 Port', 'SW-003', 'Switch jaringan 48 port', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(48, 23, 'Switch Hub 8 Port', 'SW-004', 'Switch jaringan 8 port', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(49, 23, 'Keyboard USB', 'KB-001', 'Keyboard standar USB', NULL, 100, 100, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(50, 23, 'Magic Keyboard', 'KB-002', 'Apple Magic Keyboard Wireless', NULL, 28, 28, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(51, 23, 'Wired Keyboard', 'KB-003', 'Keyboard wired untuk PC', NULL, 20, 20, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(52, 23, 'Keyboard PC HP', 'KB-004', 'Keyboard HP original', NULL, 4, 4, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(53, 23, 'Mouse USB', 'MS-001', 'Mouse standar USB', NULL, 100, 100, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(54, 23, 'Magic Mouse', 'MS-002', 'Apple Magic Mouse Wireless', NULL, 28, 28, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(55, 23, 'Wired Mouse', 'MS-003', 'Mouse wired untuk PC', NULL, 20, 20, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(56, 23, 'Mouse PC HP', 'MS-004', 'Mouse HP original', NULL, 3, 3, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(57, 23, 'Mouse Logitech', 'MS-005', 'Mouse Logitech wireless', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(58, 23, 'Wacom Cintiq 13 HD', 'WAC-001', 'Wacom drawing tablet with screen', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(59, 23, 'Wacom Intuos Pro', 'WAC-002', 'Wacom professional drawing tablet', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(60, 23, 'Wacom Pen Tablet', 'WAC-003', 'Wacom standard pen tablet', NULL, 25, 25, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(61, 23, 'Speaker Active', 'SPK-001', 'Speaker aktif untuk multimedia', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(62, 23, 'Charger Mac', 'CHG-001', 'Charger untuk MacBook/iMac', NULL, 14, 14, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(63, 23, 'Port VGA Splitter', 'VGA-001', 'Splitter VGA 1 to 2', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:20:34', '2026-06-10 04:20:34'),
(64, 24, 'Studio Flash Godox QS400 II', 'FOTO-001', 'Studio flash untuk fotografi produk dan portrait', NULL, 4, 4, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(65, 24, 'Studio Flash Tronic Alfa 1000', 'FOTO-002', 'Studio flash Tronic Alfa 1000W', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(66, 24, 'Studio Flash Tronic Alfa 300', 'FOTO-003', 'Studio flash Tronic Alfa 300W', NULL, 1, 0, 'Baik', NULL, 1, '2026-06-10 04:26:49', '2026-07-06 02:16:51'),
(67, 24, 'Studio Lighting Godox LED1000Bi II', 'FOTO-004', 'LED continuous lighting untuk video dan foto', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(68, 24, 'Softbox', 'FOTO-005', 'Softbox untuk difusi cahaya studio', NULL, 6, 6, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(69, 24, 'Reflector Flash', 'FOTO-006', 'Reflector untuk studio flash', NULL, 3, 3, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(70, 24, 'Beauty Dish', 'FOTO-007', 'Beauty dish untuk portrait photography', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(71, 24, 'Snoot', 'FOTO-008', 'Snoot untuk efek cahaya spot', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(72, 24, 'Table Top', 'FOTO-009', 'Meja kecil untuk fotografi produk', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(73, 24, 'Kipas', 'FOTO-010', 'Kipas untuk efek rambut atau properti', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(74, 24, 'Cermin', 'FOTO-011', 'Cermin untuk properti fotografi', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(75, 24, 'Expander Background', 'FOTO-012', 'Background expander untuk backdrop', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(76, 25, 'Portable Cut Off', 'MTL-001', 'Mesin potong portable untuk logam', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(77, 25, 'Air Compressor Orange', 'MTL-002', 'Kompresor angin portable', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(78, 25, 'Bench Drill', 'MTL-003', 'Mesin bor meja untuk logam', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(79, 25, 'Bending Pipa', 'MTL-004', 'Alat bending pipa manual', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(80, 25, 'Mesin Las Listrik', 'MTL-005', 'Mesin las listrik untuk welding', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(81, 25, 'Mesin Kompressor Besar', 'MTL-006', 'Kompresor angin industrial besar', NULL, 1, 1, 'Rusak Berat', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 05:19:53'),
(82, 25, 'Mesin Las TIG Argon', 'MTL-007', 'Mesin las TIG untuk pengelasan presisi', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(83, 25, 'Mesin Kompressor Besar IZUMI', 'MTL-008', 'Kompresor angin merk IZUMI', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(84, 26, 'Mesin Bubut Kayu', 'WOD-001', 'Mesin bubut untuk kayu', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(85, 26, 'Mesin Bor', 'WOD-002', 'Mesin bor meja untuk kayu', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(86, 26, 'Bench Grinder', 'WOD-003', 'Mesin gerinda meja', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(87, 26, 'Palm Sander', 'WOD-004', 'Mesin amplas genggam', NULL, 3, 2, 'Baik', NULL, 1, '2026-06-10 04:26:49', '2026-06-12 10:24:20'),
(88, 26, 'Trimer', 'WOD-005', 'Mesin trimer untuk finishing edge', NULL, 1, 0, 'Baik', NULL, 1, '2026-06-10 04:26:49', '2026-07-06 02:16:51'),
(89, 26, 'Angel Drill', 'WOD-006', 'Bor sudut untuk area sempit', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(90, 26, 'Routher', 'WOD-007', 'Mesin router kayu', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(91, 26, 'Cordless', 'WOD-008', 'Bor cordless tanpa kabel', NULL, 1, 0, 'Baik', NULL, 1, '2026-06-10 04:26:49', '2026-06-18 09:53:05'),
(92, 26, 'Mitter Saw', 'WOD-009', 'Gergaji mitter untuk potong sudut', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(93, 26, 'Table Saw', 'WOD-010', 'Gergaji meja untuk potong kayu', NULL, 2, 2, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(94, 26, 'CNC Router', 'WOD-011', 'Mesin CNC untuk ukir kayu', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(95, 26, 'Laser 60x40', 'WOD-012', 'Mesin laser cutting ukuran 60x40 cm', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(96, 26, 'Jig Saw', 'WOD-013', 'Gergaji ukir listrik', NULL, 1, 1, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(97, 26, 'Plannet', 'WOD-014', 'Mesin planner/penebal kayu', NULL, 3, 3, 'Baik', NULL, 0, '2026-06-10 04:26:49', '2026-06-10 04:26:49'),
(98, 26, 'Sircular Saw', 'WOD-015', 'Gergaji circular portable', NULL, 2, 1, 'Baik', NULL, 1, '2026-06-10 04:26:49', '2026-06-15 07:15:25'),
(99, 27, 'hdmi', 'IK1-HDM-775', 'ssdasdfasdf', NULL, 1, 0, 'Baik', NULL, 1, '2026-06-12 11:35:50', '2026-07-06 02:22:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `maintenance`
--

CREATE TABLE `maintenance` (
  `id_maintenance` int(11) NOT NULL,
  `id_aset` int(11) NOT NULL,
  `tanggal_maintenance` date NOT NULL,
  `deskripsi` text NOT NULL,
  `kondisi_setelah` enum('Baik','Rusak','Perlu Perbaikan','Sudah Diperbaiki') NOT NULL DEFAULT 'Baik',
  `catatan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `maintenance`
--

INSERT INTO `maintenance` (`id_maintenance`, `id_aset`, `tanggal_maintenance`, `deskripsi`, `kondisi_setelah`, `catatan`, `created_by`, `created_at`) VALUES
(3, 28, '2026-01-05', 'Windows corrupt tidak bisa booting / Sering mengalami Blue Screen - Melakukan install ulang OS Windows beserta driver', 'Baik', 'Maintenance 05-09 Januari 2026', NULL, '2026-06-10 04:20:34'),
(4, 29, '2026-01-06', 'Windows Sering mengalami Blue Screen - Melakukan install ulang OS Windows beserta driver', 'Baik', 'Maintenance 6 Januari 2026', NULL, '2026-06-10 04:20:34'),
(5, 30, '2026-01-12', 'Adobe sering force close saat membuka file berukuran besar atau saat dirender - Melakukan clear files pada sistem Adobe dan melakukan reinstall', 'Baik', 'Maintenance 12-13 Januari 2026', NULL, '2026-06-10 04:20:34'),
(6, 31, '2026-01-14', 'Adobe sering force close saat membuka file berukuran besar atau saat dirender - Melakukan clear files pada sistem Adobe dan melakukan reinstall', 'Baik', 'Maintenance 14 Januari 2026', NULL, '2026-06-10 04:20:34'),
(7, 32, '2026-01-19', 'Pasta yang ada pada processor kering - Membersihkan pasta kering dan menambahkan pasta baru', 'Baik', 'Maintenance 19 Januari 2024', NULL, '2026-06-10 04:20:34'),
(8, 30, '2026-01-20', 'Muncul peringatan masa aktif Adobe habis - Melakukan login ulang menggunakan akun lab dan menyinkronkan lisensi terbaru', 'Baik', 'Maintenance 20 Januari 2026', NULL, '2026-06-10 04:20:34'),
(9, 33, '2026-01-21', 'Sangat Berdebu Pada area dalam laci pc - Membersihkan laci dengan kuas dan vakum', 'Baik', 'Maintenance 21-23 Januari 2026', NULL, '2026-06-10 04:20:34'),
(10, 50, '2026-01-15', 'Batre di bawah 50% - Charger Keyboard', 'Baik', 'Melakukan pengecekan dan charging baterai', NULL, '2026-06-10 04:20:34'),
(11, 54, '2026-01-15', 'Batre di bawah 50% - Charger Mouse', 'Baik', 'Melakukan pengecekan dan charging baterai', NULL, '2026-06-10 04:20:34'),
(12, 50, '2026-01-10', 'Batre di bawah 50% - Mengganti Batre Baru', 'Baik', 'Penggantian baterai keyboard', NULL, '2026-06-10 04:20:34'),
(13, 54, '2026-01-10', 'Batre di Bawah 50% - Mengganti Batre Baru', 'Baik', 'Penggantian baterai mouse', NULL, '2026-06-10 04:20:34'),
(14, 36, '2026-01-18', 'Tidak Masuk Mac (Stuck Logo) - Perbaikan sistem', 'Perlu Perbaikan', 'Masih dalam proses perbaikan', NULL, '2026-06-10 04:20:34'),
(15, 34, '2026-01-23', 'Sangat berdebu pada area dalam laci pc - Membersihkan laci dengan kuas dan vakum', 'Baik', 'Maintenance 23 Januari 2026', NULL, '2026-06-10 04:20:34'),
(16, 58, '2026-01-10', 'Pengecekan rutin tablet Wacom - Kalibrasi ulang dan pembersihan', 'Baik', 'Maintenance berkala', NULL, '2026-06-10 04:20:34'),
(17, 64, '2026-01-30', 'Flash generasi lama - Diganti menggunakan Godox QS400 II yang baru', 'Baik', 'Penggantian unit flash dengan yang lebih baru', NULL, '2026-06-10 04:26:49'),
(18, 78, '2026-01-16', 'Kapasitor terbakar - Ganti kapasitor', 'Baik', 'Penggantian kapasitor pada mesin bor', NULL, '2026-06-10 04:26:49'),
(19, 81, '2026-01-16', 'Selang bocor - Ganti selang', 'Baik', 'Penggantian selang angin yang bocor', NULL, '2026-06-10 04:26:49'),
(20, 84, '2026-01-06', 'Catok penyangga goyang, tidak presisi - Setel ulang', 'Baik', 'Penyetelan ulang catok mesin bubut', NULL, '2026-06-10 04:26:49'),
(21, 85, '2026-01-07', '1 mesin bor Kapasitor terbakar - Ganti kapasitor', 'Baik', 'Penggantian kapasitor pada 1 unit mesin bor', NULL, '2026-06-10 04:26:49'),
(22, 88, '2026-01-09', 'Tutup karbon rusak - Penggantian tutup karbon', 'Baik', 'Penggantian tutup karbon trimer', NULL, '2026-06-10 04:26:49'),
(23, 90, '2026-01-09', 'Handle lepas - Pemasangan handle', 'Baik', 'Pemasangan ulang handle router', NULL, '2026-06-10 04:26:49'),
(24, 81, '2026-06-10', 'Beberapa dari barang ini tidak bisa digunakan', 'Rusak', 'Segera diperbaiki', 1, '2026-06-10 05:19:53'),
(25, 65, '2026-01-30', 'Barang masih bisa digunakan dan terawat', 'Baik', 'Jaga barang agar tidak rusak', 1, '2026-06-10 07:02:44'),
(26, 77, '2026-06-13', 'sedang tidak untuk di pinjam', 'Baik', NULL, 1, '2026-06-12 10:28:22'),
(27, 99, '2026-06-12', 'zzxxc', 'Baik', 'asdasdasdasd', 1, '2026-06-12 11:36:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id_notifikasi`, `id_user`, `id_peminjaman`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(24, 5, 53, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-12 10:24:20'),
(25, 1, 53, '???? Pengajuan Disetujui', 'Pengajuan peminjaman Anda telah disetujui oleh Laboran.', 'info', 1, '2026-06-12 10:26:15'),
(26, 1, 53, '✅ Pengajuan Disetujui', 'Pengajuan peminjaman Anda telah disetujui sepenuhnya. Status: DIPINJAM.', 'success', 1, '2026-06-12 10:26:47'),
(27, 4, 54, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-12 11:38:02'),
(28, 5, 54, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-12 11:38:02'),
(29, 1, 54, '???? Pengajuan Disetujui', 'Pengajuan peminjaman Anda telah disetujui oleh Laboran.', 'info', 1, '2026-06-12 11:39:46'),
(30, 1, 54, '✅ Pengajuan Disetujui', 'Pengajuan peminjaman Anda telah disetujui sepenuhnya. Status: DIPINJAM.', 'success', 1, '2026-06-12 11:40:34'),
(31, 4, 55, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-15 07:15:25'),
(32, 5, 55, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-15 07:15:25'),
(33, 4, 56, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-18 09:53:05'),
(34, 5, 56, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 1 barang.', 'info', 0, '2026-06-18 09:53:05'),
(35, 4, 58, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 2 barang.', 'info', 0, '2026-07-06 02:16:51'),
(36, 5, 58, 'Pengajuan Peminjaman Baru', 'Administrator mengajukan peminjaman 2 barang.', 'info', 0, '2026-07-06 02:16:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjam`
--

CREATE TABLE `peminjam` (
  `id_peminjam` int(11) NOT NULL,
  `nama_peminjam` varchar(200) NOT NULL,
  `nim_nip` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jenis` enum('Mahasiswa','Dosen','Staff','Laboran') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjam`
--

INSERT INTO `peminjam` (`id_peminjam`, `nama_peminjam`, `nim_nip`, `email`, `no_hp`, `jenis`, `created_at`) VALUES
(5, 'Kaeylandri Ramadhan', '1234567899', NULL, NULL, 'Mahasiswa', '2026-04-11 07:06:22'),
(6, 'Administrator', 'ADMIN001', NULL, NULL, 'Mahasiswa', '2026-04-13 05:52:13'),
(7, 'Bagian Kemahasiswaan', 'KEMAHASISWAAN001', NULL, NULL, 'Mahasiswa', '2026-04-13 06:03:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `group_id` varchar(50) DEFAULT NULL,
  `id_aset` int(11) NOT NULL,
  `id_peminjam` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `jumlah_pinjam` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali_rencana` date NOT NULL,
  `tanggal_kembali_actual` date DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `status` enum('Menunggu Persetujuan','Dipinjam','Dikembalikan','Terlambat','Ditolak') DEFAULT 'Menunggu Persetujuan',
  `status_laboran` enum('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
  `catatan_laboran` text DEFAULT NULL,
  `tgl_approve_laboran` datetime DEFAULT NULL,
  `id_approver_laboran` int(11) DEFAULT NULL,
  `status_kaur` enum('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
  `catatan_kaur` text DEFAULT NULL,
  `tgl_approve_kaur` datetime DEFAULT NULL,
  `id_approver_kaur` int(11) DEFAULT NULL,
  `kondisi_saat_pinjam` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT NULL,
  `kondisi_saat_kembali` enum('Baik','Rusak Ringan','Rusak Berat') DEFAULT NULL,
  `foto_bukti` varchar(1234) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `group_id`, `id_aset`, `id_peminjam`, `id_user`, `jumlah_pinjam`, `tanggal_pinjam`, `tanggal_kembali_rencana`, `tanggal_kembali_actual`, `keperluan`, `status`, `status_laboran`, `catatan_laboran`, `tgl_approve_laboran`, `id_approver_laboran`, `status_kaur`, `catatan_kaur`, `tgl_approve_kaur`, `id_approver_kaur`, `kondisi_saat_pinjam`, `kondisi_saat_kembali`, `foto_bukti`, `catatan`, `created_at`, `updated_at`) VALUES
(53, 'PJM_20260612_6a2bde54c27e6', 87, 6, 1, 1, '2026-06-12', '2026-06-20', NULL, 'kebutuhan event', 'Dipinjam', 'Disetujui', '', '2026-06-12 17:26:15', 4, 'Disetujui', '', '2026-06-12 17:26:46', 5, 'Baik', NULL, '', NULL, '2026-06-12 10:24:20', '2026-06-12 10:26:47'),
(54, 'PJM_20260612_6a2bef9a90ce2', 99, 6, 1, 1, '2026-06-12', '2026-06-13', NULL, 'asdfasdf', 'Dipinjam', 'Disetujui', '', '2026-06-12 18:39:46', 4, 'Disetujui', '', '2026-06-12 18:40:34', 5, 'Baik', NULL, '', NULL, '2026-06-12 11:38:02', '2026-06-12 11:40:34'),
(55, 'PJM_20260615_6a2fa68d7bfb4', 98, 6, 1, 1, '2026-06-15', '2026-06-16', NULL, 'ssss', 'Menunggu Persetujuan', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Baik', NULL, '', NULL, '2026-06-15 07:15:25', '2026-06-15 07:15:25'),
(56, 'PJM_20260618_6a33c0018f3ab', 91, 6, 1, 1, '2026-06-18', '2026-06-20', NULL, 'Test', 'Menunggu Persetujuan', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Baik', NULL, '', NULL, '2026-06-18 09:53:05', '2026-06-18 09:53:05'),
(57, 'PJM_20260706_6a4b10133c107', 66, 6, 1, 1, '2026-07-06', '2026-07-15', NULL, 'acara panitia', 'Menunggu Persetujuan', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Baik', NULL, '', NULL, '2026-07-06 02:16:51', '2026-07-06 02:16:51'),
(58, 'PJM_20260706_6a4b10133c107', 88, 6, 1, 1, '2026-07-06', '2026-07-15', NULL, 'acara panitia', 'Menunggu Persetujuan', 'Pending', NULL, NULL, NULL, 'Pending', NULL, NULL, NULL, 'Baik', NULL, '', NULL, '2026-07-06 02:16:51', '2026-07-06 02:16:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman_detail`
--

CREATE TABLE `peminjaman_detail` (
  `id_detail` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `id_aset` int(11) NOT NULL,
  `jumlah_pinjam` int(11) NOT NULL DEFAULT 1,
  `kondisi_saat_pinjam` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peminjaman_detail`
--

INSERT INTO `peminjaman_detail` (`id_detail`, `id_peminjaman`, `id_aset`, `jumlah_pinjam`, `kondisi_saat_pinjam`, `catatan`, `created_at`) VALUES
(21, 36, 21, 1, 'Baik', NULL, '2026-04-26 07:06:25'),
(22, 37, 18, 1, 'Baik', NULL, '2026-04-26 07:24:15'),
(23, 38, 21, 1, 'Baik', NULL, '2026-04-27 01:22:17'),
(24, 39, 19, 1, 'Baik', NULL, '2026-04-27 01:22:59'),
(25, 40, 20, 1, 'Baik', NULL, '2026-04-27 01:22:59'),
(26, 41, 18, 1, 'Baik', NULL, '2026-04-27 02:17:18'),
(27, 42, 20, 1, 'Baik', NULL, '2026-04-27 02:26:36'),
(28, 43, 20, 1, 'Baik', NULL, '2026-04-27 02:28:44'),
(29, 44, 18, 1, 'Baik', NULL, '2026-04-27 02:31:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ruangan`
--

CREATE TABLE `ruangan` (
  `id_ruangan` int(11) NOT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'door-open-fill',
  `warna` varchar(20) DEFAULT '#FF8C00',
  `foto` varchar(255) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ruangan`
--

INSERT INTO `ruangan` (`id_ruangan`, `nama_ruangan`, `icon`, `warna`, `foto`, `deskripsi`, `created_at`) VALUES
(23, 'Lab Multimedia', 'display', '#8B5CF6', NULL, 'Laboratorium Multimedia untuk praktikum desain grafis, editing video, dan animasi', '2026-06-10 04:20:34'),
(24, 'Lab Fotografi', 'camera-fill', '#EC4899', NULL, 'Laboratorium Fotografi untuk praktikum fotografi studio dan lighting', '2026-06-10 04:26:49'),
(25, 'Lab Metal Working', 'tools', '#3B82F6', NULL, 'Laboratorium pengerjaan logam untuk praktikum welding, cutting, dan metal fabrication', '2026-06-10 04:26:49'),
(26, 'Lab Woodworking', 'hammer', '#F59E0B', NULL, 'Laboratorium pengerjaan kayu untuk praktikum woodworking dan furniture', '2026-06-10 04:26:49'),
(27, 'IK1.03.02', 'door-open-fill', '#FF8C00', NULL, 'Lab Multimedia PC', '2026-06-12 11:35:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nim_nip` varchar(50) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','laboran','kaur') NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nim_nip`, `nama_lengkap`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN001', 'Administrator', 'admin@laboran.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-04-11 14:01:24', NULL),
(2, '2023001', 'Mahasiswa Test', 'mahasiswa@test.com', '$2y$10$YourHashedPasswordHere', 'user', '2026-04-11 14:01:24', NULL),
(3, '1234567899', 'Kaeylandri Ramadhan', NULL, '$2a$12$NnlLJ5pyGiaVaYzUY.V.9OYk.xGT/lZ55bXpJi7VI.mEQw6fgxKmC', 'user', '0000-00-00 00:00:00', NULL),
(4, 'LABORAN001', 'Laboran', 'laboran@lab.com', '$2a$12$NnlLJ5pyGiaVaYzUY.V.9OYk.xGT/lZ55bXpJi7VI.mEQw6fgxKmC', 'laboran', '2026-04-13 12:49:04', NULL),
(5, 'KAUR001', 'Kepala Urusan', 'kaur@lab.com', '$2a$12$NnlLJ5pyGiaVaYzUY.V.9OYk.xGT/lZ55bXpJi7VI.mEQw6fgxKmC', 'kaur', '2026-04-13 12:49:04', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `aset`
--
ALTER TABLE `aset`
  ADD PRIMARY KEY (`id_aset`),
  ADD UNIQUE KEY `kode_aset` (`kode_aset`),
  ADD KEY `idx_aset_kategori` (`id_ruangan`),
  ADD KEY `idx_aset_total_peminjaman` (`total_peminjaman`);

--
-- Indeks untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id_maintenance`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `idx_user` (`id_user`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `id_peminjaman` (`id_peminjaman`);

--
-- Indeks untuk tabel `peminjam`
--
ALTER TABLE `peminjam`
  ADD PRIMARY KEY (`id_peminjam`),
  ADD UNIQUE KEY `nim_nip` (`nim_nip`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `idx_peminjaman_aset` (`id_aset`),
  ADD KEY `idx_peminjaman_peminjam` (`id_peminjam`),
  ADD KEY `idx_peminjaman_status` (`status`),
  ADD KEY `idx_status_kemahasiswaan` (`status_laboran`),
  ADD KEY `idx_status_pembina` (`status_kaur`),
  ADD KEY `idx_group_id` (`group_id`);

--
-- Indeks untuk tabel `peminjaman_detail`
--
ALTER TABLE `peminjaman_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_peminjaman` (`id_peminjaman`),
  ADD KEY `id_aset` (`id_aset`);

--
-- Indeks untuk tabel `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id_ruangan`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `nim_nip` (`nim_nip`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `aset`
--
ALTER TABLE `aset`
  MODIFY `id_aset` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id_maintenance` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `peminjam`
--
ALTER TABLE `peminjam`
  MODIFY `id_peminjam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT untuk tabel `peminjaman_detail`
--
ALTER TABLE `peminjaman_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id_ruangan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `aset`
--
ALTER TABLE `aset`
  ADD CONSTRAINT `aset_ibfk_1` FOREIGN KEY (`id_ruangan`) REFERENCES `ruangan` (`id_ruangan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`id_aset`) REFERENCES `aset` (`id_aset`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_peminjam`) REFERENCES `peminjam` (`id_peminjam`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
