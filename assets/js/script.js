// SIA Printing - Custom JavaScript
// Bootstrap Modal Compatibility
// ===================================

// Simulate Bootstrap Modal API
class Modal {
    constructor(element) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.isShown = false;
    }

    show() {
        if (this.element) {
            this.element.classList.add('show');
            this.element.style.display = 'flex';
            this.isShown = true;
            document.body.style.overflow = 'hidden';
        }
    }

    hide() {
        if (this.element) {
            this.element.classList.remove('show');
            this.element.style.display = 'none';
            this.isShown = false;
            document.body.style.overflow = '';
        }
    }

    toggle() {
        if (this.isShown) {
            this.hide();
        } else {
            this.show();
        }
    }
}

// Bootstrap compatibility object
const bootstrap = {
    Modal: Modal
};

// Auto-initialize modals with data-bs-toggle
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal triggers
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-bs-target');
            const targetModal = document.querySelector(targetSelector);
            if (targetModal) {
                const modal = new Modal(targetModal);
                modal.show();
            }
        });
    });

    // Handle modal close buttons
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(closeBtn) {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                const modalInstance = new Modal(modal);
                modalInstance.hide();
            }
        });
    });

    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const modalInstance = new Modal(this);
                modalInstance.hide();
            }
        });
    });

    // Handle collapse toggle
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetSelector);
            if (targetElement) {
                targetElement.classList.toggle('show');
            }
        });
    });
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
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
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.currency').forEach(function(input) {
        input.addEventListener('keyup', function(e) {
            const value = this.value.replace(/\D/g, '');
            this.value = formatRupiah(value, 'Rp ');
        });
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

console.log('SIA Printing - System ready!');