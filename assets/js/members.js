document.querySelectorAll('.faq-question').forEach(q => {
  q.onclick = () => {
    const p = q.parentElement;
    p.classList.toggle('active');
    q.querySelector('span').textContent = p.classList.contains('active') ? '−' : '+';
  }
});
