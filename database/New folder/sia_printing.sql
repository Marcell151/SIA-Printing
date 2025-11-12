-- Database with Initial Transactions
-- Data transaksi awal usaha sudah termasuk

USE sia_printing;

-- Insert transaksi awal usaha (Oktober 2025)

-- 1. Owner menyetor modal awal Rp 100.000.000 (01 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-01', 'Setoran Modal Awal Pemilik', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '3-101'), 
 100000000, 'MODAL-001', 'Modal Awal');

-- Update saldo akun
UPDATE master_akun SET saldo = 100000000 WHERE kode_akun = '1-101'; -- Kas
UPDATE master_akun SET saldo = 100000000 WHERE kode_akun = '3-101'; -- Modal

-- 2. Pembelian Perlengkapan Rp 20.000.000 (02 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-02', 'Pembelian Perlengkapan Kantor dan Operasional', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 20000000, 'BUY-001', 'Pembelian Aset');

UPDATE master_akun SET saldo = 80000000 WHERE kode_akun = '1-101'; -- Kas berkurang
UPDATE master_akun SET saldo = 20000000 WHERE kode_akun = '1-103'; -- Perlengkapan

-- 3. Pembelian Peralatan Rp 25.000.000 (03 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-03', 'Pembelian Peralatan Printing (Mesin, Komputer, dll)', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-201'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 25000000, 'BUY-002', 'Pembelian Aset');

UPDATE master_akun SET saldo = 55000000 WHERE kode_akun = '1-101'; -- Kas berkurang
UPDATE master_akun SET saldo = 25000000 WHERE kode_akun = '1-201'; -- Peralatan

-- 4. Pembayaran Sewa Tempat 3 bulan Rp 6.000.000 (04 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-04', 'Pembayaran Sewa Tempat Usaha 3 Bulan (Okt-Des 2025)', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-103'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 6000000, 'RENT-001', 'Beban');

UPDATE master_akun SET saldo = 49000000 WHERE kode_akun = '1-101'; -- Kas
UPDATE master_akun SET saldo = 6000000 WHERE kode_akun = '5-103'; -- Beban Sewa

-- 5. Pembayaran Gaji Karyawan Bulan Oktober Rp 5.000.000 (05 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-05', 'Pembayaran Gaji Karyawan Bulan Oktober 2025', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 5000000, 'SALARY-001', 'Beban');

UPDATE master_akun SET saldo = 44000000 WHERE kode_akun = '1-101'; -- Kas
UPDATE master_akun SET saldo = 5000000 WHERE kode_akun = '5-101'; -- Beban Gaji

-- 6. Pembayaran Listrik & Utilitas Rp 1.500.000 (06 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-06', 'Pembayaran Listrik dan Utilitas Bulan Oktober', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 1500000, 'UTIL-001', 'Beban');

UPDATE master_akun SET saldo = 42500000 WHERE kode_akun = '1-101'; -- Kas
UPDATE master_akun SET saldo = 1500000 WHERE kode_akun = '5-102'; -- Beban Listrik

-- 7. Prive Owner Rp 2.000.000 (07 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-07', 'Pengambilan Prive untuk Keperluan Pribadi Owner', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '3-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 2000000, 'PRIVE-001', 'Prive');

UPDATE master_akun SET saldo = 40500000 WHERE kode_akun = '1-101'; -- Kas
UPDATE master_akun SET saldo = 2000000 WHERE kode_akun = '3-102'; -- Prive

-- 8-15. Transaksi Pendapatan (10-25 Oktober 2025)
-- Pendapatan Tunai 1
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-10', 5, 'Cetak Brosur A4 Warna 500 lembar', 'Printing', 2500000, 'Tunai', 'Pelanggan walk-in', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-10', 'Pendapatan Tunai - Cetak Brosur A4 Warna 500 lembar', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 2500000, 'PT-1', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '4-101';

-- Pendapatan Kredit 1
INSERT INTO piutang (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) VALUES
('INV-202510001', '2025-10-12', 1, 'Cetak Banner 2x3 meter + Desain', 'Printing', 5000000, 1000000, 4000000, '2025-11-12', 'Belum Lunas', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-12', 'Pendapatan Kredit - Cetak Banner 2x3 meter + Desain - INV-202510001', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 5000000, 'INV-202510001', 'Pendapatan Kredit');

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-12', 'DP Piutang - INV-202510001', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 1000000, 'INV-202510001', 'DP Piutang');

UPDATE master_akun SET saldo = saldo + 5000000 WHERE kode_akun = '1-102'; -- Piutang
UPDATE master_akun SET saldo = saldo + 5000000 WHERE kode_akun = '4-101'; -- Pendapatan
UPDATE master_akun SET saldo = saldo + 1000000 WHERE kode_akun = '1-101'; -- Kas dari DP
UPDATE master_akun SET saldo = saldo - 1000000 WHERE kode_akun = '1-102'; -- Piutang berkurang

-- Pendapatan Tunai 2
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-15', 5, 'Fotocopy A4 BW 1000 lembar + Jilid', 'Fotocopy', 350000, 'Tunai', 'Mahasiswa', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-15', 'Pendapatan Tunai - Fotocopy A4 BW 1000 lembar + Jilid', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-102'), 
 350000, 'PT-2', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 350000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 350000 WHERE kode_akun = '4-102';

-- Pendapatan Kredit 2
INSERT INTO piutang (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) VALUES
('INV-202510002', '2025-10-18', 2, 'Cetak Undangan Pernikahan 500 pcs', 'Printing', 7500000, 2500000, 5000000, '2025-11-18', 'Belum Lunas', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-18', 'Pendapatan Kredit - Cetak Undangan Pernikahan 500 pcs - INV-202510002', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 7500000, 'INV-202510002', 'Pendapatan Kredit');

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-18', 'DP Piutang - INV-202510002', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 2500000, 'INV-202510002', 'DP Piutang');

UPDATE master_akun SET saldo = saldo + 7500000 WHERE kode_akun = '1-102';
UPDATE master_akun SET saldo = saldo + 7500000 WHERE kode_akun = '4-101';
UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo - 2500000 WHERE kode_akun = '1-102';

-- Penerimaan Piutang 1
INSERT INTO pembayaran_piutang (tanggal, id_piutang, jumlah_bayar, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-20', 1, 2000000, 'Transfer Bank', 'Pembayaran cicilan ke-2', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-20', 'Penerimaan Pembayaran Piutang - INV-202510001', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 2000000, 'INV-202510001', 'Penerimaan Piutang');

UPDATE piutang SET dibayar = dibayar + 2000000, sisa = sisa - 2000000 WHERE id_piutang = 1;
UPDATE master_akun SET saldo = saldo + 2000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo - 2000000 WHERE kode_akun = '1-102';

-- Pendapatan Lainnya 1
INSERT INTO pendapatan_lainnya (tanggal, sumber_pendapatan, jumlah, keterangan, created_by) VALUES
('2025-10-22', 'Sewa Komputer untuk Event', 1500000, 'Sewa 5 unit komputer untuk event 2 hari', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-22', 'Pendapatan Lain-Lain - Sewa Komputer untuk Event', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-104'), 
 1500000, 'PL-1', 'Pendapatan Lainnya');

UPDATE master_akun SET saldo = saldo + 1500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 1500000 WHERE kode_akun = '4-104';

-- Pendapatan Tunai 3
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-25', 3, 'Cetak Kartu Nama + Desain', 'Printing', 800000, 'E-Wallet', 'Startup baru', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-25', 'Pendapatan Tunai - Cetak Kartu Nama + Desain', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 800000, 'PT-3', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 800000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 800000 WHERE kode_akun = '4-101';

-- Beban tambahan
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-28', 'Beban Lain-Lain - Biaya maintenance mesin', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-106'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 500000, 'MISC-001', 'Beban');

UPDATE master_akun SET saldo = saldo - 500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 500000 WHERE kode_akun = '5-106';

-- JURNAL PENYESUAIAN (31 Oktober 2025)

-- 1. Penyesuaian Perlengkapan yang terpakai selama Oktober
INSERT INTO jurnal_penyesuaian (tanggal, id_akun_debit, id_akun_kredit, nominal, deskripsi, periode, created_by) VALUES
('2025-10-31', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-104'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 3000000, 'Penyesuaian perlengkapan yang terpakai selama bulan Oktober 2025 (kertas, tinta, dll)', '2025-10', 2);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-31', 'Penyesuaian: Penyesuaian perlengkapan yang terpakai selama bulan Oktober 2025 (kertas, tinta, dll)', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-104'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 3000000, 'AJE-1', 'Jurnal Penyesuaian');

UPDATE master_akun SET saldo = saldo + 3000000 WHERE kode_akun = '5-104';
UPDATE master_akun SET saldo = saldo - 3000000 WHERE kode_akun = '1-103';

-- 2. Penyusutan Peralatan (Asumsi umur ekonomis 5 tahun = 60 bulan)
-- Penyusutan per bulan = 25.000.000 / 60 = 416.667
INSERT INTO jurnal_penyesuaian (tanggal, id_akun_debit, id_akun_kredit, nominal, deskripsi, periode, created_by) VALUES
('2025-10-31', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-105'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-202'), 
 416667, 'Penyusutan peralatan bulan Oktober 2025 (umur ekonomis 5 tahun, metode garis lurus)', '2025-10', 2);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-31', 'Penyesuaian: Penyusutan peralatan bulan Oktober 2025 (umur ekonomis 5 tahun, metode garis lurus)', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-105'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-202'), 
 416667, 'AJE-2', 'Jurnal Penyesuaian');

UPDATE master_akun SET saldo = saldo + 416667 WHERE kode_akun = '5-105';
UPDATE master_akun SET saldo = saldo + 416667 WHERE kode_akun = '1-202';

-- Summary saldo akhir Oktober 2025
-- Kas: 50.150.000
-- Piutang: 9.000.000
-- Perlengkapan: 17.000.000
-- Peralatan: 25.000.000
-- Akumulasi Penyusutan: 416.667
-- Modal: 100.000.000
-- Prive: 2.000.000
-- Pendapatan: 19.650.000
-- Beban: 16.416.667
-- Laba Bersih: 3.233.333