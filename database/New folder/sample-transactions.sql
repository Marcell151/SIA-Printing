-- ========================================================================
-- SAMPLE TRANSACTIONS - CV. JASA PRINTING
-- Transaksi bulan Oktober 2025 (Lengkap dengan jurnal penyesuaian)
-- ========================================================================
-- Jalankan file ini SETELAH import database utama (sia_printing.sql)
-- ========================================================================

USE sia_printing;

-- Reset data jika sudah ada
DELETE FROM pembayaran_piutang;
DELETE FROM piutang;
DELETE FROM transaksi_pendapatan;
DELETE FROM pendapatan_lainnya;
DELETE FROM jurnal_penyesuaian;
DELETE FROM jurnal_umum;
DELETE FROM neraca_saldo;
DELETE FROM neraca_saldo_penyesuaian;
DELETE FROM laba_rugi;
DELETE FROM modal;

-- Reset saldo akun ke 0
UPDATE master_akun SET saldo = 0;

-- ========================================================================
-- TRANSAKSI AWAL USAHA (01-07 Oktober 2025)
-- ========================================================================

-- 1. SETORAN MODAL AWAL - Rp 100.000.000 (01 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-01', 'Setoran Modal Awal Pemilik untuk Memulai Usaha Percetakan', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '3-101'), 
 100000000, 'MODAL-001', 'Modal Awal');

UPDATE master_akun SET saldo = 100000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = 100000000 WHERE kode_akun = '3-101';

