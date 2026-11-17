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

// Form pengajuan peminjaman (katalog siswa): jatuh tempo mengikuti tanggal pinjam.
document.querySelectorAll('.loan-form').forEach(function (form) {
  const borrow = form.querySelector('.borrow-date-input');
  const due = form.querySelector('.due-date-output');
  const days = parseInt(form.dataset.days || '14', 10);
  if (!borrow || !due) return;

  function addDays(dateValue, amount) {
    const d = new Date(dateValue + 'T00:00:00');
    d.setDate(d.getDate() + amount);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }
  function refreshDue() {
    if (borrow.value) due.value = addDays(borrow.value, days);
  }
  borrow.addEventListener('change', refreshDue);
  borrow.addEventListener('input', refreshDue);
  refreshDue();
});

// Form pengembalian (admin): tampilkan nama hari dan status sanksi sesuai tanggal yang dipilih.
document.querySelectorAll('.return-form').forEach(function (form) {
  const input = form.querySelector('.return-date-input');
  const due = form.dataset.dueDate;
  const sanctionDays = form.dataset.sanctionDays || '14';
  const label = form.querySelector('.return-sanction-label');
  const dayLabel = form.querySelector('.return-date-day');
  if (!input || !label) return;

  function refreshReturnState() {
    const late = input.value && due && input.value > due;
    input.classList.toggle('late-date', !!late);
    label.textContent = late ? 'Sanksi: Aktif ' + sanctionDays + ' hari' : 'Sanksi: Tidak ada';
    label.classList.toggle('late', !!late);

    if (dayLabel) {
      if (input.value) {
        const date = new Date(input.value + 'T00:00:00');
        dayLabel.textContent = 'Tanggal pengembalian: ' + new Intl.DateTimeFormat('id-ID', {
          weekday: 'long', day: '2-digit', month: 'long', year: 'numeric'
        }).format(date);
      } else {
        dayLabel.textContent = '';
      }
    }
  }
  input.addEventListener('input', refreshReturnState);
  input.addEventListener('change', refreshReturnState);
  refreshReturnState();
});

// Halaman daftar: kolom "Kode Admin" hanya tampil bila memilih peran Admin.
(function () {
  const group = document.getElementById('adminCodeGroup');
  if (!group) return;
  const radios = document.querySelectorAll('input[name="peran"]');
  function refresh() {
    const checked = document.querySelector('input[name="peran"]:checked');
    group.classList.toggle('is-hidden', !checked || checked.value !== 'admin');
  }
  radios.forEach(function (r) { r.addEventListener('change', refresh); });
  refresh();
})();
