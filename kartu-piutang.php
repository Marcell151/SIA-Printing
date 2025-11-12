<?php
/**
 * File: kartu-piutang.php (IMPROVED VERSION)
 * Kartu Piutang - Format Standar Akuntansi
 * Menampilkan detail mutasi piutang per pelanggan dengan format yang benar
 */
require_once 'config.php';
check_role(['kasir', 'akuntan', 'owner']);

$page_title = 'Kartu Piutang';
$current_page = 'kartu-piutang';

// Filter
$id_pelanggan = isset($_GET['id_pelanggan']) ? intval($_GET['id_pelanggan']) : 0;
$periode_dari = isset($_GET['periode_dari']) ? $_GET['periode_dari'] : '';
$periode_sampai = isset($_GET['periode_sampai']) ? $_GET['periode_sampai'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get list pelanggan yang punya piutang
$pelanggan_list = $conn->query("SELECT DISTINCT mp.id_pelanggan, mp.nama_pelanggan, mp.telepon, mp.alamat
                                 FROM master_pelanggan mp
                                 JOIN piutang p ON mp.id_pelanggan = p.id_pelanggan
                                 ORDER BY mp.nama_pelanggan");

$kartu_piutang_data = [];

if ($id_pelanggan > 0) {
    // Get data pelanggan
    $pelanggan = $conn->query("SELECT * FROM master_pelanggan WHERE id_pelanggan = $id_pelanggan")->fetch_assoc();
    
    // Get semua piutang pelanggan ini
    $query_piutang = "SELECT * FROM piutang WHERE id_pelanggan = $id_pelanggan";
    
    if ($periode_dari && $periode_sampai) {
        $query_piutang .= " AND tanggal BETWEEN '$periode_dari' AND '$periode_sampai'";
    }
    
    if ($status_filter) {
        $query_piutang .= " AND status = '$status_filter'";
    }
    
    $query_piutang .= " ORDER BY tanggal ASC, id_piutang ASC";
    
    $piutang_list = $conn->query($query_piutang);
    
    $saldo_berjalan_debet = 0;
    $saldo_berjalan_kredit = 0;
    
    while ($piutang = $piutang_list->fetch_assoc()) {
        $id_piutang = $piutang['id_piutang'];
        
        // Baris 1: Saldo Awal (jika ada)
        if ($saldo_berjalan_debet == 0 && $saldo_berjalan_kredit == 0) {
            // Saldo awal = 0
        }
        
        // Baris 2: Transaksi Piutang (DEBET) - Piutang bertambah
        $mutasi_debet = $piutang['total'];
        $mutasi_kredit = 0;
        $saldo_berjalan_debet += $mutasi_debet;
        
        $kartu_piutang_data[] = [
            'tanggal' => $piutang['tanggal'],
            'keterangan' => 'Piutang - ' . $piutang['jenis_jasa'],
            'folio' => $piutang['no_piutang'],
            'mutasi_debet' => $mutasi_debet,
            'mutasi_kredit' => 0,
            'saldo_debet' => $saldo_berjalan_debet,
            'saldo_kredit' => 0,
            'row_type' => 'piutang'
        ];
        
        // Baris 3: DP / Pembayaran Pertama (jika ada)
        if ($piutang['dibayar'] > 0) {
            // Cek apakah ada pembayaran awal (dari data piutang langsung)
            $dp_amount = $conn->query("SELECT MIN(jumlah_bayar) as dp 
                                       FROM pembayaran_piutang 
                                       WHERE id_piutang = $id_piutang 
                                       LIMIT 1")->fetch_assoc();
            
            if (!$dp_amount) {
                // Tidak ada record pembayaran, berarti DP langsung dari field dibayar
                $dp = $piutang['dibayar'];
                
                $mutasi_kredit = $dp;
                $saldo_berjalan_debet -= $mutasi_kredit;
                
                $kartu_piutang_data[] = [
                    'tanggal' => $piutang['tanggal'],
                    'keterangan' => 'DP / Pembayaran Awal',
                    'folio' => $piutang['no_piutang'],
                    'mutasi_debet' => 0,
                    'mutasi_kredit' => $mutasi_kredit,
                    'saldo_debet' => $saldo_berjalan_debet,
                    'saldo_kredit' => 0,
                    'row_type' => 'dp'
                ];
            }
        }
        
        // Get pembayaran cicilan (dari tabel pembayaran_piutang)
        $pembayaran = $conn->query("SELECT * FROM pembayaran_piutang 
                                    WHERE id_piutang = $id_piutang 
                                    ORDER BY tanggal ASC");
        
        while ($bayar = $pembayaran->fetch_assoc()) {
            $mutasi_kredit = $bayar['jumlah_bayar'];
            $saldo_berjalan_debet -= $mutasi_kredit;
            
            $kartu_piutang_data[] = [
                'tanggal' => $bayar['tanggal'],
                'keterangan' => 'Pembayaran - ' . $bayar['metode_pembayaran'] . 
                               ($bayar['keterangan'] ? ' (' . $bayar['keterangan'] . ')' : ''),
                'folio' => $piutang['no_piutang'],
                'mutasi_debet' => 0,
                'mutasi_kredit' => $mutasi_kredit,
                'saldo_debet' => $saldo_berjalan_debet,
                'saldo_kredit' => 0,
                'row_type' => 'pembayaran'
            ];
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-card-text me-2"></i>Kartu Piutang</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kartu Piutang</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Kartu Piutang</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                <select name="id_pelanggan" class="form-select" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php 
                    $pelanggan_list->data_seek(0);
                    while ($pel = $pelanggan_list->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $pel['id_pelanggan']; ?>" 
                                <?php echo ($id_pelanggan == $pel['id_pelanggan']) ? 'selected' : ''; ?>>
                            <?php echo $pel['nama_pelanggan']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="periode_dari" class="form-control" value="<?php echo $periode_dari; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="periode_sampai" class="form-control" value="<?php echo $periode_sampai; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua</option>
                    <option value="Belum Lunas" <?php echo ($status_filter == 'Belum Lunas') ? 'selected' : ''; ?>>Belum Lunas</option>
                    <option value="Lunas" <?php echo ($status_filter == 'Lunas') ? 'selected' : ''; ?>>Lunas</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($id_pelanggan > 0 && isset($pelanggan)): ?>

<!-- KARTU PIUTANG - FORMAT STANDAR -->
<div class="card">
    <div class="card-header bg-white border-bottom-0">
        <div class="text-center mb-3">
            <h4 class="mb-1 fw-bold">CV. JASA PRINTING</h4>
            <h5 class="mb-3">KARTU PIUTANG</h5>
        </div>
        
        <!-- Info Pelanggan -->
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120" class="fw-semibold">No Rekening</td>
                        <td>: <?php echo str_pad($pelanggan['id_pelanggan'], 3, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Nama</td>
                        <td>: <strong><?php echo $pelanggan['nama_pelanggan']; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Alamat</td>
                        <td>: <?php echo $pelanggan['alamat'] ?: '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="120" class="fw-semibold">Lembar ke</td>
                        <td>: 1</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Syarat</td>
                        <td>: Wajib Lunas</td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Batas Kredit</td>
                        <td>: <?php 
                        // Hitung total piutang pelanggan ini
                        $total_piutang = $conn->query("SELECT COALESCE(SUM(sisa), 0) as total FROM piutang WHERE id_pelanggan = $id_pelanggan AND status = 'Belum Lunas'")->fetch_assoc()['total'];
                        echo format_rupiah($total_piutang); 
                        ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                        <th width="10%" rowspan="2" class="align-middle">Tgl</th>
                        <th width="30%" rowspan="2" class="align-middle">Keterangan</th>
                        <th width="10%" rowspan="2" class="align-middle">Fol</th>
                        <th colspan="2" class="border-bottom">Mutasi</th>
                        <th colspan="2" class="border-bottom">Saldo</th>
                    </tr>
                    <tr class="text-center">
                        <th width="12%">Debet</th>
                        <th width="12%">Kredit</th>
                        <th width="13%">Debet</th>
                        <th width="13%">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kartu_piutang_data) > 0): ?>
                        <?php 
                        $row_num = 1;
                        foreach ($kartu_piutang_data as $row): 
                            $row_class = '';
                            if ($row['row_type'] == 'piutang') {
                                $row_class = 'table-warning';
                            } elseif ($row['row_type'] == 'pembayaran' || $row['row_type'] == 'dp') {
                                $row_class = 'table-success';
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="text-center"><?php echo date('d/m', strtotime($row['tanggal'])); ?></td>
                                <td><?php echo $row['keterangan']; ?></td>
                                <td class="text-center"><small><?php echo $row['folio']; ?></small></td>
                                <td class="text-end">
                                    <?php echo ($row['mutasi_debet'] > 0) ? format_rupiah($row['mutasi_debet']) : '--'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($row['mutasi_kredit'] > 0) ? format_rupiah($row['mutasi_kredit']) : '--'; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?php echo ($row['saldo_debet'] > 0) ? format_rupiah($row['saldo_debet']) : '--'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo ($row['saldo_kredit'] > 0) ? format_rupiah($row['saldo_kredit']) : '--'; ?>
                                </td>
                            </tr>
                        <?php 
                        $row_num++;
                        endforeach; 
                        ?>
                        
                        <!-- Saldo Akhir -->
                        <tr class="table-primary fw-bold">
                            <td colspan="5" class="text-end">SALDO AKHIR:</td>
                            <td class="text-end">
                                <?php 
                                $saldo_akhir = end($kartu_piutang_data)['saldo_debet'];
                                echo format_rupiah($saldo_akhir); 
                                ?>
                            </td>
                            <td class="text-end">--</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada transaksi piutang
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white no-print">
        <button onclick="window.print()" class="btn btn-success">
            <i class="bi bi-printer me-1"></i>Cetak Kartu Piutang
        </button>
    </div>
</div>

<?php else: ?>

<div class="alert alert-info text-center py-5">
    <i class="bi bi-info-circle display-4 d-block mb-3"></i>
    <h5>Pilih Pelanggan</h5>
    <p class="mb-0">Gunakan filter di atas untuk menampilkan kartu piutang pelanggan</p>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>