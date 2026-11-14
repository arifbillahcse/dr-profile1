'use strict';

// Reviews on the reference use its private server. This static copy keeps
// previews in this browser and never posts data to the reference site.
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('reviewForm');
  if (!form) return;
  const message = document.getElementById('reviewMessage');
  const label = document.getElementById('btnText');
  const storageKey = 'dola-local-review-previews-v1';
  label.textContent = 'Save Review Preview';
  const note = document.createElement('p');
  note.className = 'small text-muted';
  note.textContent = 'Reviews are saved as previews in this browser. They are not submitted to the clinic.';
  form.insertBefore(note, form.firstChild);
  message.setAttribute('role', 'status');
  message.setAttribute('aria-live', 'polite');

  function showPreview(review) {
    const wrapper = document.querySelector('.mySwiper .swiper-wrapper');
    const original = wrapper?.querySelector('.swiper-slide');
    if (!original) return;
    const slide = original.cloneNode(true);
    slide.removeAttribute('data-swiper-slide-index');
    slide.querySelector('.review-card > p').textContent = review.message;
    slide.querySelector('h6').textContent = review.name;
    slide.querySelector('small').textContent = [review.workplace, 'Local preview'].filter(Boolean).join(' · ');
    wrapper.append(slide);
    wrapper.closest('.swiper').swiper?.update();
  }

  let reviews = [];
  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
    if (Array.isArray(saved)) reviews = saved.filter(r => r && typeof r.name === 'string' && typeof r.message === 'string' && typeof r.workplace === 'string').slice(-12);
  } catch { /* Storage may be disabled. Previews still work for this visit. */ }
  reviews.forEach(showPreview);

  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    const review = Object.fromEntries(['name', 'workplace', 'message'].map(key => [key, String(data.get(key) || '').trim()]));
    if (!review.name || !review.message) return;
    reviews.push(review);
    let persisted = true;
    try { localStorage.setItem(storageKey, JSON.stringify(reviews.slice(-12))); } catch { persisted = false; }
    showPreview(review);
    message.className = 'mt-2 alert alert-success';
    message.textContent = persisted ? 'Your review preview is saved in this browser.' : 'Your review preview is visible for this visit.';
    form.reset();
  });
});
