// Barcode Stats Modal Manager
const barcodeStatsModal = {
    init() {
        console.log('barcodeStatsModal.init() called');

        const overlay = document.getElementById('statsModal');
        if (overlay) {
            // Setup overlay click to close
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.closeModal();
                }
            });

            // Setup close button
            const closeBtn = overlay.querySelector('.modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal();
                });
            }
        }

        // Setup Card Listeners
        this.setupCardListeners();
    },

    setupCardListeners() {
        const cards = document.querySelectorAll('.stat-card.clickable[data-stat-type]');
        cards.forEach(card => {
            card.addEventListener('click', () => {
                const type = card.dataset.statType;
                this.openModal(type);
            });
        });
    },

    openModal(type) {
        const overlay = document.getElementById('statsModal');
        if (!overlay) return;

        const body = overlay.querySelector('.modal-body');
        body.innerHTML = '<div class="modal-loading">Memuat data...</div>';

        overlay.classList.add('active');

        const titles = {
            'total': 'Daftar Semua Siswa',
            'active': 'Daftar Siswa Aktif',
            'borrowing': 'Daftar Siswa dengan Peminjaman Aktif'
        };

        overlay.querySelector('.modal-header h2').textContent = titles[type] || 'Detail Siswa';

        this.fetchAndDisplayData(type);
    },

    closeModal() {
        const overlay = document.getElementById('statsModal');
        if (overlay) {
            overlay.classList.remove('active');
        }
    },

    async fetchAndDisplayData(type) {
        const endpoints = {
            'total': '/perpustakaan-online/public/api/get-stats-members.php',
            'active': '/perpustakaan-online/public/api/get-students-active.php',
            'borrowing': '/perpustakaan-online/public/api/get-students-borrowing.php'
        };

        try {
            const url = endpoints[type];
            const response = await fetch(url, {
                credentials: 'include',
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.displayData(type, result.data);
            } else {
                this.displayError(result.message || 'Terjadi kesalahan saat memuat data');
            }
        } catch (error) {
            console.error('Error:', error);
            this.displayError('Gagal memuat data. Silakan coba lagi. Error: ' + error.message);
        }
    },

    displayData(type, data) {
        const body = document.querySelector('#statsModal .modal-body');

        if (!data || data.length === 0) {
            body.innerHTML = '<div class="modal-empty">Tidak ada data untuk ditampilkan</div>';
            return;
        }

        let html = '<table class="modal-table">';
        html += `
            <thead>
                <tr>
                    <th>Nama</th>
                    <th class="col-hide-mobile">NISN</th>
                    <th>Status</th>
                    <th class="col-hide-mobile">Peminjaman</th>
                </tr>
            </thead>
            <tbody>
        `;

        data.forEach(member => {
            html += `
                <tr>
                    <td><strong>${member.name}</strong></td>
                    <td class="col-hide-mobile">${member.nisn}</td>
                    <td><span class="student-badge ${member.status === 'Aktif' ? 'badge-active' : 'badge-inactive'}">${member.status}</span></td>
                    <td class="col-hide-mobile">${member.current_borrows} buku</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        body.innerHTML = html;
    },

    displayError(message) {
        const body = document.querySelector('#statsModal .modal-body');
        body.innerHTML = `<div class="modal-empty" style="color: var(--danger); font-size: 14px;">${message}</div>`;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    barcodeStatsModal.init();
});
