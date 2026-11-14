(function ($) {
    "use strict";

    // 1. Sticky Header
    $(window).on('scroll', function () {
        var scroll = $(window).scrollTop();
        if (scroll < 100) { // ১০০ পিক্সেলের বেশি স্ক্রল করলেই স্টিকি হবে
            $(".header-sticky").removeClass("sticky");
        } else {
            $(".header-sticky").addClass("sticky");
        }
    });

    // 2. Swiper Sliders & AOS initialization
    document.addEventListener('DOMContentLoaded', function () {

        // Desktop Hero Slider
        if (document.querySelector('.heroSwiper')) {
            new Swiper(".heroSwiper", {
                loop: true,
                speed: 1200,
                effect: "fade",
                fadeEffect: { crossFade: true },
                autoplay: { delay: 4000, disableOnInteraction: false },
                pagination: { el: ".swiper-pagination", clickable: true },
            });
        }

        // Mobile Hero Slider
        if (document.querySelector('.heroSwiperMobile')) {
            new Swiper(".heroSwiperMobile", {
                loop: true,
                speed: 1000,
                autoplay: { delay: 3500, disableOnInteraction: false },
                pagination: { el: ".swiper-pagination", clickable: true },
            });
        }

        // Service Modal Logic (Fixed Error)
        const serviceModal = document.getElementById('serviceDetailModal');
        if (serviceModal) {
            serviceModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const title = button.getAttribute('data-title');
                const description = button.getAttribute('data-desc');

                const modalTitle = serviceModal.querySelector('#serviceTitle');
                const modalBody = serviceModal.querySelector('#serviceDescription');

                if (modalTitle) modalTitle.textContent = title;
                if (modalBody) modalBody.textContent = description;
            });
        }

        // Video Card Hover Effect (Fixed Error)
        const videoCards = document.querySelectorAll('.video-card');
        if (videoCards.length > 0) {
            videoCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    videoCards.forEach(c => { if (c !== card) c.style.opacity = '0.7'; });
                });
                card.addEventListener('mouseleave', () => {
                    videoCards.forEach(c => { c.style.opacity = '1'; });
                });
            });
        }

        // Intersection Observer for Animations
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-up').forEach((el) => {
            observer.observe(el);
        });
    });

    // 3. AOS Animation (Initialized once)
    AOS.init({
        duration: 1000,
        once: true,
    });

    // 4. Testimonial/Common Swiper
    if (document.querySelector('.mySwiper')) {
        new Swiper(".mySwiper", {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: {
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            }
        });
    }

    // 5. Magnific Popup Gallery
    $(document).ready(function () {
        if ($('.popup-gallery').length > 0) {
            $('.popup-gallery').magnificPopup({
                delegate: 'a',
                type: 'image',
                gallery: {
                    enabled: true,
                    navigateByImgClick: true,
                    preload: [0, 1]
                },
                image: {
                    titleSrc: function (item) {
                        return item.el.attr('title');
                    }
                },
                zoom: {
                    enabled: true,
                    duration: 300,
                    easing: 'ease-in-out'
                }
            });
        }
    });

})(jQuery);
