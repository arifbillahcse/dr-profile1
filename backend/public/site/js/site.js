'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const sticky = document.querySelector('.header-sticky');
  const updateHeader = () => sticky?.classList.toggle('sticky', scrollY >= 100);
  addEventListener('scroll', updateHeader, { passive: true });
  updateHeader();
  document.querySelectorAll('.heroSwiper, .heroSwiperMobile, .mySwiper').forEach(el => {
    if (!window.Swiper || !el.querySelector('.swiper-slide')) return;
    const count = el.querySelectorAll('.swiper-slide').length;
    const review = el.classList.contains('mySwiper');
    new Swiper(el, {
      loop: count > (review ? 3 : 1),
      speed: reducedMotion ? 0 : 1000,
      spaceBetween: review ? 30 : 0,
      effect: !review && el.classList.contains('heroSwiper') ? 'fade' : 'slide',
      fadeEffect: { crossFade: true },
      autoplay: reducedMotion || count < 2 ? false : { delay: 4000, disableOnInteraction: false },
      pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
      breakpoints: review ? { 768: { slidesPerView: Math.min(count, 2) }, 1024: { slidesPerView: Math.min(count, 3) } } : {}
    });
  });
  if (window.AOS) AOS.init({ duration: 800, once: true, disable: reducedMotion });
  const observer = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
      observer.unobserve(entry.target);
    }
  }), { threshold: 0.05 });
  document.querySelectorAll('.animate-up').forEach(el => observer.observe(el));
  if (window.jQuery?.fn.magnificPopup) {
    jQuery('.popup-gallery').magnificPopup({
      delegate: 'a.img-popup', type: 'image', gallery: { enabled: true },
      image: { titleSrc: item => {
        const caption = document.createElement('span');
        caption.textContent = item.el.attr('title') || '';
        return caption.outerHTML;
      }}
    });
  }
  document.querySelector('.back-to-top-simple')?.addEventListener('click', event => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: reducedMotion ? 'instant' : 'smooth' });
  });
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('hidden.bs.modal', () => {
      modal.querySelectorAll('iframe').forEach(frame => {
        const source = frame.src;
        frame.src = 'about:blank';
        frame.src = source;
      });
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const reviewModal = document.querySelector('#reviewModal[data-review-errors]');
  if (reviewModal && window.bootstrap) {
    reviewModal.addEventListener('shown.bs.modal', () => document.getElementById('review-errors')?.focus(), { once: true });
    bootstrap.Modal.getOrCreateInstance(reviewModal).show();
  }
  document.getElementById('reviewForm')?.addEventListener('submit', () => {
    const button = document.getElementById('review-submit');
    button.disabled = true;
    button.textContent = 'Submitting…';
  });
});
