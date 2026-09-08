(function () {
    const track = document.getElementById("brandsTrack");

    if (!track) return;

    track.addEventListener("mouseenter", () => {
        track.style.animationPlayState = "paused";
    });

    track.addEventListener("mouseleave", () => {
        track.style.animationPlayState = "running";
    });
})();

