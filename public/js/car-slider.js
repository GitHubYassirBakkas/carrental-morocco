const slider = document.getElementById("carSlider");
const nextBtn = document.getElementById("nextBtn");
const prevBtn = document.getElementById("prevBtn");

let currentIndex = 1;
let cardWidth;
let cards;

function setupSlider() {
    cards = slider.children.length;

    // حساب العرض الحقيقي ديال الكارت
    cardWidth = slider.children[0].getBoundingClientRect().width + 32; 
    // 32 = gap-8 (Tailwind)

    slider.style.transition = "none";
    slider.style.transform = `translateX(-${cardWidth}px)`;
}

// Clone first & last
const firstClone = slider.children[0].cloneNode(true);
const lastClone = slider.children[slider.children.length - 1].cloneNode(true);

slider.appendChild(firstClone);
slider.insertBefore(lastClone, slider.children[0]);

setupSlider();

nextBtn.addEventListener("click", () => {
    currentIndex++;
    slider.style.transition = "0.5s";
    slider.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
});

prevBtn.addEventListener("click", () => {
    currentIndex--;
    slider.style.transition = "0.5s";
    slider.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
});

slider.addEventListener("transitionend", () => {
    if (currentIndex === slider.children.length - 1) {
        slider.style.transition = "none";
        currentIndex = 1;
        slider.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
    }

    if (currentIndex === 0) {
        slider.style.transition = "none";
        currentIndex = slider.children.length - 2;
        slider.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
    }
});

// Responsive on resize
window.addEventListener("resize", () => {
    setupSlider();
});

    // script Categorie car rental processe
    
