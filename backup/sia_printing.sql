-- Database: sia_printing
-- Sistem Informasi Akuntansi - Revenue Cycle

CREATE DATABASE IF NOT EXISTS sia_printing;
USE sia_printing;

-- Tabel User (untuk login)
CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('kasir', 'akuntan', 'owner') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Master Pelanggan
CREATE TABLE master_pelanggan (
    id_pelanggan INT PRIMARY KEY AUTO_INCREMENT,
    nama_pelanggan VARCHAR(100) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Master Jasa
CREATE TABLE master_jasa (
    id_jasa INT PRIMARY KEY AUTO_INCREMENT,
    nama_jasa VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    harga_satuan DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Master Akun (Chart of Account)
CREATE TABLE master_akun (
    id_akun INT PRIMARY KEY AUTO_INCREMENT,
    kode_akun VARCHAR(20) UNIQUE NOT NULL,
    nama_akun VARCHAR(100) NOT NULL,
    tipe_akun ENUM('1-Aktiva', '2-Kewajiban', '3-Modal', '4-Pendapatan', '5-Beban') NOT NULL,
    saldo DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Transaksi Pendapatan (Pendapatan Tunai)
CREATE TABLE transaksi_pendapatan (
    id_transaksi INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    id_pelanggan INT,
    jenis_jasa VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(50) NOT NULL,
    keterangan TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES master_pelanggan(id_pelanggan),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Tabel Piutang
CREATE TABLE piutang (
    id_piutang INT PRIMARY KEY AUTO_INCREMENT,
    no_piutang VARCHAR(50) UNIQUE NOT NULL,
    tanggal DATE NOT NULL,
    id_pelanggan INT NOT NULL,
    jenis_jasa VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    dibayar DECIMAL(15,2) DEFAULT 0,
    sisa DECIMAL(15,2) NOT NULL,
    jatuh_tempo DATE NOT NULL,
    status ENUM('Belum Lunas', 'Lunas') DEFAULT 'Belum Lunas',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES master_pelanggan(id_pelanggan),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Tabel Pembayaran Piutang
CREATE TABLE pembayaran_piutang (
    id_pembayaran INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    id_piutang INT NOT NULL,
    jumlah_bayar DECIMAL(15,2) NOT NULL,
    metode_pembayaran VARCHAR(50) NOT NULL,
    keterangan TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_piutang) REFERENCES piutang(id_piutang),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Tabel Pendapatan Lainnya
CREATE TABLE pendapatan_lainnya (
    id_pendapatan INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    sumber_pendapatan VARCHAR(100) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Tabel Jurnal Umum
CREATE TABLE jurnal_umum (
    id_jurnal INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    deskripsi TEXT NOT NULL,
    id_akun_debit INT NOT NULL,
    id_akun_kredit INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    referensi VARCHAR(50),
    tipe_transaksi VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_akun_debit) REFERENCES master_akun(id_akun),
    FOREIGN KEY (id_akun_kredit) REFERENCES master_akun(id_akun)
);

-- Tabel Jurnal Penyesuaian
CREATE TABLE jurnal_penyesuaian (
    id_penyesuaian INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    id_akun_debit INT NOT NULL,
    id_akun_kredit INT NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    deskripsi TEXT NOT NULL,
    periode VARCHAR(20) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_akun_debit) REFERENCES master_akun(id_akun),
    FOREIGN KEY (id_akun_kredit) REFERENCES master_akun(id_akun),
    FOREIGN KEY (created_by) REFERENCES users(id_user)
);

-- Tabel Neraca Saldo
CREATE TABLE neraca_saldo (
    id_neraca INT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(20) NOT NULL,
    id_akun INT NOT NULL,
    saldo_debit DECIMAL(15,2) DEFAULT 0,
    saldo_kredit DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_akun) REFERENCES master_akun(id_akun)
);

-- Tabel Neraca Saldo Setelah Penyesuaian
CREATE TABLE neraca_saldo_penyesuaian (
    id_neraca_penyesuaian INT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(20) NOT NULL,
    id_akun INT NOT NULL,
    saldo_debit DECIMAL(15,2) DEFAULT 0,
    saldo_kredit DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_akun) REFERENCES master_akun(id_akun)
);

-- Tabel Laba Rugi
CREATE TABLE laba_rugi (
    id_laba_rugi INT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(20) NOT NULL,
    total_pendapatan DECIMAL(15,2) NOT NULL,
    total_beban DECIMAL(15,2) NOT NULL,
    laba_rugi DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Modal
CREATE TABLE modal (
    id_modal INT PRIMARY KEY AUTO_INCREMENT,
    periode VARCHAR(20) NOT NULL,
    modal_awal DECIMAL(15,2) NOT NULL,
    prive DECIMAL(15,2) DEFAULT 0,
    laba_rugi DECIMAL(15,2) NOT NULL,
    modal_akhir DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Data User Default
INSERT INTO users (username, password, nama, role) VALUES
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir', 'kasir'), -- password: password
('akuntan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Akuntan', 'akuntan'), -- password: password
('owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Owner', 'owner'); -- password: password

-- Insert Master Akun (Chart of Account)
INSERT INTO master_akun (kode_akun, nama_akun, tipe_akun, saldo) VALUES
-- AKTIVA
('1-101', 'Kas', '1-Aktiva', 10000000),
('1-102', 'Piutang Usaha', '1-Aktiva', 0),
('1-103', 'Perlengkapan', '1-Aktiva', 2000000),
('1-201', 'Peralatan', '1-Aktiva', 15000000),
('1-202', 'Akumulasi Penyusutan Peralatan', '1-Aktiva', 0),

-- KEWAJIBAN
('2-101', 'Utang Usaha', '2-Kewajiban', 0),
('2-102', 'Pendapatan Diterima Dimuka', '2-Kewajiban', 0),

-- MODAL
('3-101', 'Modal Pemilik', '3-Modal', 20000000),
('3-102', 'Prive', '3-Modal', 0),

-- PENDAPATAN
('4-101', 'Pendapatan Jasa Printing', '4-Pendapatan', 0),
('4-102', 'Pendapatan Jasa Fotocopy', '4-Pendapatan', 0),
('4-103', 'Pendapatan Jasa Jilid', '4-Pendapatan', 0),
('4-104', 'Pendapatan Lain-Lain', '4-Pendapatan', 0),

-- BEBAN
('5-101', 'Beban Gaji', '5-Beban', 0),
('5-102', 'Beban Listrik', '5-Beban', 0),
('5-103', 'Beban Sewa', '5-Beban', 0),
('5-104', 'Beban Perlengkapan', '5-Beban', 0),
('5-105', 'Beban Penyusutan', '5-Beban', 0),
('5-106', 'Beban Lain-Lain', '5-Beban', 0);

-- Insert Master Pelanggan Sample
INSERT INTO master_pelanggan (nama_pelanggan, alamat, telepon, email) VALUES
('PT. ABC Indonesia', 'Jl. Sudirman No. 123, Jakarta', '021-1234567', 'contact@abc.co.id'),
('CV. XYZ Mandiri', 'Jl. Gatot Subroto No. 45, Bandung', '022-7654321', 'info@xyz.co.id'),
('Toko Maju Jaya', 'Jl. Ahmad Yani No. 78, Surabaya', '031-9876543', 'tokumaju@gmail.com'),
('UD. Berkah Sejahtera', 'Jl. Diponegoro No. 56, Malang', '0341-555666', 'berkah@yahoo.com'),
('Umum', 'Walk-in Customer', '-', '-');

-- Insert Master Jasa Sample
INSERT INTO master_jasa (nama_jasa, kategori, harga_satuan) VALUES
('Cetak A4 Hitam Putih', 'Printing', 500),
('Cetak A4 Warna', 'Printing', 2000),
('Cetak A3 Hitam Putih', 'Printing', 1000),
('Cetak A3 Warna', 'Printing', 3000),
('Fotocopy A4', 'Fotocopy', 300),
('Fotocopy A3', 'Fotocopy', 500),
('Jilid Spiral', 'Jilid', 5000),
('Jilid Hard Cover', 'Jilid', 15000),
('Laminating A4', 'Laminasi', 3000),
('Desain Grafis', 'Desain', 50000);