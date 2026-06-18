document.addEventListener('DOMContentLoaded', function() {
    // 1. Sidebar Toggler
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // 2. Client-Side Table Search/Filter
    const searchInputs = document.querySelectorAll('[data-search-table]');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const tableId = this.getAttribute('data-search-table');
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const query = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let match = false;
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(query)) {
                        match = true;
                    }
                });
                
                if (match) {
                    row.style.display = '';
                } else {
                    // Check if it is a form or special row
                    if (row.classList.contains('no-search')) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    });

    // 3. Auto-Dismiss Bootstrap Alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.6s ease';
            setTimeout(() => {
                alert.remove();
            }, 600);
        }, 5000);
    });

    // 4. Quick Print Helper
    const printButtons = document.querySelectorAll('[data-print]');
    printButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            window.print();
        });
    });
});
