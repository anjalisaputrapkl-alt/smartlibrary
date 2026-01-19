document.querySelectorAll('.faq-question').forEach(q => {
  q.onclick = () => {
    const i = q.parentElement;
    i.classList.toggle('active');
    q.querySelector('span').textContent = i.classList.contains('active') ? '−' : '+';
  }
});

// Image preview
function previewImage(event) {
  const file = event.target.files[0];
  const preview = document.getElementById('imagePreview');
  
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
    };
    reader.readAsDataURL(file);
  }
}

// Detail modal
function showDetail(book) {
  document.getElementById('detailTitle').textContent = book.title;
  document.getElementById('detailAuthor').textContent = book.author;
  document.getElementById('detailISBN').textContent = book.isbn || '-';
  document.getElementById('detailCategory').textContent = book.category || '-';
  document.getElementById('detailLocation').textContent = (book.shelf || '-') + ' / Baris ' + (book.row_number || '-');
  document.getElementById('detailCopies').textContent = book.copies + ' salinan';
  
  const coverImg = document.getElementById('detailCover');
  if (book.cover_image) {
    coverImg.src = '../img/covers/' + book.cover_image;
  } else {
    coverImg.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22280%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2248%22%3E📚%3C/text%3E%3C/svg%3E';
  }
  
  document.getElementById('detailModal').classList.add('active');
}

function closeDetail() {
  document.getElementById('detailModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('detailModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeDetail();
  }
});
