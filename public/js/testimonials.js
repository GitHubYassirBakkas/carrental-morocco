var swiper = new Swiper(".testimonials-slider", {
    loop: true,
    autoplay: {
        delay: 2600,
        disableOnInteraction: false,
    },
    speed: 800,
    grabCursor: true,
    slidesPerView: 1,
    spaceBetween: 30,
    breakpoints: {
        768: { slidesPerView: 2 },
        1200: { slidesPerView: 3 }
    }
});