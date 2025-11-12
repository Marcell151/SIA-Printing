<?php
require_once 'config.php';
check_role(['owner', 'akuntan']);

$page_title = 'Laporan Laba Rugi';
$current_page = 'laba-rugi';

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');

// Hitung tanggal awal dan akhir periode yang dipilih
$tanggal_awal = date('Y-m-01', strtotime($periode . '-01'));
$tanggal_akhir = date('Y-m-t', strtotime($periode . '-01'));

// Get pendapatan (akun 4-xxx) - HANYA untuk periode yang dipilih (PERIODIK)
$query_pendapatan = "SELECT ju.id_akun_kredit as id_akun, ma.kode_akun, ma.nama_akun,
                     SUM(ju.nominal) as total
                     FROM jurnal_umum ju
                     JOIN master_akun ma ON ju.id_akun_kredit = ma.id_akun
                     WHERE ma.tipe_akun = '4-Pendapatan'
                     AND ju.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                     GROUP BY ju.id_akun_kredit, ma.kode_akun, ma.nama_akun
                     ORDER BY ma.kode_akun";
$pendapatan = $conn->query($query_pendapatan);

// Get beban (akun 5-xxx) - HANYA untuk periode yang dipilih (PERIODIK)
$query_beban = "SELECT ju.id_akun_debit as id_akun, ma.kode_akun, ma.nama_akun,
                SUM(ju.nominal) as total
                FROM jurnal_umum ju
                JOIN master_akun ma ON ju.id_akun_debit = ma.id_akun
                WHERE ma.tipe_akun = '5-Beban'
                AND ju.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                GROUP BY ju.id_akun_debit, ma.kode_akun, ma.nama_akun
                ORDER BY ma.kode_akun";
$beban = $conn->query($query_beban);

$total_pendapatan = 0;
$total_beban = 0;

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-graph-up me-2"></i>Laporan Laba Rugi</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Laba Rugi</li>
        </ol>
    </nav>
</div>

<!-- Info Alert -->
<div class="alert alert-info no-print">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Catatan:</strong> Laporan Laba Rugi bersifat <strong>PERIODIK</strong> (bukan kumulatif).
    <ul class="mb-0 mt-2">
        <li>Hanya menampilkan <strong>pendapatan dan beban yang terjadi pada bulan/periode yang dipilih</strong></li>
        <li>Jika pilih <strong>Oktober 2025</strong>, maka hanya transaksi Oktober yang dihitung</li>
        <li>Jika pilih <strong>November 2025</strong>, maka hanya transaksi November yang dihitung</li>
        <li>Data diambil langsung dari <strong>Jurnal Umum</strong> (termasuk jurnal penyesuaian jika ada)</li>
    </ul>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Periode</label>
                <input type="month" name="periode" class="form-control" value="<?php echo $periode; ?>">
            </div>
            <div class="col-md-8 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search me-1"></i>Tampilkan
                </button>
                <button type="button" onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Laporan Laba Rugi -->
<div class="card">
    <div class="card-header bg-primary text-white text-center">
        <h4 class="mb-1">CV. JASA PRINTING</h4>
        <h5 class="mb-0">LAPORAN LABA RUGI</h5>
        <p class="mb-0">
            Untuk Bulan yang Berakhir
            <?php 
            $nama_bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            $pecah = explode('-', $periode);
            echo '31 ' . $nama_bulan[$pecah[1]] . ' ' . $pecah[0];
            ?>
            <br>
            <small class="opacity-75">(Periodik - Hanya Bulan Ini)</small>
        </p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-borderless">
                <!-- PENDAPATAN -->
                <tbody>
                    <tr class="table-secondary">
                        <td colspan="2" class="fw-bold">
                            <h5 class="mb-0"><i class="bi bi-arrow-down-circle me-2"></i>PENDAPATAN</h5>
                        </td>
                    </tr>
                    
                    <?php if ($pendapatan->num_rows > 0): ?>
                        <?php while ($row = $pendapatan->fetch_assoc()): 
                            $nilai = $row['total'];
                            $total_pendapatan += $nilai;
                        ?>
                            <tr>
                                <td class="ps-4" width="70%"><?php echo $row['nama_akun']; ?></td>
                                <td class="text-end" width="30%"><?php echo format_rupiah($nilai); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="border-top border-dark">
                            <td class="ps-4 fw-bold">Total Pendapatan</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_pendapatan); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">
                                Tidak ada data pendapatan
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr><td colspan="2" class="py-2"></td></tr>
                    
                    <!-- BEBAN -->
                    <tr class="table-secondary">
                        <td colspan="2" class="fw-bold">
                            <h5 class="mb-0"><i class="bi bi-arrow-up-circle me-2"></i>BEBAN</h5>
                        </td>
                    </tr>
                    
                    <?php if ($beban->num_rows > 0): ?>
                        <?php while ($row = $beban->fetch_assoc()): 
                            $nilai = $row['total'];
                            $total_beban += $nilai;
                        ?>
                            <tr>
                                <td class="ps-4"><?php echo $row['nama_akun']; ?></td>
                                <td class="text-end"><?php echo format_rupiah($nilai); ?></td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="border-top border-dark">
                            <td class="ps-4 fw-bold">Total Beban</td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($total_beban); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">
                                Tidak ada data beban
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr><td colspan="2" class="py-2"></td></tr>
                    
                    <!-- LABA/RUGI BERSIH -->
                    <?php 
                    $laba_rugi = $total_pendapatan - $total_beban;
                    $label = ($laba_rugi >= 0) ? 'LABA BERSIH' : 'RUGI BERSIH';
                    $class = ($laba_rugi >= 0) ? 'text-success' : 'text-danger';
                    ?>
                    <tr class="border-top border-dark border-2">
                        <td class="fw-bold fs-5 <?php echo $class; ?>"><?php echo $label; ?></td>
                        <td class="text-end fw-bold fs-5 <?php echo $class; ?>">
                            <?php echo format_rupiah(abs($laba_rugi)); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Box -->
        <div class="row mt-4 no-print">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <p class="text-muted small mb-1">Total Pendapatan</p>
                        <h4 class="mb-0 text-primary"><?php echo format_rupiah($total_pendapatan); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <p class="text-muted small mb-1">Total Beban</p>
                        <h4 class="mb-0 text-danger"><?php echo format_rupiah($total_beban); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card <?php echo ($laba_rugi >= 0) ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body text-center">
                        <p class="small mb-1 opacity-75"><?php echo $label; ?></p>
                        <h4 class="mb-0"><?php echo format_rupiah(abs($laba_rugi)); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="row mt-4 no-print">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Tentang Laporan Laba Rugi</h6>
                <p class="small mb-2">
                    Laporan Laba Rugi menunjukkan kinerja keuangan perusahaan selama periode tertentu 
                    dengan membandingkan total pendapatan dengan total beban yang dikeluarkan.
                </p>
                <div class="row small">
                    <div class="col-md-6">
                        <strong>Rumus:</strong>
                        <ul class="mb-0">
                            <li>Laba Bersih = Total Pendapatan - Total Beban</li>
                            <li>Jika hasilnya positif (+) = LABA</li>
                            <li>Jika hasilnya negatif (-) = RUGI</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Fungsi:</strong>
                        <ul class="mb-0">
                            <li>Mengevaluasi profitabilitas usaha</li>
                            <li>Dasar pengambilan keputusan manajemen</li>
                            <li>Input untuk laporan perubahan modal</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>