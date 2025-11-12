<?php
require_once 'config.php';
check_role(['owner', 'akuntan']);

$page_title = 'Laporan Perubahan Modal';
$current_page = 'perubahan-modal';

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : date('Y-m');

// Get Modal Awal (akun 3-101)
$modal_awal_data = $conn->query("SELECT nsp.*, ma.nama_akun
                                 FROM neraca_saldo_penyesuaian nsp
                                 JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                                 WHERE nsp.periode = '$periode'
                                 AND ma.kode_akun = '3-101'");

$modal_awal = 0;
if ($modal_awal_data->num_rows > 0) {
    $row = $modal_awal_data->fetch_assoc();
    $modal_awal = $row['saldo_kredit'] > 0 ? $row['saldo_kredit'] : $row['saldo_debit'];
}

// Get Prive (akun 3-102)
$prive_data = $conn->query("SELECT nsp.*, ma.nama_akun
                            FROM neraca_saldo_penyesuaian nsp
                            JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                            WHERE nsp.periode = '$periode'
                            AND ma.kode_akun = '3-102'");

$prive = 0;
if ($prive_data->num_rows > 0) {
    $row = $prive_data->fetch_assoc();
    $prive = $row['saldo_debit'] > 0 ? $row['saldo_debit'] : $row['saldo_kredit'];
}

// Calculate Laba/Rugi
$total_pendapatan = $conn->query("SELECT COALESCE(SUM(saldo_kredit), 0) as total
                                  FROM neraca_saldo_penyesuaian nsp
                                  JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                                  WHERE nsp.periode = '$periode'
                                  AND ma.tipe_akun = '4-Pendapatan'")->fetch_assoc()['total'];

$total_beban = $conn->query("SELECT COALESCE(SUM(saldo_debit), 0) as total
                             FROM neraca_saldo_penyesuaian nsp
                             JOIN master_akun ma ON nsp.id_akun = ma.id_akun
                             WHERE nsp.periode = '$periode'
                             AND ma.tipe_akun = '5-Beban'")->fetch_assoc()['total'];

$laba_rugi = $total_pendapatan - $total_beban;

// Calculate Modal Akhir
$modal_akhir = $modal_awal + $laba_rugi - $prive;

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-currency-dollar me-2"></i>Laporan Perubahan Modal</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Perubahan Modal</li>
        </ol>
    </nav>
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

<!-- Laporan Perubahan Modal -->
<div class="card">
    <div class="card-header bg-info text-white text-center">
        <h4 class="mb-1">CV. JASA PRINTING</h4>
        <h5 class="mb-0">LAPORAN PERUBAHAN MODAL</h5>
        <p class="mb-0">
            Untuk Periode yang Berakhir 
            <?php 
            $nama_bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            $pecah = explode('-', $periode);
            echo '31 ' . $nama_bulan[$pecah[1]] . ' ' . $pecah[0];
            ?>
        </p>
    </div>
    <div class="card-body">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <table class="table table-borderless table-lg">
                    <tbody>
                        <!-- Modal Awal -->
                        <tr>
                            <td width="50%" class="fw-bold">Modal Awal Periode</td>
                            <td width="50%" class="text-end fw-bold">
                                <?php echo format_rupiah($modal_awal); ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2" class="py-2"></td></tr>
                        
                        <!-- Laba/Rugi -->
                        <tr>
                            <td class="ps-4">
                                <?php echo ($laba_rugi >= 0) ? 'Laba Bersih' : 'Rugi Bersih'; ?>
                            </td>
                            <td class="text-end <?php echo ($laba_rugi >= 0) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo format_rupiah(abs($laba_rugi)); ?>
                            </td>
                        </tr>
                        
                        <!-- Prive -->
                        <?php if ($prive > 0): ?>
                        <tr>
                            <td class="ps-4">Prive</td>
                            <td class="text-end text-danger">
                                (<?php echo format_rupiah($prive); ?>)
                            </td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr class="border-top">
                            <td class="ps-4 fw-semibold">Penambahan Modal</td>
                            <td class="text-end fw-semibold">
                                <?php echo format_rupiah($laba_rugi - $prive); ?>
                            </td>
                        </tr>
                        
                        <tr><td colspan="2" class="py-2"></td></tr>
                        
                        <!-- Modal Akhir -->
                        <tr class="border-top border-dark border-2 table-primary">
                            <td class="fw-bold fs-5">Modal Akhir Periode</td>
                            <td class="text-end fw-bold fs-5">
                                <?php echo format_rupiah($modal_akhir); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Visual Summary -->
        <div class="row mt-4 no-print">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <p class="text-muted small mb-1">Modal Awal</p>
                        <h5 class="mb-0"><?php echo format_rupiah($modal_awal); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card <?php echo ($laba_rugi >= 0) ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <div class="card-body text-center">
                        <p class="small mb-1 opacity-75">
                            <?php echo ($laba_rugi >= 0) ? 'Laba Bersih' : 'Rugi Bersih'; ?>
                        </p>
                        <h5 class="mb-0"><?php echo format_rupiah(abs($laba_rugi)); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning">
                    <div class="card-body text-center">
                        <p class="text-dark small mb-1">Prive</p>
                        <h5 class="mb-0 text-dark"><?php echo format_rupiah($prive); ?></h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <p class="small mb-1 opacity-75">Modal Akhir</p>
                        <h5 class="mb-0"><?php echo format_rupiah($modal_akhir); ?></h5>
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
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Tentang Laporan Perubahan Modal</h6>
                <p class="small mb-2">
                    Laporan Perubahan Modal menunjukkan perubahan posisi modal pemilik selama satu periode 
                    akuntansi sebagai akibat dari operasi perusahaan dan transaksi dengan pemilik.
                </p>
                <div class="row small">
                    <div class="col-md-6">
                        <strong>Rumus:</strong>
                        <ul class="mb-0">
                            <li>Modal Akhir = Modal Awal + Laba Bersih - Prive</li>
                            <li>Atau: Modal Akhir = Modal Awal - Rugi Bersih - Prive</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Komponen:</strong>
                        <ul class="mb-0">
                            <li><strong>Modal Awal:</strong> Modal pada awal periode</li>
                            <li><strong>Laba/Rugi:</strong> Hasil dari Laporan Laba Rugi</li>
                            <li><strong>Prive:</strong> Pengambilan modal oleh pemilik</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>