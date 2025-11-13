<?php
require_once 'config.php';
check_role(['akuntan', 'owner']);

$page_title = 'Jurnal Umum';
$current_page = 'jurnal-umum';

// Process VOID
if (isset($_POST['void_jurnal']) && can_edit()) {
    $id_jurnal = intval($_POST['id_jurnal']);
    $void_reason = clean_input($_POST['void_reason']);
    
    $result = void_jurnal($id_jurnal, $void_reason);
    
    if ($result['success']) {
        alert($result['message'], 'success');
    } else {
        alert($result['message'], 'danger');
    }
    redirect('jurnal-umum.php');
}

// Process EDIT
if (isset($_POST['edit_jurnal']) && can_edit()) {
    $id_jurnal = intval($_POST['id_jurnal']);
    $tanggal = clean_input($_POST['tanggal']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $nominal = floatval($_POST['nominal']);
    $id_akun_debit = intval($_POST['id_akun_debit']);
    $id_akun_kredit = intval($_POST['id_akun_kredit']);
    
    // Get old data untuk reverse saldo
    $old_data = $conn->query("SELECT * FROM jurnal_umum WHERE id_jurnal = $id_jurnal")->fetch_assoc();
    
    if ($old_data) {
        $conn->begin_transaction();
        
        try {
            // 1. Reverse saldo lama
            $old_nominal = $old_data['nominal'];
            $old_debit = $old_data['id_akun_debit'];
            $old_kredit = $old_data['id_akun_kredit'];
            
            // Kembalikan saldo lama
            $akun_debit_old = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $old_debit")->fetch_assoc();
            $akun_kredit_old = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $old_kredit")->fetch_assoc();
            
            if (strpos($akun_debit_old['tipe_akun'], '1-Aktiva') !== false || strpos($akun_debit_old['tipe_akun'], '5-Beban') !== false) {
                $conn->query("UPDATE master_akun SET saldo = saldo - $old_nominal WHERE id_akun = $old_debit");
            } else {
                $conn->query("UPDATE master_akun SET saldo = saldo + $old_nominal WHERE id_akun = $old_debit");
            }
            
            if (strpos($akun_kredit_old['tipe_akun'], '1-Aktiva') !== false || strpos($akun_kredit_old['tipe_akun'], '5-Beban') !== false) {
                $conn->query("UPDATE master_akun SET saldo = saldo + $old_nominal WHERE id_akun = $old_kredit");
            } else {
                $conn->query("UPDATE master_akun SET saldo = saldo - $old_nominal WHERE id_akun = $old_kredit");
            }
            
            // 2. Update jurnal
            $stmt = $conn->prepare("UPDATE jurnal_umum SET tanggal = ?, deskripsi = ?, id_akun_debit = ?, id_akun_kredit = ?, nominal = ? WHERE id_jurnal = ?");
            $stmt->bind_param("ssiidi", $tanggal, $deskripsi, $id_akun_debit, $id_akun_kredit, $nominal, $id_jurnal);
            $stmt->execute();
            
            // 3. Apply saldo baru
            $akun_debit_new = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_debit")->fetch_assoc();
            $akun_kredit_new = $conn->query("SELECT tipe_akun FROM master_akun WHERE id_akun = $id_akun_kredit")->fetch_assoc();
            
            if (strpos($akun_debit_new['tipe_akun'], '1-Aktiva') !== false || strpos($akun_debit_new['tipe_akun'], '5-Beban') !== false) {
                $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_debit");
            } else {
                $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_debit");
            }
            
            if (strpos($akun_kredit_new['tipe_akun'], '1-Aktiva') !== false || strpos($akun_kredit_new['tipe_akun'], '5-Beban') !== false) {
                $conn->query("UPDATE master_akun SET saldo = saldo - $nominal WHERE id_akun = $id_akun_kredit");
            } else {
                $conn->query("UPDATE master_akun SET saldo = saldo + $nominal WHERE id_akun = $id_akun_kredit");
            }
            
            $conn->commit();
            alert('Jurnal berhasil diupdate!', 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            alert('Gagal update jurnal: ' . $e->getMessage(), 'danger');
        }
    }
    redirect('jurnal-umum.php');
}

// Filter
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : '';
$show_void = isset($_GET['show_void']) ? $_GET['show_void'] : '0';

// Query jurnal umum
$query = "SELECT ju.*, 
          ma_debit.kode_akun as kode_debit, ma_debit.nama_akun as nama_debit,
          ma_kredit.kode_akun as kode_kredit, ma_kredit.nama_akun as nama_kredit,
          u.nama as voided_by_nama
          FROM jurnal_umum ju
          JOIN master_akun ma_debit ON ju.id_akun_debit = ma_debit.id_akun
          JOIN master_akun ma_kredit ON ju.id_akun_kredit = ma_kredit.id_akun
          LEFT JOIN users u ON ju.voided_by = u.id_user
          WHERE DATE_FORMAT(ju.tanggal, '%Y-%m') = '$bulan'";

if ($show_void == '0') {
    $query .= " AND ju.is_void = 0";
}

if ($tipe) {
    $query .= " AND ju.tipe_transaksi = '$tipe'";
}

$query .= " ORDER BY ju.tanggal ASC, ju.id_jurnal ASC";

$result = $conn->query($query);

// Get accounts for edit modal
$accounts = $conn->query("SELECT * FROM master_akun ORDER BY kode_akun");

$total_nominal = 0;
if ($result->num_rows > 0) {
    $temp_result = $conn->query($query);
    while ($row = $temp_result->fetch_assoc()) {
        if ($row['is_void'] == 0) {
            $total_nominal += $row['nominal'];
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Title -->
<div class="page-title">
    <h1><i class="bi bi-journal-text me-2"></i>Jurnal Umum</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Jurnal Umum</li>
        </ol>
    </nav>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Periode</label>
                <input type="month" name="bulan" class="form-control" value="<?php echo $bulan; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipe Transaksi</label>
                <select name="tipe" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="Pendapatan Tunai" <?php echo ($tipe == 'Pendapatan Tunai') ? 'selected' : ''; ?>>Pendapatan Tunai</option>
                    <option value="Pendapatan Kredit" <?php echo ($tipe == 'Pendapatan Kredit') ? 'selected' : ''; ?>>Pendapatan Kredit</option>
                    <option value="Penerimaan Piutang" <?php echo ($tipe == 'Penerimaan Piutang') ? 'selected' : ''; ?>>Penerimaan Piutang</option>
                    <option value="Pendapatan Lainnya" <?php echo ($tipe == 'Pendapatan Lainnya') ? 'selected' : ''; ?>>Pendapatan Lainnya</option>
                    <option value="Modal Awal" <?php echo ($tipe == 'Modal Awal') ? 'selected' : ''; ?>>Modal Awal</option>
                    <option value="Beban" <?php echo ($tipe == 'Beban') ? 'selected' : ''; ?>>Beban</option>
                    <option value="Jurnal Penyesuaian" <?php echo ($tipe == 'Jurnal Penyesuaian') ? 'selected' : ''; ?>>Jurnal Penyesuaian</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="show_void" class="form-select">
                    <option value="0" <?php echo ($show_void == '0') ? 'selected' : ''; ?>>Aktif Saja</option>
                    <option value="1" <?php echo ($show_void == '1') ? 'selected' : ''; ?>>Termasuk Void</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="jurnal-umum.php" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </a>
                <button type="button" onclick="window.print()" class="btn btn-success no-print">
                    <i class="bi bi-printer me-1"></i>Cetak
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Jurnal Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-book me-2"></i>Daftar Jurnal Umum
            <?php 
            $nama_bulan = array(
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
            );
            $pecah_bulan = explode('-', $bulan);
            echo '- ' . $nama_bulan[$pecah_bulan[1]] . ' ' . $pecah_bulan[0];
            ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="8%" class="text-center">Tanggal</th>
                        <th width="8%">Referensi</th>
                        <th width="20%">Deskripsi</th>
                        <th width="8%">Kode Akun</th>
                        <th width="18%">Nama Akun</th>
                        <th width="11%" class="text-end">Debit</th>
                        <th width="11%" class="text-end">Kredit</th>
                        <?php if (can_edit()): ?>
                        <th width="8%" class="text-center no-print">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if ($result->num_rows > 0): 
                    $current_date = '';
                    $total_debit = 0;
                    $total_kredit = 0;

                    while ($row = $result->fetch_assoc()): 
                        $is_void = $row['is_void'] == 1;
                        $row_class = $is_void ? 'table-danger' : '';
                        
                        if ($current_date != $row['tanggal']) {
                            $current_date = $row['tanggal'];
                ?>
                        <tr class="table-secondary">
                            <td colspan="<?php echo can_edit() ? '8' : '7'; ?>" class="fw-bold">
                                <i class="bi bi-calendar3 me-2"></i>
                                <?php echo format_tanggal($row['tanggal']); ?>
                                <?php if ($is_void): ?>
                                    <span class="badge bg-danger ms-2">VOID</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                <?php } ?>

                        <!-- Baris Debit -->
                        <tr class="<?php echo $row_class; ?>">
                            <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td>
                                <small class="text-muted"><?php echo $row['referensi']; ?></small>
                                <?php if ($is_void): ?>
                                    <br><span class="badge bg-danger">VOID</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $row['deskripsi']; ?>
                                <?php if ($row['tipe_transaksi']): ?>
                                    <br><small class="badge bg-info"><?php echo $row['tipe_transaksi']; ?></small>
                                <?php endif; ?>
                                <?php if ($is_void): ?>
                                    <br><small class="text-danger"><strong>Void by:</strong> <?php echo $row['voided_by_nama']; ?> | <?php echo date('d/m/Y H:i', strtotime($row['voided_at'])); ?></small>
                                    <br><small class="text-danger"><strong>Alasan:</strong> <?php echo $row['void_reason']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $row['kode_debit']; ?></strong></td>
                            <td><?php echo $row['nama_debit']; ?></td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($row['nominal']); ?></td>
                            <td></td>
                            <?php if (can_edit()): ?>
                            <td class="text-center no-print" rowspan="2">
                                <?php if (!$is_void): ?>
                                    <button type="button" class="btn btn-sm btn-warning mb-1" 
                                            onclick='editJurnal(<?php echo json_encode($row); ?>)' 
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="voidJurnal(<?php echo $row['id_jurnal']; ?>, '<?php echo $row['referensi']; ?>')" 
                                            title="Void">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Void</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Baris Kredit (menjorok ke dalam) -->
                        <tr class="<?php echo $row_class; ?>" style="background-color: #f9f9f9;">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><strong><?php echo $row['kode_kredit']; ?></strong></td>
                            <td style="padding-left: 40px;"><?php echo $row['nama_kredit']; ?></td>
                            <td></td>
                            <td class="text-end fw-bold"><?php echo format_rupiah($row['nominal']); ?></td>
                        </tr>

                <?php
                        if (!$is_void) {
                            $total_debit += $row['nominal'];
                            $total_kredit += $row['nominal'];
                        }
                    endwhile;
                ?>
                    <!-- Baris Total -->
                    <tr class="table-primary">
                        <td colspan="5" class="text-end fw-bold">TOTAL TRANSAKSI AKTIF:</td>
                        <td class="text-end fw-bold"><?php echo format_rupiah($total_debit); ?></td>
                        <td class="text-end fw-bold"><?php echo format_rupiah($total_kredit); ?></td>
                        <?php if (can_edit()): ?>
                        <td></td>
                        <?php endif; ?>
                    </tr>

                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo can_edit() ? '8' : '7'; ?>" class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                            Tidak ada data jurnal untuk periode ini
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Jurnal -->
<?php if (can_edit()): ?>
<div class="modal" id="modalEdit">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Edit Jurnal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="edit_jurnal" value="1">
                <input type="hidden" name="id_jurnal" id="edit_id_jurnal">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Perhatian:</strong> Edit jurnal akan mengubah saldo akun terkait. Pastikan data sudah benar!
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Akun Debit</label>
                        <select name="id_akun_debit" id="edit_akun_debit" class="form-select" required>
                            <?php 
                            $accounts->data_seek(0);
                            $current_tipe = '';
                            while ($acc = $accounts->fetch_assoc()): 
                                if ($current_tipe != $acc['tipe_akun']) {
                                    if ($current_tipe != '') echo '</optgroup>';
                                    echo '<optgroup label="' . $acc['tipe_akun'] . '">';
                                    $current_tipe = $acc['tipe_akun'];
                                }
                            ?>
                                <option value="<?php echo $acc['id_akun']; ?>">
                                    <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                                </option>
                            <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Akun Kredit</label>
                        <select name="id_akun_kredit" id="edit_akun_kredit" class="form-select" required>
                            <?php 
                            $accounts->data_seek(0);
                            $current_tipe = '';
                            while ($acc = $accounts->fetch_assoc()): 
                                if ($current_tipe != $acc['tipe_akun']) {
                                    if ($current_tipe != '') echo '</optgroup>';
                                    echo '<optgroup label="' . $acc['tipe_akun'] . '">';
                                    $current_tipe = $acc['tipe_akun'];
                                }
                            ?>
                                <option value="<?php echo $acc['id_akun']; ?>">
                                    <?php echo $acc['kode_akun'] . ' - ' . $acc['nama_akun']; ?>
                                </option>
                            <?php endwhile; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nominal (Rp)</label>
                        <input type="number" name="nominal" id="edit_nominal" class="form-control" required min="0" step="100">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save me-1"></i>Update Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Void Jurnal -->
<div class="modal" id="modalVoid">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle me-2"></i>Void Jurnal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="void_jurnal" value="1">
                <input type="hidden" name="id_jurnal" id="void_id_jurnal">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Peringatan:</strong> Void jurnal akan mengembalikan saldo akun dan menandai jurnal sebagai tidak aktif. Data tidak akan dihapus permanen (audit trail).
                    </div>
                    
                    <p>Anda akan void jurnal: <strong id="void_referensi"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Alasan Void <span class="text-danger">*</span></label>
                        <textarea name="void_reason" class="form-control" rows="3" 
                                  placeholder="Jelaskan alasan void jurnal ini..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Void Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editJurnal(data) {
    document.getElementById('edit_id_jurnal').value = data.id_jurnal;
    document.getElementById('edit_tanggal').value = data.tanggal;
    document.getElementById('edit_akun_debit').value = data.id_akun_debit;
    document.getElementById('edit_akun_kredit').value = data.id_akun_kredit;
    document.getElementById('edit_nominal').value = data.nominal;
    document.getElementById('edit_deskripsi').value = data.deskripsi;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}

function voidJurnal(id, referensi) {
    document.getElementById('void_id_jurnal').value = id;
    document.getElementById('void_referensi').textContent = referensi;
    
    const modal = new bootstrap.Modal(document.getElementById('modalVoid'));
    modal.show();
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>