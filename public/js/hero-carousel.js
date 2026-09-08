document.addEventListener('DOMContentLoaded', () => {

    const items = document.querySelectorAll('.carousel-item');
    let current = 0;

    function nextSlide() {
        items[current].classList.remove('active');
        current = (current + 1) % items.length;
        items[current].classList.add('active');
    }

    // Show first slide
    if (items.length > 0) {
        items[0].classList.add('active');
    }

    // Change every 5 seconds
    setInterval(nextSlide, 5000);

});