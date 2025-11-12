<?php
if (!is_logged_in()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-calculator-fill me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-2" style="font-size: 1.5rem;"></i>
                            <div class="d-flex flex-column align-items-start">
                                <span class="fw-semibold"><?php echo $_SESSION['nama']; ?></span>
                                <small class="text-white-50"><?php echo ucfirst($_SESSION['user_role']); ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="dashboard.php">
                                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php" 
                                   onclick="return confirm('Apakah Anda yakin ingin logout?')">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>" 
                               href="dashboard.php">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['user_role'] == 'kasir'): ?>
                            <li class="nav-item">
                                <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>MASTER DATA</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'master-pelanggan') ? 'active' : ''; ?>" 
                                href="master-pelanggan.php">
                                    <i class="bi bi-people me-2"></i>Master Pelanggan
                                </a>
                            </li>
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>TRANSAKSI</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'pendapatan-tunai') ? 'active' : ''; ?>" 
                                   href="pendapatan-tunai.php">
                                    <i class="bi bi-cash-coin me-2"></i>Pendapatan Tunai
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'pendapatan-kredit') ? 'active' : ''; ?>" 
                                   href="pendapatan-kredit.php">
                                    <i class="bi bi-credit-card me-2"></i>Pendapatan Kredit
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'penerimaan-piutang') ? 'active' : ''; ?>" 
                                   href="penerimaan-piutang.php">
                                    <i class="bi bi-receipt me-2"></i>Penerimaan Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'pendapatan-lainnya') ? 'active' : ''; ?>" 
                                   href="pendapatan-lainnya.php">
                                    <i class="bi bi-wallet2 me-2"></i>Pendapatan Lainnya
                                </a>
                            </li>
                            <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>PIUTANG</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                                href="kartu-piutang.php">
                                    <i class="bi bi-card-text me-2"></i>Kartu Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'umur-piutang') ? 'active' : ''; ?>" 
                                href="umur-piutang.php">
                                    <i class="bi bi-clock-history me-2"></i>Umur Piutang
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['user_role'] == 'akuntan'): ?>
                        <li class="nav-item">
                            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                <span>MASTER DATA</span>
                            </h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'master-akun') ? 'active' : ''; ?>" 
                            href="master-akun.php">
                                <i class="bi bi-list-ul me-2"></i>Master Akun
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                <span>TRANSAKSI</span>
                            </h6>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'transaksi-umum') ? 'active' : ''; ?>" 
                            href="transaksi-umum.php">
                                <i class="bi bi-journal-plus me-2"></i>Transaksi Umum
                            </a>
                        </li>
                            <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>PIUTANG</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                                href="kartu-piutang.php">
                                    <i class="bi bi-card-text me-2"></i>Kartu Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'umur-piutang') ? 'active' : ''; ?>" 
                                href="umur-piutang.php">
                                    <i class="bi bi-clock-history me-2"></i>Umur Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>AKUNTANSI</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'jurnal-umum') ? 'active' : ''; ?>" 
                                   href="jurnal-umum.php">
                                    <i class="bi bi-journal-text me-2"></i>Jurnal Umum
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'buku-besar') ? 'active' : ''; ?>" 
                                   href="buku-besar.php">
                                    <i class="bi bi-book me-2"></i>Buku Besar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'neraca-saldo') ? 'active' : ''; ?>" 
                                   href="neraca-saldo.php">
                                    <i class="bi bi-calculator me-2"></i>Neraca Saldo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'jurnal-penyesuaian') ? 'active' : ''; ?>" 
                                   href="jurnal-penyesuaian.php">
                                    <i class="bi bi-pencil-square me-2"></i>Jurnal Penyesuaian
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'buku-besar-penyesuaian') ? 'active' : ''; ?>" 
                                   href="buku-besar-penyesuaian.php">
                                    <i class="bi bi-book-half me-2"></i>Buku Besar Setelah Penyesuaian
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'neraca-penyesuaian') ? 'active' : ''; ?>" 
                                   href="neraca-penyesuaian.php">
                                    <i class="bi bi-calculator-fill me-2"></i>Neraca Setelah Penyesuaian
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['user_role'] == 'owner'): ?>
                            <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>PIUTANG</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'kartu-piutang') ? 'active' : ''; ?>" 
                                href="kartu-piutang.php">
                                    <i class="bi bi-card-text me-2"></i>Kartu Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'umur-piutang') ? 'active' : ''; ?>" 
                                href="umur-piutang.php">
                                    <i class="bi bi-clock-history me-2"></i>Umur Piutang
                                </a>
                            </li>
                            <li class="nav-item">
                                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                                    <span>LAPORAN</span>
                                </h6>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'laba-rugi') ? 'active' : ''; ?>" 
                                   href="laba-rugi.php">
                                    <i class="bi bi-graph-up me-2"></i>Laba Rugi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'perubahan-modal') ? 'active' : ''; ?>" 
                                   href="perubahan-modal.php">
                                    <i class="bi bi-currency-dollar me-2"></i>Perubahan Modal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'neraca-akhir') ? 'active' : ''; ?>" 
                                   href="neraca-akhir.php">
                                    <i class="bi bi-table me-2"></i>Neraca Akhir
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="py-4">
                    <?php show_alert(); ?>