document.addEventListener('click', function (e) {
  const btn = e.target.closest('.password-toggle');
  if (!btn) return;
  const input = document.getElementById(btn.dataset.target);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
});
document.addEventListener('click', function(e){
  const btn=e.target.closest('[data-confirm]');
  if(btn && !confirm(btn.dataset.confirm)) e.preventDefault();
});
const sidebar=document.querySelector('.sidebar');
document.addEventListener('click',e=>{if(e.target.closest('.sidebar-toggle')) sidebar?.classList.toggle('open');});

// Katalog siswa: buka detail buku lalu tampilkan form pengajuan peminjaman.
document.querySelectorAll('[data-modal-target]').forEach(function (trigger) {
  trigger.addEventListener('click', function (event) {
    // Tombol di dalam card harus membuka modal tanpa menjalankan aksi lain.
    event.stopPropagation();
    const selector = trigger.getAttribute('data-modal-target');
    const modal = document.querySelector(selector);
    if (!modal) return;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
    const firstInput = modal.querySelector('input[type="date"]:not([readonly])');
    if (firstInput) setTimeout(function(){ firstInput.focus(); }, 50);
  });
});

document.querySelectorAll('[data-close-modal]').forEach(function (button) {
  button.addEventListener('click', function () {
    const modal = button.closest('.book-modal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  });
});

document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  const modal = document.querySelector('.book-modal.open');
  if (modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
  }
});


document.addEventListener('change', function(e){
 if(e.target && e.target.id==='coverInput'){ const file=e.target.files?.[0], wrap=document.getElementById('coverPreviewWrap'), img=document.getElementById('coverPreview'); if(!file){if(wrap)wrap.style.display='none';return;} if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>2*1024*1024){alert('Cover harus JPG, PNG, atau WEBP dan maksimal 2 MB.');e.target.value='';if(wrap)wrap.style.display='none';return;} if(img&&wrap){img.src=URL.createObjectURL(file);wrap.style.display='block';}}
});
