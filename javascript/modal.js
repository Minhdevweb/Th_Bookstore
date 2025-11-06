// Modal helpers extracted from Home/index.php
function openModal(modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (el.classList.contains('modal')) {
    el.classList.add('active');
  } else {
    el.style.display = 'block';
  }
  const backdrop = document.getElementById('modalBackdrop');
  if (backdrop) backdrop.style.display = 'block';
}

function closeModal(modalId) {
  const el = document.getElementById(modalId);
  if (!el) return;
  if (el.classList.contains('modal')) {
    el.classList.remove('active');
  } else {
    el.style.display = 'none';
  }
  const backdrop = document.getElementById('modalBackdrop');
  if (backdrop) backdrop.style.display = 'none';
}

