<?php
require_once 'config.php';
check_login();

$page_title = 'Dashboard';
$current_page = 'dashboard';

// Get statistics
$bulan_ini = date('Y-m');

// Total Pendapatan Bulan Ini
$query_pendapatan = "SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi_pendapatan 
                     WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$total_pendapatan_tunai = $conn->query($query_pendapatan)->fetch_assoc()['total'];

$query_piutang_pendapatan = "SELECT COALESCE(SUM(total), 0) as total FROM piutang 
                             WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$total_pendapatan_kredit = $conn->query($query_piutang_pendapatan)->fetch_assoc()['total'];

$query_pendapatan_lain = "SELECT COALESCE(SUM(jumlah), 0) as total FROM pendapatan_lainnya 
                          WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$total_pendapatan_lain = $conn->query($query_pendapatan_lain)->fetch_assoc()['total'];

$total_pendapatan = $total_pendapatan_tunai + $total_pendapatan_kredit + $total_pendapatan_lain;

// Total Piutang (Belum Lunas)
$query_piutang = "SELECT COALESCE(SUM(sisa), 0) as total FROM piutang WHERE status = 'Belum Lunas'";
$total_piutang = $conn->query($query_piutang)->fetch_assoc()['total'];

// Penerimaan Kas Bulan Ini
$query_kas = "SELECT COALESCE(SUM(jumlah), 0) as total FROM transaksi_pendapatan 
              WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$penerimaan_kas_tunai = $conn->query($query_kas)->fetch_assoc()['total'];

$query_kas_piutang = "SELECT COALESCE(SUM(jumlah_bayar), 0) as total FROM pembayaran_piutang 
                      WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
$penerimaan_kas_piutang = $conn->query($query_kas_piutang)->fetch_assoc()['total'];

$penerimaan_kas = $penerimaan_kas_tunai + $penerimaan_kas_piutang + $total_pendapatan_lain;

// Transaksi Hari Ini
$hari_ini = date('Y-m-d');
$query_transaksi = "SELECT 
                    (SELECT COUNT(*) FROM transaksi_pendapatan WHERE tanggal = '$hari_ini') +
                    (SELECT COUNT(*) FROM piutang WHERE tanggal = '$hari_ini') +
                    (SELECT COUNT(*) FROM pembayaran_piutang WHERE tanggal = '$hari_ini') +
                    (SELECT COUNT(*) FROM pendapatan_lainnya WHERE tanggal = '$hari_ini') as total";
$transaksi_hari_ini = $conn->query($query_transaksi)->fetch_assoc()['total'];

// Transaksi Terakhir (5 terakhir)
$query_terakhir = "
    SELECT 'Pendapatan Tunai' as jenis, tanggal, 
           (SELECT nama_pelanggan FROM master_pelanggan WHERE id_pelanggan = tp.id_pelanggan) as pelanggan,
           jumlah as nominal, created_at
    FROM transaksi_pendapatan tp
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 'Pendapatan Kredit' as jenis, tanggal,
           (SELECT nama_pelanggan FROM master_pelanggan WHERE id_pelanggan = p.id_pelanggan) as pelanggan,
           total as nominal, created_at
    FROM piutang p
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 'Penerimaan Piutang' as jenis, pp.tanggal,
           (SELECT nama_pelanggan FROM master_pelanggan mp 
            JOIN piutang p ON mp.id_pelanggan = p.id_pelanggan 
            WHERE p.id_piutang = pp.id_piutang) as pelanggan,
           jumlah_bayar as nominal, pp.created_at
    FROM pembayaran_piutang pp
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 'Pendapatan Lainnya' as jenis, tanggal,
           sumber_pendapatan as pelanggan,
           jumlah as nominal, created_at
    FROM pendapatan_lainnya
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    
    ORDER BY created_at DESC
    LIMIT 10
";
$result_terakhir = $conn->query($query_terakhir);

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Pendapatan Bulan Ini</p>
                        <h4 class="mb-0 fw-bold"><?php echo format_rupiah($total_pendapatan); ?></h4>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card stat-card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Total Piutang</p>
                        <h4 class="mb-0 fw-bold"><?php echo format_rupiah($total_piutang); ?></h4>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-credit-card"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card stat-card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Penerimaan Kas</p>
                        <h4 class="mb-0 fw-bold"><?php echo format_rupiah($penerimaan_kas); ?></h4>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card stat-card border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1 small">Transaksi Hari Ini</p>
                        <h4 class="mb-0 fw-bold"><?php echo $transaksi_hari_ini; ?></h4>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-graph-up"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Statistik -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Grafik Statistik Keuangan</h6>
      <select id="pilihanGrafik" class="form-select form-select-sm w-auto">
          <option value="pendapatan">Pendapatan per Bulan</option>
          <option value="piutang">Piutang (Lunas vs Belum Lunas)</option>
          <!-- <option value="modal">Perubahan Modal</option>
          <option value="laba">Laba Rugi per Periode</option> -->
      </select>
  </div>
  <div class="card-body p-3">
      <div style="position: relative; height:300px; width:100%;">
          <canvas id="chartDashboard"></canvas>
      </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartInstance = null;

function loadGrafik(type) {
    fetch('grafik_dashboard.php?type=' + type)
      .then(response => response.json())
      .then(data => {
          const ctx = document.getElementById('chartDashboard').getContext('2d');
          if (chartInstance) chartInstance.destroy(); // reset grafik lama

          chartInstance = new Chart(ctx, {
              type: data.chartType,
              data: {
                  labels: data.labels,
                  datasets: data.datasets
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                      legend: {
                          position: 'top',
                          labels: { boxWidth: 15, font: { size: 11 } }
                      }
                  },
                  scales: {
                      y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                      x: { ticks: { font: { size: 11 } } }
                  }
              }
          });
      });
}

document.getElementById('pilihanGrafik').addEventListener('change', function() {
    loadGrafik(this.value);
});

window.onload = () => loadGrafik('pendapatan');
</script>

<!-- Recent Transactions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Transaksi Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Transaksi</th>
                                <th>Pelanggan / Sumber</th>
                                <th class="text-end">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_terakhir->num_rows > 0): ?>
                                <?php while ($row = $result_terakhir->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    echo ($row['jenis'] == 'Pendapatan Tunai') ? 'bg-success' : 
                                                         (($row['jenis'] == 'Pendapatan Kredit') ? 'bg-warning' : 
                                                         (($row['jenis'] == 'Penerimaan Piutang') ? 'bg-info' : 'bg-secondary')); 
                                                ?>">
                                                <?php echo $row['jenis']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['pelanggan']; ?></td>
                                        <td class="text-end fw-bold"><?php echo format_rupiah($row['nominal']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        Belum ada transaksi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>