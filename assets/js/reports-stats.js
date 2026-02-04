/**
 * Reports Stats Controller
 * Handles clicking on stat cards and displaying the modal with detailed data
 */

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('statsModal');
    if (!modal) return;

    const modalBody = modal.querySelector('.modal-body');
    const modalTitle = modal.querySelector('.modal-header h2');
    const closeBtn = modal.querySelector('.modal-close');
    const statCards = document.querySelectorAll('.clickable[data-stat-type]');

    // API Config
    const apiConfig = {
        'total_books': {
            title: 'Daftar Semua Buku',
            endpoint: 'api/get-report-books.php',
            render: renderBooks
        },
        'borrows_month': {
            title: 'Peminjaman Bulan Ini',
            endpoint: 'api/get-report-borrows-this-month.php',
            render: renderBorrows
        },
        'returns_month': {
            title: 'Pengembalian Bulan Ini',
            endpoint: 'api/get-report-returns-this-month.php',
            render: renderReturns
        },
        'active_members': {
            title: 'Anggota Aktif (90 Hari)',
            endpoint: 'api/get-report-active-members-90.php',
            render: renderMembersActive
        },
        'late_fines': {
            title: 'Detail Denda Keterlambatan',
            endpoint: 'api/get-report-fines-late.php',
            render: renderLateFines
        },
        'damage_fines': {
            title: 'Detail Denda Kerusakan',
            endpoint: 'api/get-report-fines-damage.php',
            render: renderDamageFines
        },
        'new_members_30': {
            title: 'Anggota Baru (30 Hari)',
            endpoint: 'api/get-report-new-30.php?type=members',
            render: renderNewMembers
        },
        'new_books_30': {
            title: 'Buku Baru (30 Hari)',
            endpoint: 'api/get-report-new-30.php?type=books',
            render: renderNewBooks
        }
    };

    // Card Click Event
    statCards.forEach(card => {
        card.addEventListener('click', () => {
            const type = card.getAttribute('data-stat-type');
            const status = card.getAttribute('data-status');
            const config = { ...apiConfig[type] };

            if (status) {
                config.endpoint += (config.endpoint.includes('?') ? '&' : '?') + 'status=' + status;
                if (status === 'pending') config.title += ' (Tertunda)';
                if (status === 'paid') config.title += ' (Terbayar)';
            }

            if (config) {
                openModal(config);
            }
        });
    });

    // Close Modal Event
    closeBtn.addEventListener('click', closeModal);
    window.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    function openModal(config) {
        modalTitle.textContent = config.title;
        modalBody.innerHTML = '<div class="modal-loading">Memuat data...</div>';
        modal.style.display = 'flex';

        fetch(config.endpoint)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    config.render(res.data);
                } else {
                    modalBody.innerHTML = `<div style="text-align:center;color:red;padding:20px;">Error: ${res.message}</div>`;
                }
            })
            .catch(err => {
                modalBody.innerHTML = `<div style="text-align:center;color:red;padding:20px;">Error: ${err.message}</div>`;
            });
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    // --- Renderers ---

    function renderBooks(data) {
        renderTable(data, [
            { label: 'Judul', key: 'title', html: (row) => `<strong>${row.title}</strong><br><small>${row.author}</small>` },
            { label: 'ISBN', key: 'isbn' },
            { label: 'Stok', key: 'copies' },
            { label: 'Terdaftar', key: 'created_at', html: (row) => new Date(row.created_at).toLocaleDateString('id-ID') }
        ]);
    }

    function renderBorrows(data) {
        renderTable(data, [
            { label: 'Tanggal', key: 'borrowed_at', html: (row) => new Date(row.borrowed_at).toLocaleDateString('id-ID') },
            { label: 'Buku', key: 'title', html: (row) => `<strong>${row.title}</strong><br><small>${row.isbn}</small>` },
            { label: 'Peminjam', key: 'member_name' },
            { label: 'Status', key: 'status', html: (row) => `<span class="student-badge badge-${row.status === 'overdue' ? 'inactive' : 'active'}">${row.status}</span>` }
        ]);
    }

    function renderReturns(data) {
        renderTable(data, [
            { label: 'Kembali', key: 'returned_at', html: (row) => new Date(row.returned_at).toLocaleDateString('id-ID') },
            { label: 'Buku', key: 'title' },
            { label: 'Peminjam', key: 'member_name' },
            { label: 'Terlambat', key: 'days_late', html: (row) => `${row.days_late || 0} hari` }
        ]);
    }

    function renderMembersActive(data) {
        renderTable(data, [
            { label: 'Nama', key: 'name' },
            { label: 'NISN', key: 'nisn' },
            { label: 'Peminjaman', key: 'total_borrows' },
            { label: 'Terakhir', key: 'last_borrow', html: (row) => new Date(row.last_borrow).toLocaleDateString('id-ID') }
        ]);
    }

    function renderLateFines(data) {
        renderTable(data, [
            { label: 'Peminjam', key: 'member_name' },
            { label: 'Buku', key: 'title' },
            { label: 'Terlambat', key: 'days_late', html: (row) => `${row.days_late} hari` },
            { label: 'Denda', key: 'fine_amount', html: (row) => `<strong style="color:#dc2626;">Rp ${row.fine_amount.toLocaleString('id-ID')}</strong>` }
        ]);
    }

    function renderDamageFines(data) {
        renderTable(data, [
            { label: 'Peminjam', key: 'member_name' },
            { label: 'Buku', key: 'title' },
            { label: 'Tipe', key: 'damage_type' },
            { label: 'Denda', key: 'fine_amount', html: (row) => `<strong style="color:#dc2626;">Rp ${row.fine_amount.toLocaleString('id-ID')}</strong>` },
            { label: 'Status', key: 'status', html: (row) => `<span class="student-badge badge-${row.status === 'paid' ? 'active' : 'inactive'}">${row.status === 'paid' ? 'Lunas' : 'Tertunda'}</span>` }
        ]);
    }

    function renderNewMembers(data) {
        renderTable(data, [
            { label: 'Nama', key: 'name' },
            { label: 'NISN', key: 'nisn' },
            { label: 'Bergabung', key: 'created_at', html: (row) => new Date(row.created_at).toLocaleDateString('id-ID') }
        ]);
    }

    function renderNewBooks(data) {
        renderTable(data, [
            { label: 'Judul', key: 'title' },
            { label: 'ISBN', key: 'isbn' },
            { label: 'Ditambahkan', key: 'created_at', html: (row) => new Date(row.created_at).toLocaleDateString('id-ID') }
        ]);
    }

    function renderTable(data, cols) {
        if (!data || data.length === 0) {
            modalBody.innerHTML = '<div style="text-align:center;padding:40px;color:#888;">Tidak ada data ditemukan.</div>';
            return;
        }

        let html = '<table class="modal-table"><thead><tr>';
        cols.forEach(c => html += `<th>${c.label}</th>`);
        html += '</tr></thead><tbody>';

        data.forEach(row => {
            html += '<tr>';
            cols.forEach(c => {
                const val = c.html ? c.html(row) : row[c.key];
                html += `<td>${val}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        modalBody.innerHTML = html;
    }
});
