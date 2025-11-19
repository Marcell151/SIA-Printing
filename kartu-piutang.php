<?php
/**
 * File: kartu-piutang.php (1 User = 1 Kartu Piutang)
 * Menampilkan semua transaksi piutang pelanggan dalam 1 kartu
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
$pelanggan_info = null;
$total_piutang_keseluruhan = 0;
$total_dibayar_keseluruhan = 0;
$total_sisa_keseluruhan = 0;

if ($id_pelanggan > 0) {
    // Get data pelanggan
    $pelanggan_info = $conn->query("SELECT mp.*, 
                                   (SELECT syarat_kredit FROM piutang WHERE id_pelanggan = mp.id_pelanggan ORDER BY tanggal DESC LIMIT 1) as syarat_kredit
                                   FROM master_pelanggan mp 
                                   WHERE mp.id_pelanggan = $id_pelanggan")->fetch_assoc();
    
    if ($pelanggan_info) {
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
        
        $saldo_berjalan = 0;
        
        while ($piutang = $piutang_list->fetch_assoc()) {
            $id_piutang = $piutang['id_piutang'];
            
            // Hitung total untuk summary
            $total_piutang_keseluruhan += $piutang['total'];
            $total_dibayar_keseluruhan += $piutang['dibayar'];
            $total_sisa_keseluruhan += $piutang['sisa'];
            
            // Baris: Transaksi Piutang (DEBET) - Piutang bertambah
            $saldo_berjalan += $piutang['total'];
            
            $kartu_piutang_data[] = [
                'tanggal' => $piutang['tanggal'],
                'keterangan' => 'Piutang - ' . $piutang['jenis_jasa'],
                'folio' => $piutang['no_piutang'],
                'mutasi_debet' => $piutang['total'],
                'mutasi_kredit' => 0,
                'saldo_debet' => $saldo_berjalan,
                'saldo_kredit' => 0,
                'row_type' => 'piutang'
            ];
            
            // Get pembayaran (DP + cicilan) dari tabel pembayaran_piutang
            $pembayaran = $conn->query("SELECT * FROM pembayaran_piutang 
                                        WHERE id_piutang = $id_piutang 
                                        ORDER BY tanggal ASC, is_dp DESC");
            
            while ($bayar = $pembayaran->fetch_assoc()) {
                $mutasi_kredit = $bayar['jumlah_bayar'];
                $saldo_berjalan -= $mutasi_kredit;
                
                // Label pembayaran
                if ($bayar['is_dp'] == 1) {
                    $label_bayar = 'Pembayaran - DP / Pembayaran Awal';
                } else {
                    $label_bayar = 'Pembayaran - Cicilan ke-' . $bayar['cicilan_ke'];
                }
                
                if ($bayar['metode_pembayaran']) {
                    $label_bayar .= ' (' . $bayar['metode_pembayaran'] . ')';
                }
                
                $kartu_piutang_data[] = [
                    'tanggal' => $bayar['tanggal'],
                    'keterangan' => $label_bayar,
                    'folio' => $piutang['no_piutang'],
                    'mutasi_debet' => 0,
                    'mutasi_kredit' => $mutasi_kredit,
                    'saldo_debet' => $saldo_berjalan > 0 ? $saldo_berjalan : 0,
                    'saldo_kredit' => $saldo_berjalan < 0 ? abs($saldo_berjalan) : 0,
                    'row_type' => $bayar['is_dp'] == 1 ? 'dp' : 'pembayaran'
                ];
            }
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
                <select name="id_pelanggan" class="form-select" required onchange="this.form.submit()">
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
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($id_pelanggan > 0 && $pelanggan_info): ?>

<!-- KARTU PIUTANG - 1 USER 1 KARTU -->
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
                        <td>: <?php echo str_pad($pelanggan_info['id_pelanggan'], 3, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Nama</td>
                        <td>: <strong><?php echo $pelanggan_info['nama_pelanggan']; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Alamat</td>
                        <td>: <?php echo $pelanggan_info['alamat'] ?: '-'; ?></td>
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
                        <td>: <strong><?php echo $pelanggan_info['syarat_kredit'] ?? 'Net 30'; ?></strong></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">Batas Kredit</td>
                        <td>: <?php echo format_rupiah($total_sisa_keseluruhan); ?></td>
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
                        <th width="10%" rowspan="2" class="align-middle">TGL</th>
                        <th width="35%" rowspan="2" class="align-middle">KETERANGAN</th>
                        <th width="10%" rowspan="2" class="align-middle">FOL</th>
                        <th colspan="2" class="border-bottom">MUTASI</th>
                        <th colspan="2" class="border-bottom">SALDO</th>
                    </tr>
                    <tr class="text-center">
                        <th width="11%">DEBET</th>
                        <th width="11%">KREDIT</th>
                        <th width="11%">DEBET</th>
                        <th width="12%">KREDIT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kartu_piutang_data) > 0): ?>
                        <?php 
                        foreach ($kartu_piutang_data as $row): 
                            $row_class = '';
                            if ($row['row_type'] == 'piutang') {
                                $row_class = 'table-warning';
                            } elseif ($row['row_type'] == 'dp') {
                                $row_class = 'table-info';
                            } elseif ($row['row_type'] == 'pembayaran') {
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
                        <?php endforeach; ?>
                        
                        <!-- Saldo Akhir -->
                        <tr class="table-primary fw-bold">
                            <td colspan="5" class="text-end">SALDO AKHIR:</td>
                            <td class="text-end">
                                <?php 
                                $last_row = end($kartu_piutang_data);
                                $saldo_akhir = $last_row['saldo_debet'] > 0 ? $last_row['saldo_debet'] : 0;
                                echo format_rupiah($saldo_akhir); 
                                ?>
                            </td>
                            <td class="text-end">--</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada transaksi piutang untuk pelanggan ini
                                <?php if ($periode_dari || $periode_sampai || $status_filter): ?>
                                    <br><small>dengan filter yang dipilih</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Summary -->
    <div class="card-footer bg-light">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted d-block">Total Piutang</small>
                <strong><?php echo format_rupiah($total_piutang_keseluruhan); ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Sudah Dibayar</small>
                <strong class="text-success"><?php echo format_rupiah($total_dibayar_keseluruhan); ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Sisa Piutang</small>
                <strong class="text-danger"><?php echo format_rupiah($total_sisa_keseluruhan); ?></strong>
            </div>
            <div class="col-md-3 text-end">
                <button onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak Kartu
                </button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>