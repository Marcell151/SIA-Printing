<?php
/**
 * File: grafik_dashboard_advanced.php
 * API untuk grafik dashboard yang lebih lengkap
 */
require_once 'config.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'pendapatan';
$data = ['labels' => [], 'datasets' => [], 'chartType' => 'bar'];

switch ($type) {
    // 📊 Grafik Pendapatan per Bulan (6 bulan terakhir)
    case 'pendapatan':
        $q = "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, 
              SUM(jumlah) AS total 
              FROM transaksi_pendapatan 
              WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
              ORDER BY bulan ASC";
        $r = $conn->query($q);
        $values = [];
        while ($row = $r->fetch_assoc()) {
            $data['labels'][] = $row['bulan'];
            $values[] = (float)$row['total'];
        }
        $data['datasets'][] = [
            'label' => 'Pendapatan Tunai',
            'data' => $values,
            'backgroundColor' => '#4e73df',
            'borderColor' => '#2e59d9',
            'borderWidth' => 2
        ];
        
        // Tambahkan pendapatan kredit
        $q2 = "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, 
               SUM(total) AS total 
               FROM piutang 
               WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
               GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
               ORDER BY bulan ASC";
        $r2 = $conn->query($q2);
        $values2 = [];
        while ($row = $r2->fetch_assoc()) {
            $values2[] = (float)$row['total'];
        }
        $data['datasets'][] = [
            'label' => 'Pendapatan Kredit',
            'data' => $values2,
            'backgroundColor' => '#1cc88a',
            'borderColor' => '#17a673',
            'borderWidth' => 2
        ];
        break;

    // 💳 Grafik Piutang Status
    case 'piutang':
        $q = "SELECT status, COUNT(*) as jumlah, SUM(sisa) AS total 
              FROM piutang 
              GROUP BY status";
        $r = $conn->query($q);
        $labels = [];
        $values = [];
        $colors = [];
        while ($row = $r->fetch_assoc()) {
            $labels[] = $row['status'] . ' (' . $row['jumlah'] . ' transaksi)';
            $values[] = (float)$row['total'];
            $colors[] = ($row['status'] == 'Lunas') ? '#1cc88a' : '#f6c23e';
        }
        $data['labels'] = $labels;
        $data['chartType'] = 'doughnut';
        $data['datasets'][] = [
            'label' => 'Total Piutang',
            'data' => $values,
            'backgroundColor' => $colors
        ];
        break;

    // 📈 Grafik Perbandingan Pendapatan vs Beban
    case 'pendapatan_beban':
        // Get 6 bulan terakhir
        for ($i = 5; $i >= 0; $i--) {
            $bulan = date('Y-m', strtotime("-$i month"));
            $data['labels'][] = $bulan;
            
            // Hitung pendapatan
            $tanggal_awal = date('Y-m-01', strtotime($bulan . '-01'));
            $tanggal_akhir = date('Y-m-t', strtotime($bulan . '-01'));
            
            $q_pendapatan = "SELECT COALESCE(SUM(ju.nominal), 0) as total
                            FROM jurnal_umum ju
                            JOIN master_akun ma ON ju.id_akun_kredit = ma.id_akun
                            WHERE ma.tipe_akun = '4-Pendapatan'
                            AND ju.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            $pendapatan = $conn->query($q_pendapatan)->fetch_assoc()['total'];
            $pendapatan_values[] = (float)$pendapatan;
            
            // Hitung beban
            $q_beban = "SELECT COALESCE(SUM(ju.nominal), 0) as total
                       FROM jurnal_umum ju
                       JOIN master_akun ma ON ju.id_akun_debit = ma.id_akun
                       WHERE ma.tipe_akun = '5-Beban'
                       AND ju.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
            $beban = $conn->query($q_beban)->fetch_assoc()['total'];
            $beban_values[] = (float)$beban;
            
            // Hitung laba
            $laba_values[] = (float)($pendapatan - $beban);
        }
        
        $data['chartType'] = 'line';
        $data['datasets'][] = [
            'label' => 'Pendapatan',
            'data' => $pendapatan_values,
            'borderColor' => '#4e73df',
            'backgroundColor' => 'rgba(78, 115, 223, 0.1)',
            'fill' => true,
            'tension' => 0.4
        ];
        $data['datasets'][] = [
            'label' => 'Beban',
            'data' => $beban_values,
            'borderColor' => '#e74a3b',
            'backgroundColor' => 'rgba(231, 74, 59, 0.1)',
            'fill' => true,
            'tension' => 0.4
        ];
        $data['datasets'][] = [
            'label' => 'Laba',
            'data' => $laba_values,
            'borderColor' => '#1cc88a',
            'backgroundColor' => 'rgba(28, 200, 138, 0.1)',
            'fill' => true,
            'tension' => 0.4
        ];
        break;

    // 🎯 Grafik Top 5 Pelanggan
    case 'top_pelanggan':
        $q = "SELECT mp.nama_pelanggan, 
              COALESCE(SUM(tp.jumlah), 0) + COALESCE(SUM(p.total), 0) as total_transaksi
              FROM master_pelanggan mp
              LEFT JOIN transaksi_pendapatan tp ON mp.id_pelanggan = tp.id_pelanggan
              LEFT JOIN piutang p ON mp.id_pelanggan = p.id_pelanggan
              WHERE mp.nama_pelanggan != 'Umum'
              GROUP BY mp.id_pelanggan, mp.nama_pelanggan
              ORDER BY total_transaksi DESC
              LIMIT 5";
        $r = $conn->query($q);
        $values = [];
        while ($row = $r->fetch_assoc()) {
            $data['labels'][] = $row['nama_pelanggan'];
            $values[] = (float)$row['total_transaksi'];
        }
        $data['chartType'] = 'bar';
        $data['datasets'][] = [
            'label' => 'Total Transaksi',
            'data' => $values,
            'backgroundColor' => [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
            ]
        ];
        break;
}

echo json_encode($data);
?>