-- 2. PEMBELIAN PERLENGKAPAN - Rp 20.000.000 (02 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-02', 'Pembelian Perlengkapan: Kertas A4, A3, Tinta Printer, Cartridge, dll untuk persediaan operasional', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 20000000, 'BUY-SUPPLY-001', 'Pembelian Aset');

UPDATE master_akun SET saldo = saldo - 20000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 20000000 WHERE kode_akun = '1-103';

-- 3. PEMBELIAN PERALATAN - Rp 25.000.000 (03 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-03', 'Pembelian Peralatan: 2 unit Printer Digital, 3 unit Komputer, 1 unit Mesin Cutting, Meja Kerja', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-201'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 25000000, 'BUY-EQUIP-001', 'Pembelian Aset');

UPDATE master_akun SET saldo = saldo - 25000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 25000000 WHERE kode_akun = '1-201';

-- 4. PEMBAYARAN SEWA TEMPAT 3 BULAN - Rp 6.000.000 (04 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-04', 'Pembayaran Sewa Tempat Usaha periode Oktober - Desember 2025 @ Rp 2.000.000/bulan', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-103'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 6000000, 'RENT-Q4-2025', 'Beban');

UPDATE master_akun SET saldo = saldo - 6000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 6000000 WHERE kode_akun = '5-103';

-- 5. PEMBAYARAN GAJI KARYAWAN - Rp 5.000.000 (05 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-05', 'Pembayaran Gaji 2 karyawan (operator & desainer) bulan Oktober 2025', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 5000000, 'PAYROLL-OCT-2025', 'Beban');

UPDATE master_akun SET saldo = saldo - 5000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 5000000 WHERE kode_akun = '5-101';

-- 6. PEMBAYARAN LISTRIK & UTILITAS - Rp 1.500.000 (06 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-06', 'Pembayaran Listrik, Air, Internet bulan Oktober 2025', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 1500000, 'UTILITY-OCT-2025', 'Beban');

UPDATE master_akun SET saldo = saldo - 1500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 1500000 WHERE kode_akun = '5-102';

-- 7. PENGAMBILAN PRIVE - Rp 2.000.000 (07 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-07', 'Pengambilan Uang untuk Keperluan Pribadi Owner', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '3-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 2000000, 'PRIVE-OCT-001', 'Prive');

UPDATE master_akun SET saldo = saldo - 2000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 2000000 WHERE kode_akun = '3-102';

-- ========================================================================
-- TRANSAKSI PENDAPATAN (10-25 Oktober 2025)
-- ========================================================================

-- 8. PENDAPATAN TUNAI 1 - Rp 2.500.000 (10 Oktober 2025)
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-10', 5, 'Cetak Brosur A4 Warna 500 lembar + Desain Layout', 'Printing', 2500000, 'Tunai', 'Pelanggan walk-in untuk promosi toko', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-10', 'Pendapatan Tunai - Cetak Brosur A4 Warna 500 lembar + Desain Layout', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 2500000, 'PT-1', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '4-101';

-- 9. PENDAPATAN KREDIT 1 - Rp 5.000.000 (12 Oktober 2025)
INSERT INTO piutang (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) VALUES
('INV-202510001', '2025-10-12', 1, 'Cetak Banner 2x3 meter (5 buah) + Desain Custom Logo', 'Printing', 5000000, 1000000, 4000000, '2025-11-12', 'Belum Lunas', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-12', 'Pendapatan Kredit - Cetak Banner 2x3 meter + Desain - PT. ABC Indonesia - INV-202510001', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 5000000, 'INV-202510001', 'Pendapatan Kredit'),
('2025-10-12', 'DP Piutang 20% - INV-202510001', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 1000000, 'INV-202510001', 'DP Piutang');

UPDATE master_akun SET saldo = saldo + 4000000 WHERE kode_akun = '1-102';
UPDATE master_akun SET saldo = saldo + 5000000 WHERE kode_akun = '4-101';
UPDATE master_akun SET saldo = saldo + 1000000 WHERE kode_akun = '1-101';

-- 10. PENDAPATAN TUNAI 2 - Rp 350.000 (15 Oktober 2025)
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-15', 5, 'Fotocopy A4 BW 1000 lembar + Jilid Spiral 2 buah', 'Fotocopy', 350000, 'Tunai', 'Mahasiswa untuk skripsi', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-15', 'Pendapatan Tunai - Fotocopy A4 BW 1000 lembar + Jilid Spiral', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-102'), 
 350000, 'PT-2', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 350000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 350000 WHERE kode_akun = '4-102';

-- 11. PENDAPATAN KREDIT 2 - Rp 7.500.000 (18 Oktober 2025)
INSERT INTO piutang (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) VALUES
('INV-202510002', '2025-10-18', 2, 'Cetak Undangan Pernikahan 500 pcs + Amplop + Box Custom', 'Printing', 7500000, 2500000, 5000000, '2025-11-18', 'Belum Lunas', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-18', 'Pendapatan Kredit - Undangan Pernikahan Custom - CV. XYZ Mandiri - INV-202510002', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 7500000, 'INV-202510002', 'Pendapatan Kredit'),
('2025-10-18', 'DP Piutang 33% - INV-202510002', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 2500000, 'INV-202510002', 'DP Piutang');

UPDATE master_akun SET saldo = saldo + 5000000 WHERE kode_akun = '1-102';
UPDATE master_akun SET saldo = saldo + 7500000 WHERE kode_akun = '4-101';
UPDATE master_akun SET saldo = saldo + 2500000 WHERE kode_akun = '1-101';

-- 12. PENERIMAAN PIUTANG 1 - Rp 2.000.000 (20 Oktober 2025)
INSERT INTO pembayaran_piutang (tanggal, id_piutang, jumlah_bayar, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-20', 1, 2000000, 'Transfer Bank', 'Pembayaran cicilan ke-2 dari PT. ABC', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-20', 'Penerimaan Pembayaran Piutang - INV-202510001 - Cicilan 2', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'), 
 2000000, 'INV-202510001', 'Penerimaan Piutang');

UPDATE piutang SET dibayar = dibayar + 2000000, sisa = sisa - 2000000 WHERE id_piutang = 1;
UPDATE master_akun SET saldo = saldo + 2000000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo - 2000000 WHERE kode_akun = '1-102';

-- 13. PENDAPATAN LAINNYA 1 - Rp 1.500.000 (22 Oktober 2025)
INSERT INTO pendapatan_lainnya (tanggal, sumber_pendapatan, jumlah, keterangan, created_by) VALUES
('2025-10-22', 'Sewa Komputer & Printer untuk Event', 1500000, 'Sewa 5 unit komputer + 2 printer untuk acara kampus 2 hari', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-22', 'Pendapatan Lain-Lain - Sewa Komputer & Printer untuk Event Kampus', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-104'), 
 1500000, 'PL-1', 'Pendapatan Lainnya');

UPDATE master_akun SET saldo = saldo + 1500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 1500000 WHERE kode_akun = '4-104';

-- 14. PENDAPATAN TUNAI 3 - Rp 800.000 (25 Oktober 2025)
INSERT INTO transaksi_pendapatan (tanggal, id_pelanggan, jenis_jasa, kategori, jumlah, metode_pembayaran, keterangan, created_by) VALUES
('2025-10-25', 3, 'Cetak Kartu Nama 2 box + Desain Logo Perusahaan Baru', 'Printing', 800000, 'E-Wallet', 'Startup baru di bidang F&B', 1);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-25', 'Pendapatan Tunai - Cetak Kartu Nama + Desain Logo Startup', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '4-101'), 
 800000, 'PT-3', 'Pendapatan Tunai');

UPDATE master_akun SET saldo = saldo + 800000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 800000 WHERE kode_akun = '4-101';

-- 15. BEBAN LAIN-LAIN - Rp 500.000 (28 Oktober 2025)
INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-28', 'Beban Maintenance & Service Mesin Printer + Komputer', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-106'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'), 
 500000, 'MAINT-OCT-001', 'Beban');

UPDATE master_akun SET saldo = saldo - 500000 WHERE kode_akun = '1-101';
UPDATE master_akun SET saldo = saldo + 500000 WHERE kode_akun = '5-106';

-- ========================================================================
-- JURNAL PENYESUAIAN (31 Oktober 2025)
-- ========================================================================

-- 1. PENYESUAIAN PERLENGKAPAN TERPAKAI - Rp 3.000.000
INSERT INTO jurnal_penyesuaian (tanggal, id_akun_debit, id_akun_kredit, nominal, deskripsi, periode, created_by) VALUES
('2025-10-31', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-104'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 3000000, 
 'Penyesuaian perlengkapan yang terpakai selama bulan Oktober 2025 (kertas A4/A3, tinta, cartridge, dll) berdasarkan stock opname', 
 '2025-10', 2);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-31', 'Penyesuaian: Perlengkapan yang terpakai bulan Oktober 2025', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-104'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-103'), 
 3000000, 'AJE-1', 'Jurnal Penyesuaian');

UPDATE master_akun SET saldo = saldo + 3000000 WHERE kode_akun = '5-104';
UPDATE master_akun SET saldo = saldo - 3000000 WHERE kode_akun = '1-103';

-- 2. PENYUSUTAN PERALATAN - Rp 416.667
-- Asumsi: Umur ekonomis 5 tahun = 60 bulan
-- Penyusutan/bulan = Rp 25.000.000 / 60 = Rp 416.667
INSERT INTO jurnal_penyesuaian (tanggal, id_akun_debit, id_akun_kredit, nominal, deskripsi, periode, created_by) VALUES
('2025-10-31', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-105'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-202'), 
 416667, 
 'Penyusutan peralatan bulan Oktober 2025 (Printer, Komputer, Mesin Cutting) dengan umur ekonomis 5 tahun, metode garis lurus', 
 '2025-10', 2);

