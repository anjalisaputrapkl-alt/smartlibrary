// Activity tabs functionality
document.querySelectorAll('.activity-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const tabName = tab.getAttribute('data-tab');

    // Remove active class from all tabs and contents
    document.querySelectorAll('.activity-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.activity-content').forEach(c => c.classList.remove('active'));

    // Add active class to clicked tab and its content
    tab.classList.add('active');
    document.getElementById(tabName + '-content').classList.add('active');
  });
});

// FAQ functionality
document.querySelectorAll('.faq-question').forEach(item => {
  item.addEventListener('click', () => {
    const parent = item.parentElement;
    parent.classList.toggle('active');
    item.querySelector('span').textContent =
      parent.classList.contains('active') ? '−' : '+';
  });
});

// Initialize charts
function initializeCharts(monthlyBorrows) {
  new Chart(document.getElementById('borrowChart'), {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
      datasets: [{
        data: monthlyBorrows,
        borderColor: '#2563eb',
        tension: 0.3
      }]
    },
    options: { plugins: { legend: { display: false } } }
  });
}

function initializeStatusChart(totalBooks, totalBorrowed, totalOverdue) {
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: ['Tersedia', 'Dipinjam', 'Terlambat'],
      datasets: [{
        data: [
          totalBooks - totalBorrowed,
          totalBorrowed,
          totalOverdue
        ],
        backgroundColor: ['#16a34a', '#2563eb', '#dc2626']
      }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });
}
