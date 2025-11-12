// SIA Printing - Custom JavaScript
// ===================================

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});

// Format currency input
function formatRupiah(angka, prefix = '') {
    const number_string = angka.replace(/[^,\d]/g, '').toString();
    const split = number_string.split(',');
    const sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    const ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        const separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return prefix + rupiah;
}

// Add currency format to inputs with class 'currency'
document.querySelectorAll('.currency').forEach(function(input) {
    input.addEventListener('keyup', function(e) {
        const value = this.value.replace(/\D/g, '');
        this.value = formatRupiah(value, 'Rp ');
    });
});

// Confirmation before delete
document.querySelectorAll('.confirm-delete').forEach(function(button) {
    button.addEventListener('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
});

// Auto-calculate sisa piutang
const totalPiutang = document.getElementById('total_piutang');
const dibayarPiutang = document.getElementById('dibayar_piutang');
const sisaPiutang = document.getElementById('sisa_piutang');

if (totalPiutang && dibayarPiutang && sisaPiutang) {
    function hitungSisa() {
        const total = parseFloat(totalPiutang.value) || 0;
        const dibayar = parseFloat(dibayarPiutang.value) || 0;
        const sisa = total - dibayar;
        sisaPiutang.textContent = 'Rp ' + sisa.toLocaleString('id-ID');
    }

    totalPiutang.addEventListener('input', hitungSisa);
    dibayarPiutang.addEventListener('input', hitungSisa);
}

// Print functionality
function printPage() {
    window.print();
}

// Export to Excel (simple method)
function exportToExcel(tableId, filename = 'export.xls') {
    const table = document.getElementById(tableId);
    const html = table.outerHTML;
    const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    const downloadLink = document.createElement('a');
    
    document.body.appendChild(downloadLink);
    downloadLink.href = url;
    downloadLink.download = filename;
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Sidebar toggle for mobile
const sidebarToggle = document.querySelector('[data-bs-toggle="sidebar"]');
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('show');
    });
}

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

// Popover initialization
const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

// Auto-complete for select2 (if needed)
// Uncomment if using Select2 library
/*
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
*/

// Date picker enhancement
const dateInputs = document.querySelectorAll('input[type="date"]');
dateInputs.forEach(function(input) {
    if (!input.value) {
        input.valueAsDate = new Date();
    }
});

// Number input validation (only numbers)
document.querySelectorAll('input[type="number"]').forEach(function(input) {
    input.addEventListener('keypress', function(e) {
        if (e.which < 48 || e.which > 57) {
            if (e.which !== 8 && e.which !== 0 && e.which !== 46) {
                e.preventDefault();
            }
        }
    });
});

// Auto-submit form on select change (for filters)
document.querySelectorAll('.auto-submit').forEach(function(select) {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});

// Loading overlay
function showLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loading-overlay';
    loadingOverlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50';
    loadingOverlay.style.zIndex = '9999';
    loadingOverlay.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(loadingOverlay);
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// AJAX form submission example
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showLoading();
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                if (successCallback) {
                    successCallback(data);
                }
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            hideLoading();
            showAlert('danger', 'Terjadi kesalahan: ' + error);
        });
    });
}

// Show alert dynamically
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const mainContent = document.querySelector('main .py-4');
    if (mainContent) {
        mainContent.insertBefore(alertDiv, mainContent.firstChild);
        
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
}

// Console log suppression for production
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    console.log = function() {};
    console.warn = function() {};
    console.error = function() {};
}

console.log('SIA Printing - System ready!');