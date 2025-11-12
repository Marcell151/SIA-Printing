<?php
require_once 'config.php';
check_role(['kasir']);

$page_title = 'Pendapatan Kredit (Piutang)';
$current_page = 'pendapatan-kredit';

// Get master data
$pelanggan = $conn->query("SELECT * FROM master_pelanggan ORDER BY nama_pelanggan");

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = clean_input($_POST['tanggal']);
    $id_pelanggan = clean_input($_POST['id_pelanggan']);
    $jenis_jasa = clean_input($_POST['jenis_jasa']);
    $kategori = clean_input($_POST['kategori']);
    $total = floatval(str_replace('.', '', $_POST['total'])); // Hapus pemisah ribuan
    $dibayar = floatval(str_replace('.', '', $_POST['dibayar']));
    $jatuh_tempo = clean_input($_POST['jatuh_tempo']);
    $keterangan = clean_input($_POST['keterangan']);
    $created_by = $_SESSION['user_id'];
    // ===== VALIDASI TAMBAHAN =====
    
    // 1. Validasi Total tidak boleh 0 atau negatif
    if ($total <= 0) {
        alert('Total transaksi harus lebih dari 0!', 'danger');
        redirect('pendapatan-kredit.php');
        exit;
    }
    
    // 2. Validasi DP tidak boleh melebihi total
    if ($dibayar > $total) {
        alert('DP/Dibayar tidak boleh melebihi total transaksi!', 'danger');
        redirect('pendapatan-kredit.php');
        exit;
    }
    
    // 3. Validasi DP tidak boleh negatif
    if ($dibayar < 0) {
        alert('DP/Dibayar tidak boleh negatif!', 'danger');
        redirect('pendapatan-kredit.php');
        exit;
    }
    
    // 4. Validasi Jatuh Tempo harus lebih besar dari tanggal transaksi
    if (strtotime($jatuh_tempo) < strtotime($tanggal)) {
        alert('Jatuh tempo harus setelah tanggal transaksi!', 'danger');
        redirect('pendapatan-kredit.php');
        exit;
    }
    
    // 5. Validasi Jatuh Tempo maksimal 90 hari
    $selisih_hari = (strtotime($jatuh_tempo) - strtotime($tanggal)) / (60 * 60 * 24);
    if ($selisih_hari > 90) {
        alert('Peringatan: Jatuh tempo lebih dari 90 hari dari tanggal transaksi!', 'warning');
        // Tidak redirect, hanya peringatan
    }
    
    // Auto-suggest jatuh tempo jika kosong (default 30 hari)
    if (empty($jatuh_tempo)) {
        $jatuh_tempo = date('Y-m-d', strtotime($tanggal . ' +30 days'));
    }
    
    $sisa = $total - $dibayar;
    
    // Generate nomor piutang
    $no_piutang = generate_no_piutang();
    
    // Status
    $status = ($sisa <= 0) ? 'Lunas' : 'Belum Lunas';
    
    // Begin Transaction untuk memastikan konsistensi data
    $conn->begin_transaction();
    
    try {
        // Insert piutang
        $stmt = $conn->prepare("INSERT INTO piutang 
        (no_piutang, tanggal, id_pelanggan, jenis_jasa, kategori, total, dibayar, sisa, jatuh_tempo, status, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissdddssi", 
            $no_piutang, 
            $tanggal, 
            $id_pelanggan, 
            $jenis_jasa, 
            $kategori, 
            $total, 
            $dibayar, 
            $sisa, 
            $jatuh_tempo, 
            $status, 
            $created_by
        );
    
    if (!$stmt->execute()) {
            throw new Exception("Gagal insert piutang: " . $stmt->error);
        }
        
        $id_piutang = $stmt->insert_id;
        
        // Get akun ID
        $piutang_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-102'")->fetch_assoc()['id_akun'];
        
        // Determine pendapatan account
        $kode_pendapatan = '';
        switch($kategori) {
            case 'Printing': $kode_pendapatan = '4-101'; break;
            case 'Fotocopy': $kode_pendapatan = '4-102'; break;
            case 'Jilid': $kode_pendapatan = '4-103'; break;
            default: $kode_pendapatan = '4-101';
        }
        
        $pendapatan_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '$kode_pendapatan'")->fetch_assoc()['id_akun'];
        $kas_akun = $conn->query("SELECT id_akun FROM master_akun WHERE kode_akun = '1-101'")->fetch_assoc()['id_akun'];
        
        // Create jurnal entry: Debit Piutang, Kredit Pendapatan
        $deskripsi = "Pendapatan Kredit - $jenis_jasa - $no_piutang";
        insert_jurnal($tanggal, $deskripsi, $piutang_akun, $pendapatan_akun, $total, $no_piutang, "Pendapatan Kredit");
        
        // Jika ada DP, buat jurnal: Debit Kas, Kredit Piutang
        if ($dibayar > 0) {
            $deskripsi_dp = "DP Piutang - $no_piutang";
            $jurnal_dp_result = insert_jurnal($tanggal, $deskripsi_dp, $kas_akun, $piutang_akun, $dibayar, $no_piutang, "DP Piutang");
            
            if (!$jurnal_dp_result) {
                throw new Exception("Gagal insert jurnal DP piutang");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        alert("Transaksi pendapatan kredit berhasil disimpan! No. Piutang: $no_piutang", 'success');
        redirect('pendapatan-kredit.php');
        
    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        alert('Gagal menyimpan transaksi: ' . $e->getMessage(), 'danger');
        redirect('pendapatan-kredit.php');
    }
}

// Get recent piutang
$recent = $conn->query("SELECT p.*, mp.nama_pelanggan 
                        FROM piutang p
                        LEFT JOIN master_pelanggan mp ON p.id_pelanggan = mp.id_pelanggan
                        ORDER BY p.created_at DESC LIMIT 10");

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-credit-card me-2"></i>Pendapatan Kredit (Piutang)</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Pendapatan Kredit</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Form Input -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Input Transaksi Kredit</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="formPiutang">
                    <div class="mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                        <select name="id_pelanggan" class="form-select" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php while ($p = $pelanggan->fetch_assoc()): ?>
                                <option value="<?php echo $p['id_pelanggan']; ?>">
                                    <?php echo $p['nama_pelanggan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Jasa <span class="text-danger">*</span></label>
                        <input type="text" name="jenis_jasa" class="form-control" 
                               placeholder="Contoh: Cetak Banner 2x3 meter" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-select" required>
                            <option value="Printing">Printing</option>
                            <option value="Fotocopy">Fotocopy</option>
                            <option value="Jilid">Jilid</option>
                            <option value="Laminasi">Laminasi</option>
                            <option value="Desain">Desain</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total (Rp) <span class="text-danger">*</span></label>
                        <input type="text" name="total" id="total_piutang" class="form-control" 
                               placeholder="0" required min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">DP / Dibayar (Rp)</label>
                        <input type="text" name="dibayar" id="dibayar_piutang" class="form-control" 
                               placeholder="0" value="0" min="0" step="100">
                    </div>

                    <div class="mb-3">
                        <div class="label">
                            <strong>Sisa Piutang:</strong>
                            <span id="sisa_piutang" class="fw-bold">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" name="jatuh_tempo" id="jatuh_tempo" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="setJatuhTempo(7)" title="7 hari dari sekarang">
                                7 hari
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setJatuhTempo(14)" title="14 hari dari sekarang">
                                14 hari
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setJatuhTempo(30)" title="30 hari dari sekarang">
                                30 hari
                            </button>
                        </div>
                        <small class="text-muted">Atau pilih quick button: 7, 14, atau 30 hari dari tanggal transaksi</small>
                    </div>

                    <script>
                    function setJatuhTempo(hari) {
                        const tanggal = document.querySelector('input[name="tanggal"]').value;
                        if (!tanggal) {
                            alert('Pilih tanggal transaksi terlebih dahulu');
                            return;
                        }
                        
                        const date = new Date(tanggal);
                        date.setDate(date.getDate() + hari);
                        
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        
                        document.getElementById('jatuh_tempo').value = `${year}-${month}-${day}`;
                    }
                    
                    // Auto-set jatuh tempo 30 hari saat tanggal berubah
                    document.querySelector('input[name="tanggal"]').addEventListener('change', function() {
                        if (!document.getElementById('jatuh_tempo').value) {
                            setJatuhTempo(30);
                        }
                    });
                    </script>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" 
                                  placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i>Simpan Transaksi
                    </button>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const inputTotal = document.getElementById('total_piutang');
                            const inputDibayar = document.getElementById('dibayar_piutang');
                            const sisaDisplay = document.getElementById('sisa_piutang');

                            function formatRibuan(angka) {
                                return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }

                            function bersihkan(angka) {
                                return parseInt(angka.replace(/\./g, '')) || 0;
                            }

                            function hitungSisa() {
                                const total = bersihkan(inputTotal.value);
                                const dibayar = bersihkan(inputDibayar.value);
                                const sisa = total - dibayar;

                                sisaDisplay.textContent = "Rp " + formatRibuan(sisa >= 0 ? sisa : 0);
                            }

                            function formatInput(input) {
                                input.addEventListener('input', function() {
                                    let nilai = bersihkan(this.value);
                                    this.value = nilai > 0 ? formatRibuan(nilai) : '';
                                    hitungSisa();
                                });
                            }

                            formatInput(inputTotal);
                            formatInput(inputDibayar);

                            // Saat submit form → angka dikembalikan ke format murni
                            document.getElementById('formPiutang').addEventListener('submit', function() {
                                inputTotal.value = bersihkan(inputTotal.value);
                                inputDibayar.value = bersihkan(inputDibayar.value);
                            });
                        });
                        </script>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Piutang -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Piutang Terakhir</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Piutang</th>
                                <th>Tanggal</th>
                                <th>Pelanggan</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent->num_rows > 0): ?>
                                <?php while ($row = $recent->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $row['no_piutang']; ?></strong></td>
                                        <td><?php echo format_tanggal($row['tanggal']); ?></td>
                                        <td>
                                            <?php echo $row['nama_pelanggan']; ?><br>
                                            <small class="text-muted"><?php echo $row['jenis_jasa']; ?></small>
                                        </td>
                                        <td class="text-end fw-bold">
                                            <?php echo format_rupiah($row['total']); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php echo format_rupiah($row['sisa']); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($row['status'] == 'Lunas') ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada data piutang
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