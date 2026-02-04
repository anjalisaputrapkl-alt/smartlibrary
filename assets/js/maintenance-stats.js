// Maintenance Stats Modal Manager
const maintenanceStatsModal = {
    init() {
        console.log('maintenanceStatsModal.init() called');

        const overlay = document.getElementById('statsModal');
        console.log('Stats modal overlay found:', !!overlay);

        if (overlay) {
            // Setup overlay click to close
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    console.log('Overlay clicked - closing modal');
                    this.closeModal();
                }
            });

            // Setup close button
            const closeBtn = overlay.querySelector('.modal-close');
            console.log('Close button found:', !!closeBtn);

            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    console.log('Close button clicked');
                    this.closeModal();
                });
            }
        }

        // Setup KPI card click listeners
        this.setupCardListeners();
    },

    setupCardListeners() {
        const cards = document.querySelectorAll('.kpi-card[data-stat-type]');
        console.log('KPI cards found:', cards.length);

        cards.forEach((card, index) => {
            const type = card.dataset.statType;
            console.log(`Card ${index + 1}: type="${type}"`);

            card.addEventListener('click', () => {
                console.log('Card clicked:', type);
                this.openModal(type);
            });
        });
    },

    openModal(type) {
        const overlay = document.getElementById('statsModal');
        const container = overlay.querySelector('.modal-container');

        if (!overlay || !container) return;

        // Reset content
        const body = overlay.querySelector('.modal-body');
        body.innerHTML = '<div class="modal-loading">Memuat data...</div>';

        // Show overlay
        overlay.classList.add('active');

        // Set title based on type
        const titles = {
            'reports': 'Semua Laporan Kerusakan',
            'fines': 'Semua Denda (Berdasarkan Jumlah)',
            'pending': 'Denda Tertunda'
        };

        overlay.querySelector('.modal-header h2').textContent = titles[type] || 'Detail Data';

        // Fetch data based on type
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
            'reports': '/perpustakaan-online/public/api/get-maintenance-reports.php',
            'fines': '/perpustakaan-online/public/api/get-maintenance-fines.php',
            'pending': '/perpustakaan-online/public/api/get-maintenance-pending.php'
        };

        try {
            const url = endpoints[type] || endpoints.reports;
            console.log('Fetching from:', url);

            const response = await fetch(url, {
                credentials: 'include',
                method: 'GET'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Response:', result);

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

        // Create table based on type
        if (type === 'reports') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="col-hide-mobile">Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th>Status</th>
                        <th class="col-hide-mobile">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.member_name}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.nisn}</div>
                        </td>
                        <td class="col-hide-mobile">${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td>
                            <span class="status-badge ${item.status_class === 'paid' ? 'active' : 'inactive'}">
                                ${item.status}
                            </span>
                        </td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        } else if (type === 'fines') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th>Status</th>
                        <th class="col-hide-mobile">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td><strong>${item.member_name}</strong></td>
                        <td>${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td>
                            <span class="status-badge ${item.status_class === 'paid' ? 'active' : 'inactive'}">
                                ${item.status}
                            </span>
                        </td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        } else if (type === 'pending') {
            html += `
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th class="col-hide-mobile">Buku</th>
                        <th>Tipe Kerusakan</th>
                        <th>Denda</th>
                        <th class="col-hide-mobile">Dipinjam</th>
                        <th class="col-hide-mobile">Dilaporkan</th>
                    </tr>
                </thead>
                <tbody>
            `;
            data.forEach(item => {
                html += `
                    <tr>
                        <td>
                            <strong>${item.member_name}</strong>
                            <div style="font-size: 11px; color: var(--muted);">${item.nisn}</div>
                        </td>
                        <td class="col-hide-mobile">${item.book_title}</td>
                        <td>
                            <span style="font-size: 12px; padding: 4px 8px; background: rgba(220, 38, 38, 0.1); color: #dc2626; border-radius: 4px;">
                                ${item.damage_type}
                            </span>
                        </td>
                        <td><strong style="color: #dc2626;">${item.fine_formatted}</strong></td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.borrowed_at}</td>
                        <td class="col-hide-mobile" style="font-size: 12px;">${item.created_at}</td>
                    </tr>
                `;
            });
        }

        html += '</tbody></table>';
        body.innerHTML = html;
    },

    displayError(message) {
        const body = document.querySelector('#statsModal .modal-body');
        body.innerHTML = `<div class="modal-empty" style="color: var(--danger);">${message}</div>`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    maintenanceStatsModal.init();
});
