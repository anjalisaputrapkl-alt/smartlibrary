function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast ${type === 'success' ? 'toast-success' : 'toast-error'}`;
  toast.innerText = msg;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'slideIn 0.3s reverse';
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

function exportCSV() {
  window.location.href = '?export=csv';
}

function filterTable() {
  const searchText = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusFilter').value;
  const priorityFilter = document.getElementById('priorityFilter').value;
  const rows = document.querySelectorAll('tbody tr');

  rows.forEach(row => {
    const title = row.cells[1].textContent.toLowerCase();
    const author = row.cells[2].textContent.toLowerCase();
    const status = row.cells[3].textContent.trim();
    const priority = row.cells[4].textContent.trim();

    const matchSearch = title.includes(searchText) || author.includes(searchText);
    const matchStatus = !statusFilter || status.includes(statusFilter);
    const matchPriority = !priorityFilter || priority.includes(priorityFilter);

    row.style.display = matchSearch && matchStatus && matchPriority ? '' : 'none';
  });
}

function resetFilter() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('priorityFilter').value = '';
  filterTable();
}

function openAddModal() {
  document.getElementById('recordId').value = '';
  document.getElementById('maintenanceForm').reset();
  document.getElementById('modalTitle').innerText = 'Tambah Catatan Maintenance';
  document.getElementById('maintenanceModal').classList.add('active');
}

function openEditModal(id) {
  fetch('?action=get&id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.data) {
        const record = data.data;
        document.getElementById('recordId').value = record.id;
        document.getElementById('bookId').value = record.book_id;
        document.getElementById('status').value = record.status;
        document.getElementById('priority').value = record.priority || 'Normal';
        document.getElementById('followUpDate').value = record.follow_up_date || '';
        document.getElementById('notes').value = record.notes || '';
        document.getElementById('modalTitle').innerText = 'Edit Catatan Maintenance';
        document.getElementById('maintenanceModal').classList.add('active');
      }
    });
}

function closeModal() {
  document.getElementById('maintenanceModal').classList.remove('active');
}

function saveRecord() {
  const id = document.getElementById('recordId').value;
  const bookId = document.getElementById('bookId').value;
  const status = document.getElementById('status').value;
  const priority = document.getElementById('priority').value;
  const followUpDate = document.getElementById('followUpDate').value;
  const notes = document.getElementById('notes').value;

  if (!bookId || !status) {
    showToast('Buku dan Status harus dipilih!', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('action', id ? 'update' : 'add');
  formData.append('book_id', bookId);
  formData.append('status', status);
  formData.append('priority', priority);
  formData.append('follow_up_date', followUpDate);
  formData.append('notes', notes);
  if (id) formData.append('id', id);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.text().then(text => {
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid JSON');
      }
    }))
    .then(data => {
      if (data.success) {
        showToast(data.message);
        closeModal();
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    })
    .catch(err => {
      showToast('Error: ' + err.message, 'error');
    });
}

function deleteRecord(id) {
  if (!confirm('Yakin hapus catatan ini?')) return;

  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.text().then(text => {
      try {
        return JSON.parse(text);
      } catch (e) {
        throw new Error('Invalid JSON');
      }
    }))
    .then(data => {
      if (data.success) {
        showToast(data.message);
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    })
    .catch(err => {
      showToast('Error: ' + err.message, 'error');
    });
}

// Update stats on page load
document.addEventListener('DOMContentLoaded', () => {
  const records = window.recordsData;
  const good = records.filter(r => r.status === 'Good').length;
  const damaged = records.filter(r =>
    ['Damaged', 'Need Repair', 'Missing'].includes(r.status)
  ).length;

  document.getElementById('goodCount').innerText = good;
  document.getElementById('damagedCount').innerText = damaged;
});

// Close modal on outside click
document.getElementById('maintenanceModal').addEventListener('click', (e) => {
  if (e.target.id === 'maintenanceModal') closeModal();
});

// FAQ toggle functionality
document.querySelectorAll('.faq-question').forEach(q => {
  q.onclick = () => {
    const p = q.parentElement;
    p.classList.toggle('active');
    q.querySelector('span').textContent = p.classList.contains('active') ? '−' : '+';
  }
});
