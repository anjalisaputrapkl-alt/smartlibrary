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
  const damageTypeFilter = document.getElementById('damageTypeFilter').value;
  const rows = document.querySelectorAll('tbody tr');

  rows.forEach(row => {
    const memberName = row.cells[0].textContent.toLowerCase();
    const bookTitle = row.cells[1].textContent.toLowerCase();
    const damageType = row.cells[2].getAttribute('data-damage-type') || '';
    const status = row.cells[5].textContent.trim();

    const matchSearch = memberName.includes(searchText) || bookTitle.includes(searchText);
    const matchStatus = !statusFilter || status.toLowerCase().includes(statusFilter.toLowerCase());
    const matchDamage = !damageTypeFilter || damageType === damageTypeFilter;

    row.style.display = matchSearch && matchStatus && matchDamage ? '' : 'none';
  });
}

function resetFilter() {
  document.getElementById('searchInput').value = '';
  document.getElementById('statusFilter').value = '';
  document.getElementById('damageTypeFilter').value = '';
  filterTable();
}

function openAddModal() {
  document.getElementById('damageForm').reset();
  document.getElementById('fineAmount').innerText = 'Rp 0';
  document.getElementById('fineAmountInput').value = '0';
  document.getElementById('damageModal').classList.add('active');
}

function closeModal() {
  document.getElementById('damageModal').classList.remove('active');
}

function onBorrowSelected() {
  // If needed in future for auto-filling member info
}

function onDamageTypeChanged() {
  const select = document.getElementById('damageType');
  const selectedOption = select.options[select.selectedIndex];
  const fineAmount = selectedOption.getAttribute('data-fine') || '0';

  document.getElementById('fineAmount').innerText = 'Rp ' + formatCurrency(fineAmount);
  document.getElementById('fineAmountInput').value = fineAmount;
}

function formatCurrency(value) {
  return parseFloat(value).toLocaleString('id-ID');
}

function saveDamageReport() {
  const borrowId = document.getElementById('borrowId').value;
  const damageType = document.getElementById('damageType').value;
  const damageDescription = document.getElementById('damageDescription').value;
  const fineAmount = document.getElementById('fineAmountInput').value;

  if (!borrowId || !damageType) {
    showToast('Peminjaman dan Tipe Kerusakan harus dipilih!', 'error');
    return;
  }

  // Get member_id and book_id from selected option
  const borrowOption = document.getElementById('borrowId').options[document.getElementById('borrowId').selectedIndex];
  const memberId = borrowOption.getAttribute('data-member-id');
  const bookId = borrowOption.getAttribute('data-book-id');

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('borrow_id', borrowId);
  formData.append('member_id', memberId);
  formData.append('book_id', bookId);
  formData.append('damage_type', damageType);
  formData.append('damage_description', damageDescription);
  formData.append('fine_amount', fineAmount);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.json())
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

function markAsPaid(id) {
  if (!confirm('Tandai denda ini sebagai lunas?')) return;

  const formData = new FormData();
  formData.append('action', 'update_status');
  formData.append('id', id);
  formData.append('status', 'paid');

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.json())
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

function deleteRecord(id) {
  if (!confirm('Yakin hapus catatan denda ini?')) return;

  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);

  fetch(window.location.pathname, { method: 'POST', body: formData })
    .then(r => r.json())
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

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const damageTypeFilter = document.getElementById('damageTypeFilter');

  if (searchInput) searchInput.addEventListener('input', filterTable);
  if (statusFilter) statusFilter.addEventListener('change', filterTable);
  if (damageTypeFilter) damageTypeFilter.addEventListener('change', filterTable);
});

// Close modal on outside click
document.addEventListener('click', (e) => {
  const modal = document.getElementById('damageModal');
  if (e.target === modal) {
    closeModal();
  }
});
