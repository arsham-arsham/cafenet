// assets/app.js
document.addEventListener('DOMContentLoaded', () => {

    // تبدیل ارقام فارسی به انگلیسی هنگام تایپ
    const map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9',
                 '٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
    document.querySelectorAll('input.digits').forEach(inp => {
      inp.addEventListener('input', () => {
        inp.value = inp.value.replace(/[۰-۹٠-٩]/g, d => map[d]);
      });
    });
  
    // تاریخ شمسی امروز
    function todayJalali() {
      const parts = new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-latn', {
        year: 'numeric', month: '2-digit', day: '2-digit'
      }).formatToParts(new Date());
      const get = t => parts.find(p => p.type === t).value;
      return `${get('year')}/${get('month')}/${get('day')}`;
    }
    const reqDate = document.getElementById('reqDate');
    if (reqDate && !reqDate.value) reqDate.value = todayJalali();
  
    // تایید حذف
    document.querySelectorAll('form[data-confirm]').forEach(f => {
      f.addEventListener('submit', ev => {
        if (!confirm(f.dataset.confirm)) ev.preventDefault();
      });
    });
  
    // پیش‌نمایش عکس
    const pi = document.getElementById('photoInput');
    const pv = document.getElementById('photoPreview');
    if (pi && pv) {
      pi.addEventListener('change', () => {
        if (pi.files && pi.files[0]) {
          pv.src = URL.createObjectURL(pi.files[0]);
          pv.style.display = 'block';
        }
      });
    }
  
    // نمایش رمزها
    const rb = document.getElementById('revealBtn');
    if (rb) {
      rb.addEventListener('click', async () => {
        const fd = new FormData();
        fd.append('id', rb.dataset.id);
        fd.append('csrf', rb.dataset.csrf);
        const res = await fetch('reveal.php', { method: 'POST', body: fd });
        if (!res.ok) { alert('خطا در دریافت اطلاعات'); return; }
        const d = await res.json();
        document.getElementById('ppVal').textContent = d.personal;
        document.getElementById('spVal').textContent = d.sana;
        rb.disabled = true;
        setTimeout(() => {
          document.getElementById('ppVal').textContent = '••••••';
          document.getElementById('spVal').textContent = '••••••';
          rb.disabled = false;
        }, 15000);
      });
    }
  });
  