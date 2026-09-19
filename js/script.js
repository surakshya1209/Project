// Auto-hide flash messages after a few seconds
document.addEventListener('DOMContentLoaded', function () {
  const flash = document.querySelector('.flash');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.4s';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 400);
    }, 3500);
  }

  // Live preview for the star rating input on the product page
  // Star rating input on the product page - click to select, hover to preview
  const starInput = document.getElementById('star-input');
  if (starInput) {
    const labels = Array.from(starInput.querySelectorAll('label'));

    function paint(value) {
      labels.forEach(label => {
        const input = label.querySelector('input');
        const span = label.querySelector('span');
        const v = parseInt(input.value, 10);
        span.style.color = v <= value ? '#f4a259' : '#ddd';
      });
    }

    function currentChecked() {
      const checked = starInput.querySelector('input:checked');
      return checked ? parseInt(checked.value, 10) : 0;
    }

    labels.forEach(label => {
      const input = label.querySelector('input');
      label.addEventListener('mouseenter', () => paint(parseInt(input.value, 10)));
      input.addEventListener('change', () => paint(parseInt(input.value, 10)));
    });

    starInput.addEventListener('mouseleave', () => paint(currentChecked()));

    paint(currentChecked());
  }
  // Simple client-side quantity guard on add-to-cart forms
  document.querySelectorAll('.add-cart-form, .qty-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      const qtyInput = form.querySelector('input[name="quantity"]');
      if (qtyInput) {
        const max = parseInt(qtyInput.getAttribute('max') || '9999', 10);
        const min = parseInt(qtyInput.getAttribute('min') || '1', 10);
        let val = parseInt(qtyInput.value, 10) || min;
        if (val < min) val = min;
        if (val > max) val = max;
        qtyInput.value = val;
      }
    });
  });
});
