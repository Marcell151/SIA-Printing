-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Nov 2025 pada 15.57
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sia_printing`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurnal_penyesuaian`
--

CREATE TABLE `jurnal_penyesuaian` (
  `id_penyesuaian` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `id_akun_debit` int(11) NOT NULL,
  `id_akun_kredit` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `deskripsi` text NOT NULL,
  `periode` varchar(20) NOT NULL,
  `is_void` tinyint(1) DEFAULT 0,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jurnal_penyesuaian`
--

INSERT INTO `jurnal_penyesuaian` (`id_penyesuaian`, `tanggal`, `id_akun_debit`, `id_akun_kredit`, `nominal`, `deskripsi`, `periode`, `is_void`, `voided_by`, `voided_at`, `void_reason`, `created_by`, `created_at`) VALUES
(2, '2025-10-31', 17, 3, 3000000.00, 'Penyesuaian perlengkapan yang terpakai selama bulan Oktober 2025 (kertas A4/A3, tinta, cartridge, dll) berdasarkan stock opname', '2025-10', 0, NULL, NULL, NULL, 2, '2025-11-01 02:49:45'),
(3, '2025-10-31', 18, 5, 416667.00, 'Penyusutan peralatan bulan Oktober 2025 (Printer, Komputer, Mesin Cutting) dengan umur ekonomis 5 tahun, metode garis lurus', '2025-10', 0, NULL, NULL, NULL, 2, '2025-11-01 02:49:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurnal_umum`
--

CREATE TABLE `jurnal_umum` (
  `id_jurnal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `deskripsi` text NOT NULL,
  `id_akun_debit` int(11) NOT NULL,
  `id_akun_kredit` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `referensi` varchar(50) DEFAULT NULL,
  `tipe_transaksi` varchar(50) DEFAULT NULL,
  `is_void` tinyint(1) DEFAULT 0,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jurnal_umum`
--

INSERT INTO `jurnal_umum` (`id_jurnal`, `tanggal`, `deskripsi`, `id_akun_debit`, `id_akun_kredit`, `nominal`, `referensi`, `tipe_transaksi`, `is_void`, `voided_by`, `voided_at`, `void_reason`, `created_at`) VALUES
(36, '2025-10-01', 'Setoran Modal Awal Pemilik untuk Memulai Usaha Percetakan', 1, 8, 100000000.00, 'MODAL-001', 'Modal Awal', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(37, '2025-10-02', 'Pembelian Perlengkapan: Kertas A4, A3, Tinta Printer, Cartridge, dll untuk persediaan operasional', 3, 1, 20000000.00, 'BUY-SUPPLY-001', 'Pembelian Aset', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(38, '2025-10-03', 'Pembelian Peralatan: 2 unit Printer Digital, 3 unit Komputer, 1 unit Mesin Cutting, Meja Kerja', 4, 1, 25000000.00, 'BUY-EQUIP-001', 'Pembelian Aset', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(39, '2025-10-04', 'Pembayaran Sewa Tempat Usaha periode Oktober - Desember 2025 @ Rp 2.000.000/bulan', 16, 1, 6000000.00, 'RENT-Q4-2025', 'Beban', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(40, '2025-10-05', 'Pembayaran Gaji 2 karyawan (operator & desainer) bulan Oktober 2025', 14, 1, 5000000.00, 'PAYROLL-OCT-2025', 'Beban', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(41, '2025-10-06', 'Pembayaran Listrik, Air, Internet bulan Oktober 2025', 15, 1, 1500000.00, 'UTILITY-OCT-2025', 'Beban', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(42, '2025-10-07', 'Pengambilan Uang untuk Keperluan Pribadi Owner', 9, 1, 2000000.00, 'PRIVE-OCT-001', 'Prive', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(43, '2025-10-10', 'Pendapatan Tunai - Cetak Brosur A4 Warna 500 lembar + Desain Layout', 1, 10, 2500000.00, 'PT-1', 'Pendapatan Tunai', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(44, '2025-10-12', 'Pendapatan Kredit - Cetak Banner 2x3 meter + Desain - PT. ABC Indonesia - INV-202510001', 2, 10, 5000000.00, 'INV-202510001', 'Pendapatan Kredit', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(45, '2025-10-12', 'DP Piutang 20% - INV-202510001', 1, 2, 1000000.00, 'INV-202510001', 'DP Piutang', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(46, '2025-10-15', 'Pendapatan Tunai - Fotocopy A4 BW 1000 lembar + Jilid Spiral', 1, 11, 350000.00, 'PT-2', 'Pendapatan Tunai', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(47, '2025-10-18', 'Pendapatan Kredit - Undangan Pernikahan Custom - CV. XYZ Mandiri - INV-202510002', 2, 10, 7500000.00, 'INV-202510002', 'Pendapatan Kredit', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(48, '2025-10-18', 'DP Piutang 33% - INV-202510002', 1, 2, 2500000.00, 'INV-202510002', 'DP Piutang', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(49, '2025-10-20', 'Penerimaan Pembayaran Piutang - INV-202510001 - Cicilan 2', 1, 2, 2000000.00, 'INV-202510001', 'Penerimaan Piutang', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(50, '2025-10-22', 'Pendapatan Lain-Lain - Sewa Komputer & Printer untuk Event Kampus', 1, 13, 1500000.00, 'PL-1', 'Pendapatan Lainnya', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(51, '2025-10-25', 'Pendapatan Tunai - Cetak Kartu Nama + Desain Logo Startup', 1, 10, 800000.00, 'PT-3', 'Pendapatan Tunai', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(52, '2025-10-28', 'Beban Maintenance & Service Mesin Printer + Komputer', 19, 1, 500000.00, 'MAINT-OCT-001', 'Beban', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(53, '2025-10-31', 'Penyesuaian: Perlengkapan yang terpakai bulan Oktober 2025', 17, 3, 3000000.00, 'AJE-1', 'Jurnal Penyesuaian', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(54, '2025-10-31', 'Penyesuaian: Penyusutan peralatan bulan Oktober 2025', 18, 5, 416667.00, 'AJE-2', 'Jurnal Penyesuaian', 0, NULL, NULL, NULL, '2025-11-01 02:49:45'),
(55, '2025-11-01', 'Pendapatan Tunai - Cetak A4 Warna', 1, 10, 100000.00, 'PT-9', 'Pendapatan Tunai', 0, NULL, NULL, NULL, '2025-11-01 07:25:20'),
(56, '2025-11-07', 'Pendapatan Kredit - Banner 1x2 - INV-2025110001', 2, 11, 1000000.00, 'INV-2025110001', 'Pendapatan Kredit', 0, NULL, NULL, NULL, '2025-11-07 12:14:20'),
(57, '2025-11-07', 'DP Piutang - INV-2025110001', 1, 2, 500000.00, 'INV-2025110001', 'DP Piutang', 0, NULL, NULL, NULL, '2025-11-07 12:14:20'),
(58, '2025-11-07', 'Pendapatan Kredit - Cetak Poster - INV-2025110002', 2, 10, 200000.00, 'INV-2025110002', 'Pendapatan Kredit', 0, NULL, NULL, NULL, '2025-11-07 12:18:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `laba_rugi`
--

CREATE TABLE `laba_rugi` (
  `id_laba_rugi` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `total_pendapatan` decimal(15,2) NOT NULL,
  `total_beban` decimal(15,2) NOT NULL,
  `laba_rugi` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_akun`
--

CREATE TABLE `master_akun` (
  `id_akun` int(11) NOT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `tipe_akun` enum('1-Aktiva','2-Kewajiban','3-Modal','4-Pendapatan','5-Beban') NOT NULL,
  `saldo` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_akun`
--

INSERT INTO `master_akun` (`id_akun`, `kode_akun`, `nama_akun`, `tipe_akun`, `saldo`, `created_at`) VALUES
(1, '1-101', 'Kas', '1-Aktiva', 51250000.00, '2025-10-30 05:51:07'),
(2, '1-102', 'Piutang Usaha', '1-Aktiva', 7700000.00, '2025-10-30 05:51:07'),
(3, '1-103', 'Perlengkapan', '1-Aktiva', 17000000.00, '2025-10-30 05:51:07'),
(4, '1-201', 'Peralatan', '1-Aktiva', 25000000.00, '2025-10-30 05:51:07'),
(5, '1-202', 'Akumulasi Penyusutan Peralatan', '1-Aktiva', 416667.00, '2025-10-30 05:51:07'),
(6, '2-101', 'Utang Usaha', '2-Kewajiban', 0.00, '2025-10-30 05:51:07'),
(7, '2-102', 'Pendapatan Diterima Dimuka', '2-Kewajiban', 0.00, '2025-10-30 05:51:07'),
(8, '3-101', 'Modal Pemilik', '3-Modal', 100000000.00, '2025-10-30 05:51:07'),
(9, '3-102', 'Prive', '3-Modal', 2000000.00, '2025-10-30 05:51:07'),
(10, '4-101', 'Pendapatan Jasa Printing', '4-Pendapatan', 16100000.00, '2025-10-30 05:51:07'),
(11, '4-102', 'Pendapatan Jasa Fotocopy', '4-Pendapatan', 1350000.00, '2025-10-30 05:51:07'),
(12, '4-103', 'Pendapatan Jasa Jilid', '4-Pendapatan', 0.00, '2025-10-30 05:51:07'),
(13, '4-104', 'Pendapatan Lain-Lain', '4-Pendapatan', 1500000.00, '2025-10-30 05:51:07'),
(14, '5-101', 'Beban Gaji', '5-Beban', 5000000.00, '2025-10-30 05:51:07'),
(15, '5-102', 'Beban Listrik', '5-Beban', 1500000.00, '2025-10-30 05:51:07'),
(16, '5-103', 'Beban Sewa', '5-Beban', 6000000.00, '2025-10-30 05:51:07'),
(17, '5-104', 'Beban Perlengkapan', '5-Beban', 3000000.00, '2025-10-30 05:51:07'),
(18, '5-105', 'Beban Penyusutan', '5-Beban', 416667.00, '2025-10-30 05:51:07'),
(19, '5-106', 'Beban Lain-Lain', '5-Beban', 500000.00, '2025-10-30 05:51:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_jasa`
--

CREATE TABLE `master_jasa` (
  `id_jasa` int(11) NOT NULL,
  `nama_jasa` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `harga_satuan` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_jasa`
--

INSERT INTO `master_jasa` (`id_jasa`, `nama_jasa`, `kategori`, `harga_satuan`, `created_at`) VALUES
(1, 'Cetak A4 Hitam Putih', 'Printing', 500.00, '2025-10-30 05:51:07'),
(2, 'Cetak A4 Warna', 'Printing', 2000.00, '2025-10-30 05:51:07'),
(3, 'Cetak A3 Hitam Putih', 'Printing', 1000.00, '2025-10-30 05:51:07'),
(4, 'Cetak A3 Warna', 'Printing', 3000.00, '2025-10-30 05:51:07'),
(5, 'Fotocopy A4', 'Fotocopy', 300.00, '2025-10-30 05:51:07'),
(6, 'Fotocopy A3', 'Fotocopy', 500.00, '2025-10-30 05:51:07'),
(7, 'Jilid Spiral', 'Jilid', 5000.00, '2025-10-30 05:51:07'),
(8, 'Jilid Hard Cover', 'Jilid', 15000.00, '2025-10-30 05:51:07'),
(9, 'Laminating A4', 'Laminasi', 3000.00, '2025-10-30 05:51:07'),
(10, 'Desain Grafis', 'Desain', 50000.00, '2025-10-30 05:51:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_pelanggan`
--

CREATE TABLE `master_pelanggan` (
  `id_pelanggan` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_pelanggan`
--

INSERT INTO `master_pelanggan` (`id_pelanggan`, `nama_pelanggan`, `alamat`, `telepon`, `email`, `created_at`) VALUES
(1, 'PT. ABC Indonesia', 'Jl. Sudirman No. 123, Jakarta', '021-1234567', 'contact@abc.co.id', '2025-10-30 05:51:07'),
(2, 'CV. XYZ Mandiri', 'Jl. Gatot Subroto No. 45, Bandung', '022-7654321', 'info@xyz.co.id', '2025-10-30 05:51:07'),
(3, 'Toko Maju Jaya', 'Jl. Ahmad Yani No. 78, Surabaya', '031-9876543', 'tokumaju@gmail.com', '2025-10-30 05:51:07'),
(4, 'UD. Berkah Sejahtera', 'Jl. Diponegoro No. 56, Malang', '0341-555666', 'berkah@yahoo.com', '2025-10-30 05:51:07'),
(5, 'Umum', 'Walk-in Customer', '-', '-', '2025-10-30 05:51:07'),
(6, 'Carlos Susanto', 'Jl. Dirgahayu No. 67, Malang', '08932422322', 'carlosusanto@gmail.com', '2025-11-08 02:50:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `modal`
--

CREATE TABLE `modal` (
  `id_modal` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `modal_awal` decimal(15,2) NOT NULL,
  `prive` decimal(15,2) DEFAULT 0.00,
  `laba_rugi` decimal(15,2) NOT NULL,
  `modal_akhir` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `neraca_saldo`
--

CREATE TABLE `neraca_saldo` (
  `id_neraca` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `id_akun` int(11) NOT NULL,
  `saldo_debit` decimal(15,2) DEFAULT 0.00,
  `saldo_kredit` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `neraca_saldo`
--

INSERT INTO `neraca_saldo` (`id_neraca`, `periode`, `id_akun`, `saldo_debit`, `saldo_kredit`, `created_at`) VALUES
(148, '2025-10', 1, 50650000.00, 0.00, '2025-11-01 07:26:34'),
(149, '2025-10', 2, 7000000.00, 0.00, '2025-11-01 07:26:34'),
(150, '2025-10', 3, 20000000.00, 0.00, '2025-11-01 07:26:34'),
(151, '2025-10', 4, 25000000.00, 0.00, '2025-11-01 07:26:34'),
(152, '2025-10', 8, 0.00, 100000000.00, '2025-11-01 07:26:34'),
(153, '2025-10', 9, 2000000.00, 0.00, '2025-11-01 07:26:34'),
(154, '2025-10', 10, 0.00, 15800000.00, '2025-11-01 07:26:34'),
(155, '2025-10', 11, 0.00, 350000.00, '2025-11-01 07:26:34'),
(156, '2025-10', 13, 0.00, 1500000.00, '2025-11-01 07:26:34'),
(157, '2025-10', 14, 5000000.00, 0.00, '2025-11-01 07:26:34'),
(158, '2025-10', 15, 1500000.00, 0.00, '2025-11-01 07:26:34'),
(159, '2025-10', 16, 6000000.00, 0.00, '2025-11-01 07:26:34'),
(160, '2025-10', 19, 500000.00, 0.00, '2025-11-01 07:26:34'),
(161, '2025-11', 1, 50750000.00, 0.00, '2025-11-01 07:26:43'),
(162, '2025-11', 2, 7000000.00, 0.00, '2025-11-01 07:26:43'),
(163, '2025-11', 3, 20000000.00, 0.00, '2025-11-01 07:26:43'),
(164, '2025-11', 4, 25000000.00, 0.00, '2025-11-01 07:26:43'),
(165, '2025-11', 8, 0.00, 100000000.00, '2025-11-01 07:26:43'),
(166, '2025-11', 9, 2000000.00, 0.00, '2025-11-01 07:26:43'),
(167, '2025-11', 10, 0.00, 15900000.00, '2025-11-01 07:26:43'),
(168, '2025-11', 11, 0.00, 350000.00, '2025-11-01 07:26:43'),
(169, '2025-11', 13, 0.00, 1500000.00, '2025-11-01 07:26:43'),
(170, '2025-11', 14, 5000000.00, 0.00, '2025-11-01 07:26:43'),
(171, '2025-11', 15, 1500000.00, 0.00, '2025-11-01 07:26:43'),
(172, '2025-11', 16, 6000000.00, 0.00, '2025-11-01 07:26:43'),
(173, '2025-11', 19, 500000.00, 0.00, '2025-11-01 07:26:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `neraca_saldo_penyesuaian`
--

CREATE TABLE `neraca_saldo_penyesuaian` (
  `id_neraca_penyesuaian` int(11) NOT NULL,
  `periode` varchar(20) NOT NULL,
  `id_akun` int(11) NOT NULL,
  `saldo_debit` decimal(15,2) DEFAULT 0.00,
  `saldo_kredit` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `neraca_saldo_penyesuaian`
--

INSERT INTO `neraca_saldo_penyesuaian` (`id_neraca_penyesuaian`, `periode`, `id_akun`, `saldo_debit`, `saldo_kredit`, `created_at`) VALUES
(93, '2025-10', 1, 50650000.00, 0.00, '2025-11-01 07:27:10'),
(94, '2025-10', 2, 7000000.00, 0.00, '2025-11-01 07:27:10'),
(95, '2025-10', 3, 17000000.00, 0.00, '2025-11-01 07:27:10'),
(96, '2025-10', 4, 25000000.00, 0.00, '2025-11-01 07:27:10'),
(97, '2025-10', 5, 0.00, 416667.00, '2025-11-01 07:27:10'),
(98, '2025-10', 8, 0.00, 100000000.00, '2025-11-01 07:27:10'),
(99, '2025-10', 9, 2000000.00, 0.00, '2025-11-01 07:27:10'),
(100, '2025-10', 10, 0.00, 15800000.00, '2025-11-01 07:27:10'),
(101, '2025-10', 11, 0.00, 350000.00, '2025-11-01 07:27:10'),
(102, '2025-10', 13, 0.00, 1500000.00, '2025-11-01 07:27:10'),
(103, '2025-10', 14, 5000000.00, 0.00, '2025-11-01 07:27:10'),
(104, '2025-10', 15, 1500000.00, 0.00, '2025-11-01 07:27:10'),
(105, '2025-10', 16, 6000000.00, 0.00, '2025-11-01 07:27:10'),
(106, '2025-10', 17, 3000000.00, 0.00, '2025-11-01 07:27:10'),
(107, '2025-10', 18, 416667.00, 0.00, '2025-11-01 07:27:10'),
(108, '2025-10', 19, 500000.00, 0.00, '2025-11-01 07:27:10'),
(109, '2025-11', 1, 50750000.00, 0.00, '2025-11-01 07:27:16'),
(110, '2025-11', 2, 7000000.00, 0.00, '2025-11-01 07:27:16'),
(111, '2025-11', 3, 17000000.00, 0.00, '2025-11-01 07:27:16'),
(112, '2025-11', 4, 25000000.00, 0.00, '2025-11-01 07:27:16'),
(113, '2025-11', 5, 0.00, 416667.00, '2025-11-01 07:27:16'),
(114, '2025-11', 8, 0.00, 100000000.00, '2025-11-01 07:27:16'),
(115, '2025-11', 9, 2000000.00, 0.00, '2025-11-01 07:27:16'),
(116, '2025-11', 10, 0.00, 15900000.00, '2025-11-01 07:27:16'),
(117, '2025-11', 11, 0.00, 350000.00, '2025-11-01 07:27:16'),
(118, '2025-11', 13, 0.00, 1500000.00, '2025-11-01 07:27:16'),
(119, '2025-11', 14, 5000000.00, 0.00, '2025-11-01 07:27:16'),
(120, '2025-11', 15, 1500000.00, 0.00, '2025-11-01 07:27:16'),
(121, '2025-11', 16, 6000000.00, 0.00, '2025-11-01 07:27:16'),
(122, '2025-11', 17, 3000000.00, 0.00, '2025-11-01 07:27:16'),
(123, '2025-11', 18, 416667.00, 0.00, '2025-11-01 07:27:16'),
(124, '2025-11', 19, 500000.00, 0.00, '2025-11-01 07:27:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran_piutang`
--

CREATE TABLE `pembayaran_piutang` (
  `id_pembayaran` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `id_piutang` int(11) NOT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembayaran_piutang`
--

INSERT INTO `pembayaran_piutang` (`id_pembayaran`, `tanggal`, `id_piutang`, `jumlah_bayar`, `metode_pembayaran`, `keterangan`, `created_by`, `created_at`) VALUES
(5, '2025-10-20', 1, 2000000.00, 'Transfer Bank', 'Pembayaran cicilan ke-2 dari PT. ABC', 1, '2025-11-01 02:49:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendapatan_lainnya`
--

CREATE TABLE `pendapatan_lainnya` (
  `id_pendapatan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `sumber_pendapatan` varchar(100) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pendapatan_lainnya`
--

INSERT INTO `pendapatan_lainnya` (`id_pendapatan`, `tanggal`, `sumber_pendapatan`, `jumlah`, `keterangan`, `created_by`, `created_at`) VALUES
(2, '2025-10-22', 'Sewa Komputer & Printer untuk Event', 1500000.00, 'Sewa 5 unit komputer + 2 printer untuk acara kampus 2 hari', 1, '2025-11-01 02:49:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `piutang`
--

CREATE TABLE `piutang` (
  `id_piutang` int(11) NOT NULL,
  `no_piutang` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `id_pelanggan` int(11) NOT NULL,
  `jenis_jasa` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  `dibayar` decimal(15,2) DEFAULT 0.00,
  `sisa` decimal(15,2) NOT NULL,
  `jatuh_tempo` date NOT NULL,
  `status` enum('Belum Lunas','Lunas') DEFAULT 'Belum Lunas',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `piutang`
--

INSERT INTO `piutang` (`id_piutang`, `no_piutang`, `tanggal`, `id_pelanggan`, `jenis_jasa`, `kategori`, `total`, `dibayar`, `sisa`, `jatuh_tempo`, `status`, `created_by`, `created_at`) VALUES
(6, 'INV-202510001', '2025-10-12', 1, 'Cetak Banner 2x3 meter (5 buah) + Desain Custom Logo', 'Printing', 5000000.00, 1000000.00, 4000000.00, '2025-11-12', 'Belum Lunas', 1, '2025-11-01 02:49:45'),
(7, 'INV-202510002', '2025-10-18', 2, 'Cetak Undangan Pernikahan 500 pcs + Amplop + Box Custom', 'Printing', 7500000.00, 2500000.00, 5000000.00, '2025-11-18', 'Belum Lunas', 1, '2025-11-01 02:49:45'),
(8, 'INV-2025110001', '2025-11-07', 5, 'Banner 1x2', 'Fotocopy', 1000000.00, 500000.00, 500000.00, '2025-11-08', 'Belum Lunas', 1, '2025-11-07 12:14:20'),
(9, 'INV-2025110002', '2025-11-07', 2, 'Cetak Poster', 'Printing', 200000.00, 0.00, 200000.00, '2025-11-10', 'Belum Lunas', 1, '2025-11-07 12:18:26');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_pendapatan`
--

CREATE TABLE `transaksi_pendapatan` (
  `id_transaksi` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `id_pelanggan` int(11) DEFAULT NULL,
  `jenis_jasa` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi_pendapatan`
--

INSERT INTO `transaksi_pendapatan` (`id_transaksi`, `tanggal`, `id_pelanggan`, `jenis_jasa`, `kategori`, `jumlah`, `metode_pembayaran`, `keterangan`, `created_by`, `created_at`) VALUES
(6, '2025-10-10', 5, 'Cetak Brosur A4 Warna 500 lembar + Desain Layout', 'Printing', 2500000.00, 'Tunai', 'Pelanggan walk-in untuk promosi toko', 1, '2025-11-01 02:49:45'),
(7, '2025-10-15', 5, 'Fotocopy A4 BW 1000 lembar + Jilid Spiral 2 buah', 'Fotocopy', 350000.00, 'Tunai', 'Mahasiswa untuk skripsi', 1, '2025-11-01 02:49:45'),
(8, '2025-10-25', 3, 'Cetak Kartu Nama 2 box + Desain Logo Perusahaan Baru', 'Printing', 800000.00, 'E-Wallet', 'Startup baru di bidang F&B', 1, '2025-11-01 02:49:45'),
(9, '2025-11-01', 5, 'Cetak A4 Warna', 'Printing', 100000.00, 'Tunai', '', 1, '2025-11-01 07:25:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('kasir','akuntan','owner') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama`, `role`, `created_at`) VALUES
(1, 'kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir', 'kasir', '2025-10-30 05:51:07'),
(2, 'akuntan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Akuntan', 'akuntan', '2025-10-30 05:51:07'),
(3, 'owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Owner', 'owner', '2025-10-30 05:51:07');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `jurnal_penyesuaian`
--
ALTER TABLE `jurnal_penyesuaian`
  ADD PRIMARY KEY (`id_penyesuaian`),
  ADD KEY `id_akun_debit` (`id_akun_debit`),
  ADD KEY `id_akun_kredit` (`id_akun_kredit`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `voided_by` (`voided_by`),
  ADD KEY `idx_is_void_penyesuaian` (`is_void`);

--
-- Indeks untuk tabel `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  ADD PRIMARY KEY (`id_jurnal`),
  ADD KEY `id_akun_debit` (`id_akun_debit`),
  ADD KEY `id_akun_kredit` (`id_akun_kredit`),
  ADD KEY `voided_by` (`voided_by`),
  ADD KEY `idx_is_void` (`is_void`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_referensi` (`referensi`);

--
-- Indeks untuk tabel `laba_rugi`
--
ALTER TABLE `laba_rugi`
  ADD PRIMARY KEY (`id_laba_rugi`);

--
-- Indeks untuk tabel `master_akun`
--
ALTER TABLE `master_akun`
  ADD PRIMARY KEY (`id_akun`),
  ADD UNIQUE KEY `kode_akun` (`kode_akun`);

--
-- Indeks untuk tabel `master_jasa`
--
ALTER TABLE `master_jasa`
  ADD PRIMARY KEY (`id_jasa`);

--
-- Indeks untuk tabel `master_pelanggan`
--
ALTER TABLE `master_pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indeks untuk tabel `modal`
--
ALTER TABLE `modal`
  ADD PRIMARY KEY (`id_modal`);

--
-- Indeks untuk tabel `neraca_saldo`
--
ALTER TABLE `neraca_saldo`
  ADD PRIMARY KEY (`id_neraca`),
  ADD KEY `id_akun` (`id_akun`);

--
-- Indeks untuk tabel `neraca_saldo_penyesuaian`
--
ALTER TABLE `neraca_saldo_penyesuaian`
  ADD PRIMARY KEY (`id_neraca_penyesuaian`),
  ADD KEY `id_akun` (`id_akun`);

--
-- Indeks untuk tabel `pembayaran_piutang`
--
ALTER TABLE `pembayaran_piutang`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_piutang` (`id_piutang`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `pendapatan_lainnya`
--
ALTER TABLE `pendapatan_lainnya`
  ADD PRIMARY KEY (`id_pendapatan`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `piutang`
--
ALTER TABLE `piutang`
  ADD PRIMARY KEY (`id_piutang`),
  ADD UNIQUE KEY `no_piutang` (`no_piutang`),
  ADD KEY `id_pelanggan` (`id_pelanggan`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `transaksi_pendapatan`
--
ALTER TABLE `transaksi_pendapatan`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_pelanggan` (`id_pelanggan`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `jurnal_penyesuaian`
--
ALTER TABLE `jurnal_penyesuaian`
  MODIFY `id_penyesuaian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  MODIFY `id_jurnal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT untuk tabel `laba_rugi`
--
ALTER TABLE `laba_rugi`
  MODIFY `id_laba_rugi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `master_akun`
--
ALTER TABLE `master_akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `master_jasa`
--
ALTER TABLE `master_jasa`
  MODIFY `id_jasa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `master_pelanggan`
--
ALTER TABLE `master_pelanggan`
  MODIFY `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `modal`
--
ALTER TABLE `modal`
  MODIFY `id_modal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `neraca_saldo`
--
ALTER TABLE `neraca_saldo`
  MODIFY `id_neraca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT untuk tabel `neraca_saldo_penyesuaian`
--
ALTER TABLE `neraca_saldo_penyesuaian`
  MODIFY `id_neraca_penyesuaian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT untuk tabel `pembayaran_piutang`
--
ALTER TABLE `pembayaran_piutang`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pendapatan_lainnya`
--
ALTER TABLE `pendapatan_lainnya`
  MODIFY `id_pendapatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `piutang`
--
ALTER TABLE `piutang`
  MODIFY `id_piutang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `transaksi_pendapatan`
--
ALTER TABLE `transaksi_pendapatan`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `jurnal_penyesuaian`
--
ALTER TABLE `jurnal_penyesuaian`
  ADD CONSTRAINT `jurnal_penyesuaian_ibfk_1` FOREIGN KEY (`id_akun_debit`) REFERENCES `master_akun` (`id_akun`),
  ADD CONSTRAINT `jurnal_penyesuaian_ibfk_2` FOREIGN KEY (`id_akun_kredit`) REFERENCES `master_akun` (`id_akun`),
  ADD CONSTRAINT `jurnal_penyesuaian_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `jurnal_penyesuaian_ibfk_4` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  ADD CONSTRAINT `jurnal_umum_ibfk_1` FOREIGN KEY (`id_akun_debit`) REFERENCES `master_akun` (`id_akun`),
  ADD CONSTRAINT `jurnal_umum_ibfk_2` FOREIGN KEY (`id_akun_kredit`) REFERENCES `master_akun` (`id_akun`),
  ADD CONSTRAINT `jurnal_umum_ibfk_3` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `neraca_saldo`
--
ALTER TABLE `neraca_saldo`
  ADD CONSTRAINT `neraca_saldo_ibfk_1` FOREIGN KEY (`id_akun`) REFERENCES `master_akun` (`id_akun`);

--
-- Ketidakleluasaan untuk tabel `neraca_saldo_penyesuaian`
--
ALTER TABLE `neraca_saldo_penyesuaian`
  ADD CONSTRAINT `neraca_saldo_penyesuaian_ibfk_1` FOREIGN KEY (`id_akun`) REFERENCES `master_akun` (`id_akun`);

--
-- Ketidakleluasaan untuk tabel `pembayaran_piutang`
--
ALTER TABLE `pembayaran_piutang`
  ADD CONSTRAINT `pembayaran_piutang_ibfk_1` FOREIGN KEY (`id_piutang`) REFERENCES `piutang` (`id_piutang`),
  ADD CONSTRAINT `pembayaran_piutang_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `pendapatan_lainnya`
--
ALTER TABLE `pendapatan_lainnya`
  ADD CONSTRAINT `pendapatan_lainnya_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `piutang`
--
ALTER TABLE `piutang`
  ADD CONSTRAINT `piutang_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `master_pelanggan` (`id_pelanggan`),
  ADD CONSTRAINT `piutang_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`);

--
-- Ketidakleluasaan untuk tabel `transaksi_pendapatan`
--
ALTER TABLE `transaksi_pendapatan`
  ADD CONSTRAINT `transaksi_pendapatan_ibfk_1` FOREIGN KEY (`id_pelanggan`) REFERENCES `master_pelanggan` (`id_pelanggan`),
  ADD CONSTRAINT `transaksi_pendapatan_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
