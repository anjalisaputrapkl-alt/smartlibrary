console.log('🟢 Dashboard script starting...');
window.dashboardReady = false;

function waitForDependencies(callback) {
  if (typeof jQuery !== 'undefined' && typeof Chart !== 'undefined') {
    callback();
  } else {
    setTimeout(() => waitForDependencies(callback), 50);
  }
}

waitForDependencies(function () {
  console.log('🟢 Dependencies ready, initializing...');
  initDashboard();
});

function initDashboard() {
  console.log('🟢 initDashboard() started');

  const trendLabels = window.chartData.trendLabels;
  const trendData = window.chartData.trendData;
  const categoryLabels = window.chartData.categoryLabels;
  const categoryData = window.chartData.categoryData;
  const memLabels = window.chartData.memLabels;
  const memData = window.chartData.memData;

  // Init charts
  console.log('Rendering trend chart...');
  const ctxTrend = document.getElementById('chart-trend');
  const trendChart = new Chart(ctxTrend.getContext('2d'), {
    type: 'line',
    data: { labels: trendLabels, datasets: [{ label: 'Peminjaman', data: trendData, borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.12)', tension: 0.25, fill: true }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
  window.trendChartGlobal = trendChart;

  if (categoryLabels.length > 0) {
    console.log('Rendering category chart...');
    const ctxCat = document.getElementById('chart-category');
    if (ctxCat) {
      const catChart = new Chart(ctxCat.getContext('2d'), {
        type: 'pie',
        data: { labels: categoryLabels, datasets: [{ data: categoryData, backgroundColor: ['#60a5fa', '#34d399', '#f59e0b', '#f97316', '#a78bfa', '#fb7185', '#94a3b8'] }] },
        options: { responsive: true, maintainAspectRatio: false }
      });
      window.globalCategoryChart = catChart;
    }
  }

  console.log('Rendering members chart...');
  const ctxMem = document.getElementById('chart-members');
  const memChart = new Chart(ctxMem.getContext('2d'), {
    type: 'bar',
    data: { labels: memLabels, datasets: [{ label: 'Anggota Baru', data: memData, backgroundColor: '#8b5cf6' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
  window.memChartGlobal = memChart;

  // Init DataTables
  console.log('Initializing DataTables...');
  const tables = jQuery('.datatable');

  if (tables.length >= 1) {
    window.borrowsDataTable = jQuery(tables[0]).DataTable({
      pageLength: 10,
      order: [[1, 'desc']],
      responsive: true
    });
  }
  if (tables.length >= 2) {
    window.returnsDataTable = jQuery(tables[1]).DataTable({
      pageLength: 10,
      order: [[1, 'desc']],
      responsive: true
    });
  }
  if (tables.length >= 3) {
    window.booksDataTable = jQuery(tables[2]).DataTable({
      pageLength: 10,
      order: [[1, 'asc']],
      responsive: true
    });
  }

  console.log('🟢 Charts and tables ready');

  // Helper functions
  window.showToast = function (msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;right:18px;bottom:18px;background:#333;color:#fff;padding:12px 16px;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
    t.innerText = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity 0.3s'; setTimeout(() => t.remove(), 300); }, 2500);
  };

  window.numberFormat = function (num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  };

  window.escapeHtml = function (text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  };

  // Export function
  window.exportToExcel = function () {
    if (typeof XLSX === 'undefined') {
      showToast('XLSX library belum loaded');
      return;
    }

    const wb = XLSX.utils.book_new();

    const tables = [
      { id: 'tbl-borrows', name: 'Borrows' },
      { id: 'tbl-returns', name: 'Returns' },
      { id: 'tbl-books', name: 'Books' }
    ];

    tables.forEach(config => {
      const el = document.getElementById(config.id);
      if (!el) return;

      const headers = [];
      el.querySelectorAll('thead th').forEach(th => {
        headers.push(th.innerText.trim());
      });

      const rows = [];
      el.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
          row.push(td.innerText.trim());
        });
        if (row.length > 0) {
          rows.push(row);
        }
      });

      const jsonData = rows.map(row => {
        const obj = {};
        headers.forEach((h, i) => {
          obj[h] = row[i] || '';
        });
        return obj;
      });

      const ws = XLSX.utils.json_to_sheet(jsonData, { header: headers });
      const wscols = headers.map((h, i) => {
        if (i === 1 || i === 5 || i === 6) {
          return { wch: 35 };
        }
        return { wch: 20 };
      });
      ws['!cols'] = wscols;

      XLSX.utils.book_append_sheet(wb, ws, config.name);
    });

    const fname = 'perpustakaan-report-' + new Date().toISOString().slice(0, 10) + '.xlsx';
    try {
      XLSX.writeFile(wb, fname);
      showToast('✅ Export berhasil: ' + fname);
    } catch (err) {
      console.error('Export error:', err);
      showToast('❌ Export gagal: ' + err.message);
    }
  };

  // Filter handler
  console.log('🟢 Setting up filter button...');

  window.applyFilter = function () {
    console.log('🔵 applyFilter() called');
    const startDate = document.getElementById('filter-start').value;
    const endDate = document.getElementById('filter-end').value;
    const category = document.getElementById('filter-category').value;

    console.log('Filter params:', { startDate, endDate, category });

    if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
      showToast('Tanggal awal harus lebih kecil dari tanggal akhir');
      return;
    }

    showToast('Memproses filter...');

    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (category) params.append('category', category);

    const url = './reports-filter.php?' + params.toString();
    console.log('Fetching:', url);

    fetch(url)
      .then(r => r.json())
      .then(data => {
        console.log('Response:', data);
        if (data.error) {
          showToast('Error: ' + data.error);
          return;
        }

        // Update charts
        if (window.trendChartGlobal) {
          window.trendChartGlobal.data.labels = data.trend.labels;
          window.trendChartGlobal.data.datasets[0].data = data.trend.data;
          window.trendChartGlobal.update();
        }

        if (window.globalCategoryChart && data.category.labels.length > 0) {
          window.globalCategoryChart.data.labels = data.category.labels;
          window.globalCategoryChart.data.datasets[0].data = data.category.data;
          window.globalCategoryChart.update();
        }

        if (window.memChartGlobal) {
          window.memChartGlobal.data.labels = data.members.labels;
          window.memChartGlobal.data.datasets[0].data = data.members.data;
          window.memChartGlobal.update();
        }

        // Update KPI
        const kpiCards = document.querySelectorAll('.kpi-card');
        if (kpiCards.length >= 5) {
          kpiCards[0].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.tot_books);
          kpiCards[1].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.borrows_month);
          kpiCards[2].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.returns_month);
          kpiCards[3].querySelector('.kpi-value').innerText = window.numberFormat(data.stats.active_members);
          kpiCards[4].querySelector('.kpi-value').innerText = 'Rp ' + window.numberFormat(data.stats.fines);
        }

        showToast('✅ Filter berhasil diterapkan');
      })
      .catch(err => {
        console.error('Fetch error:', err);
        showToast('❌ Error: ' + err.message);
      });
  };

  // Attach button listeners
  const btnFilter = document.getElementById('btn-apply');
  if (btnFilter) {
    console.log('✅ btn-apply found');
    btnFilter.addEventListener('click', (e) => {
      e.preventDefault();
      window.applyFilter();
    });
  } else {
    console.error('❌ btn-apply NOT FOUND');
  }

  const btnExport = document.getElementById('btn-export-excel');
  if (btnExport) {
    btnExport.addEventListener('click', (e) => {
      e.preventDefault();
      window.exportToExcel();
    });
  }

  window.dashboardReady = true;
  console.log('✅ Dashboard ready!');
}
