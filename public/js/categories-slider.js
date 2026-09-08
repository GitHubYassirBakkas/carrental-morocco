const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

dots.forEach(dot => {
    dot.addEventListener("click", () => {
        let index = dot.dataset.slide;

        slides.forEach(s => s.style.transform = `translateX(-${index}00%)`);
        dots.forEach(d => d.classList.remove("active"));
        dot.classList.add("active");
    });
});