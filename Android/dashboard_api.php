<?php
require_once 'config.php';

header('Content-Type: application/json');

$bulan_ini = date('Y-m');

// Total Pendapatan
$query_pendapatan = "SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi_pendapatan 
                     WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$total_pendapatan = $conn->query($query_pendapatan)->fetch_assoc()['total'];

// Total Piutang
$query_piutang = "SELECT COALESCE(SUM(sisa), 0) as total FROM piutang WHERE status = 'Belum Lunas'";
$total_piutang = $conn->query($query_piutang)->fetch_assoc()['total'];

// Penerimaan Kas
$query_kas = "SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi_pendapatan 
              WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$penerimaan_kas = $conn->query($query_kas)->fetch_assoc()['total'];

// Transaksi Hari Ini
$hari_ini = date('Y-m-d');
$query_transaksi = "SELECT 
                    (SELECT COUNT(*) FROM transaksi_pendapatan WHERE tanggal = '$hari_ini') +
                    (SELECT COUNT(*) FROM piutang WHERE tanggal = '$hari_ini') as total";
$transaksi_hari_ini = $conn->query($query_transaksi)->fetch_assoc()['total'];

// Output JSON
echo json_encode([
    'total_pendapatan' => "Rp " . number_format($total_pendapatan, 0, ',', '.'),
    'total_piutang' => "Rp " . number_format($total_piutang, 0, ',', '.'),
    'penerimaan_kas' => "Rp " . number_format($penerimaan_kas, 0, ',', '.'),
    'transaksi_hari_ini' => $transaksi_hari_ini
]);
?>
