/**
 * Borrows Stats Controller
 * Handles clicking on stat cards and displaying the modal with detailed data
 */

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('statsModal');
    if (!modal) return;

    const modalBody = modal.querySelector('.modal-body');
    const modalTitle = modal.querySelector('.modal-header h2');
    const closeBtn = modal.querySelector('.modal-close');
    const statCards = document.querySelectorAll('.stat-card.clickable');

    // API Config
    const apiConfig = {
        'total': {
            title: 'Daftar Semua Peminjaman',
            endpoint: 'api/get-borrows-total.php'
        },
        'active': {
            title: 'Daftar Peminjaman Sedang Dipinjam',
            endpoint: 'api/get-borrows-active.php'
        },
        'overdue': {
            title: 'Daftar Peminjaman Terlambat',
            endpoint: 'api/get-borrows-overdue.php'
        },
        'pending_confirmation': {
            title: 'Form Menunggu Konfirmasi',
            endpoint: 'api/get-borrows-pending-confirmation.php'
        },
        'pending_return': {
            title: 'Pengembalian Menunggu Konfirmasi',
            endpoint: 'api/get-borrows-pending-return.php'
        }
    };

    // Card Click Event
    statCards.forEach(card => {
        card.addEventListener('click', () => {
            const type = card.getAttribute('data-stat-type');
            const config = apiConfig[type];
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
                    renderData(res.data);
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

    function renderData(data) {
        if (!data || data.length === 0) {
            modalBody.innerHTML = '<div style="text-align:center;padding:40px;color:#888;">Tidak ada data untuk kategori ini.</div>';
            return;
        }

        let html = `
            <table class="modal-table">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="col-hide-mobile">Peminjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        data.forEach(item => {
            let statusClass = 'badge-active';
            let statusLabel = item.status;

            if (item.status === 'overdue') {
                statusClass = 'badge-inactive';
                statusLabel = 'Terlambat';
            } else if (item.status === 'returned') {
                statusClass = 'badge-active';
                statusLabel = 'Kembali';
            } else if (item.status === 'pending_confirmation') {
                statusClass = 'badge-inactive';
                statusLabel = 'Tunggu Konfirmasi';
            } else if (item.status === 'pending_return') {
                statusClass = 'badge-inactive';
                statusLabel = 'Tunggu Kembali';
            } else {
                statusLabel = 'Dipinjam';
            }

            const dueDate = item.due_at ? new Date(item.due_at).toLocaleDateString('id-ID') : '-';

            html += `
                <tr>
                    <td>
                        <div style="font-weight:600;">${item.title}</div>
                        <div style="font-size:11px;color:#888;">ISBN: ${item.isbn}</div>
                    </td>
                    <td class="col-hide-mobile">
                        <div style="font-weight:600;">${item.member_name}</div>
                        <div style="font-size:11px;color:#888;">NISN: ${item.nisn}</div>
                    </td>
                    <td>${dueDate}</td>
                    <td><span class="student-badge ${statusClass}">${statusLabel}</span></td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        modalBody.innerHTML = html;
    }
});
