
// Image Preview
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('imagePreview');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">`;
        }

        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}

// FAQ Accordion
function toggleFaq(element) {
    // Close other FAQs
    const allFaqs = document.querySelectorAll('.faq-item');
    allFaqs.forEach(item => {
        if (item !== element) {
            item.classList.remove('active');
        }
    });

    // Toggle current
    element.classList.toggle('active');
}

// Detail Modal - Uses global window.booksData
function openDetailModal(index) {
    // Safety check
    if (!window.booksData || !window.booksData[index]) {
        console.error('Book data not found for index:', index);
        return;
    }

    const book = window.booksData[index];

    // Populate data
    const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    };

    set('detailTitle', book.title);
    set('detailAuthor', book.author);
    set('detailISBN', book.isbn || '-');
    set('detailCategory', book.category || '-');
    set('detailLocation', `Rak ${book.shelf || '-'} / Baris ${book.row_number || '-'}`);
    set('detailCopies', `${book.copies} Salinan Tersedia`);

    // Handle Image
    const imgContainer = document.getElementById('detailCover');
    if (imgContainer) {
        if (book.cover_image) {
            imgContainer.src = '../img/covers/' + book.cover_image;
            imgContainer.style.display = 'block';
        } else {
            imgContainer.src = '';
            imgContainer.style.display = 'none';
        }
    }

    // Show modal
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeDetail() {
    const modal = document.getElementById('detailModal');
    if (modal) modal.style.display = 'none';
}

// --- STATS LOGIC ---
function showStatDetail(type) {
    console.log('Opening stats for:', type);
    const books = window.booksData || [];
    const modal = document.getElementById('statModal');
    const titleEl = document.getElementById('statModalTitle');
    const bodyEl = document.getElementById('statModalBody');

    if (!modal || !bodyEl) return;

    let content = '';

    if (type === 'books') {
        titleEl.textContent = 'Daftar Semua Buku';
        // List top 10 newest stats or simple list
        content = `<div class="modal-stat-list">`;
        // Show last 5 books
        const recent = books.slice(0, 10); // Take first 10 (as they are ordered DESC)
        recent.forEach(b => {
            content += `
                <div class="modal-stat-item">
                    <span class="stat-item-label">${b.title}</span>
                    <span class="stat-item-val">${b.category || '-'}</span>
                </div>
             `;
        });
        content += `</div><p style="text-align:center; color:#666; margin-top:10px; font-size:12px;">Menampilkan 10 buku terbaru</p>`;

    } else if (type === 'copies') {
        titleEl.textContent = 'Stok Buku Tertinggi';
        // Sort by copies
        const sorted = [...books].sort((a, b) => b.copies - a.copies).slice(0, 10);
        content = `<div class="modal-stat-list">`;
        sorted.forEach(b => {
            content += `
                <div class="modal-stat-item">
                    <span class="stat-item-label">${b.title}</span>
                    <span class="stat-item-val">${b.copies} Eks.</span>
                </div>
             `;
        });
        content += `</div>`;

    } else if (type === 'categories') {
        titleEl.textContent = 'Statistik Kategori';
        // Group by category
        const counts = {};
        books.forEach(b => {
            const cat = b.category || 'Lainnya';
            counts[cat] = (counts[cat] || 0) + 1;
        });

        content = `<div class="modal-stat-list">`;
        for (const [key, val] of Object.entries(counts)) {
            content += `
                <div class="modal-stat-item">
                    <span class="stat-item-label">${key}</span>
                    <span class="stat-item-val">${val} Judul</span>
                </div>
             `;
        }
        content += `</div>`;
    }

    bodyEl.innerHTML = content;
    modal.style.display = 'block';
}

function closeStatModal() {
    const modal = document.getElementById('statModal');
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside (Global for all modals)
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
