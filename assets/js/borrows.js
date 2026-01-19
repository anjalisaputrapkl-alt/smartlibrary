document.querySelectorAll('.faq-question').forEach(q => {
  q.onclick = () => {
    const p = q.parentElement;
    p.classList.toggle('active');
    q.querySelector('span').textContent = p.classList.contains('active') ? '−' : '+';
  }
});

function showBorrowDetail(borrowData) {
  const modal = document.getElementById('borrowDetailModal');
  if (!modal) return;
  
  // Populate modal fields
  const coverImg = document.getElementById('borrowDetailCover');
  if (borrowData.cover_image && borrowData.cover_image.trim()) {
    coverImg.src = '../img/covers/' + borrowData.cover_image;
    coverImg.onerror = () => {
      coverImg.innerHTML = '📚';
    };
  } else {
    coverImg.innerHTML = '📚';
  }
  
  document.getElementById('borrowDetailTitle').textContent = borrowData.title || '-';
  document.getElementById('borrowDetailMember').textContent = borrowData.member_name || '-';
  document.getElementById('borrowDetailBorrowDate').textContent = borrowData.borrowed_at ? new Date(borrowData.borrowed_at).toLocaleDateString('id-ID') : '-';
  document.getElementById('borrowDetailDueDate').textContent = borrowData.due_at ? new Date(borrowData.due_at).toLocaleDateString('id-ID') : '-';
  document.getElementById('borrowDetailReturnDate').textContent = borrowData.returned_at ? new Date(borrowData.returned_at).toLocaleDateString('id-ID') : '-';
  document.getElementById('borrowDetailStatus').textContent = borrowData.status ? borrowData.status.charAt(0).toUpperCase() + borrowData.status.slice(1) : '-';
  
  // Show modal
  modal.classList.add('active');
}

function closeBorrowDetail() {
  const modal = document.getElementById('borrowDetailModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

// Close modal when clicking outside of modal-content
document.addEventListener('click', (e) => {
  const modal = document.getElementById('borrowDetailModal');
  if (modal && e.target === modal) {
    closeBorrowDetail();
  }
});
