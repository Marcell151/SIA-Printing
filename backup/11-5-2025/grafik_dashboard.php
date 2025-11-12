<?php
require_once 'config.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'pendapatan';
$data = ['labels' => [], 'datasets' => [], 'chartType' => 'bar'];

switch ($type) {
    // 📊 Grafik Pendapatan per Bulan
    case 'pendapatan':
        $q = "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(jumlah) AS total 
              FROM transaksi_pendapatan 
              GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
              ORDER BY bulan ASC";
        $r = $conn->query($q);
        while ($row = $r->fetch_assoc()) {
            $data['labels'][] = $row['bulan'];
            $values[] = $row['total'];
        }
        $data['datasets'][] = [
            'label' => 'Total Pendapatan',
            'data' => $values,
            'backgroundColor' => '#4e73df'
        ];
        break;

    // 💳 Grafik Piutang
    case 'piutang':
        $q = "SELECT status, SUM(sisa) AS total FROM piutang GROUP BY status";
        $r = $conn->query($q);
        while ($row = $r->fetch_assoc()) {
            $data['labels'][] = $row['status'];
            $values[] = $row['total'];
        }
        $data['chartType'] = 'doughnut';
        $data['datasets'][] = [
            'label' => 'Total Piutang',
            'data' => $values,
            'backgroundColor' => ['#36b9cc', '#f6c23e']
        ];
        break;

    // // 💰 Grafik Perubahan Modal
    // case 'modal':
    //     $q = "SELECT periode, modal_awal, modal_akhir FROM modal ORDER BY periode ASC";
    //     $r = $conn->query($q);
    //     $modal_awal = [];
    //     $modal_akhir = [];
    //     while ($row = $r->fetch_assoc()) {
    //         $data['labels'][] = $row['periode'];
    //         $modal_awal[] = $row['modal_awal'];
    //         $modal_akhir[] = $row['modal_akhir'];
    //     }
    //     $data['chartType'] = 'line';
    //     $data['datasets'][] = [
    //         'label' => 'Modal Awal',
    //         'data' => $modal_awal,
    //         'borderColor' => '#1cc88a',
    //         'fill' => false
    //     ];
    //     $data['datasets'][] = [
    //         'label' => 'Modal Akhir',
    //         'data' => $modal_akhir,
    //         'borderColor' => '#36b9cc',
    //         'fill' => false
    //     ];
    //     break;

    // // 📈 Grafik Laba Rugi per Periode
    // case 'laba':
    //     $q = "SELECT periode, laba_rugi FROM laba_rugi ORDER BY periode ASC";
    //     $r = $conn->query($q);
    //     while ($row = $r->fetch_assoc()) {
    //         $data['labels'][] = $row['periode'];
    //         $values[] = $row['laba_rugi'];
    //     }
    //     $data['chartType'] = 'bar';
    //     $data['datasets'][] = [
    //         'label' => 'Laba Rugi',
    //         'data' => $values,
    //         'backgroundColor' => '#e74a3b'
    //     ];
    //     break;
}

echo json_encode($data);