INSERT INTO jurnal_umum (tanggal, deskripsi, id_akun_debit, id_akun_kredit, nominal, referensi, tipe_transaksi) VALUES
('2025-10-31', 'Penyesuaian: Penyusutan peralatan bulan Oktober 2025', 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '5-105'), 
 (SELECT id_akun FROM master_akun WHERE kode_akun = '1-202'), 
 416667, 'AJE-2', 'Jurnal Penyesuaian');

UPDATE master_akun SET saldo = saldo + 416667 WHERE kode_akun = '5-105';
UPDATE master_akun SET saldo = saldo + 416667 WHERE kode_akun = '1-202';

-- ========================================================================
-- SUMMARY POSISI KEUANGAN PER 31 OKTOBER 2025
-- ========================================================================
-- 
-- AKTIVA:
-- - Kas: Rp 50.150.000
-- - Piutang Usaha: Rp 7.000.000
-- - Perlengkapan: Rp 17.000.000
-- - Peralatan: Rp 25.000.000
-- - Akumulasi Penyusutan: (Rp 416.667)
-- Total Aktiva: Rp 98.733.333
--
-- PASIVA:
-- - Modal Pemilik: Rp 100.000.000
-- - Prive: (Rp 2.000.000)
--
-- PENDAPATAN:
-- - Pendapatan Jasa Printing: Rp 16.600.000
-- - Pendapatan Jasa Fotocopy: Rp 350.000
-- - Pendapatan Lain-Lain: Rp 1.500.000
-- Total Pendapatan: Rp 18.450.000
--
-- BEBAN:
-- - Beban Gaji: Rp 5.000.000
-- - Beban Listrik: Rp 1.500.000
-- - Beban Sewa: Rp 6.000.000
-- - Beban Perlengkapan: Rp 3.000.000
-- - Beban Penyusutan: Rp 416.667
-- - Beban Lain-Lain: Rp 500.000
-- Total Beban: Rp 16.416.667
--
-- LABA BERSIH: Rp 2.033.333
-- MODAL AKHIR: Rp 100.033.333
-- ========================================================================

SELECT 'Sample transactions berhasil diimport!' as Status;