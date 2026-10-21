  function toggleMenu() {
    const navLinks = document.getElementById("navLinks");
    navLinks.classList.toggle("active");
}

let currentBannerIndex = 0;
let autoSlideInterval = null;

function setBannerSlide(index) {
    const items = document.querySelectorAll("#heroBanner .banner-item");
    if (!items.length) return;

    const total = items.length;
    currentBannerIndex = (index + total) % total;

    items.forEach((item, i) => {
        if (i === currentBannerIndex) {
            item.classList.add("active");
            // Play video on active slide
            const video = item.querySelector('video');
            if (video) {
                video.play();
            }
        } else {
            item.classList.remove("active");
            // Pause video on inactive slides
            const video = item.querySelector('video');
            if (video) {
                video.pause();
                video.currentTime = 0;
            }
        }
    });

    const progressFill = document.getElementById("bannerProgressFill");
    if (progressFill) {
        const percent = ((currentBannerIndex + 1) / total) * 100;
        progressFill.style.width = percent + "%";
    }
}

function scrollBanner(direction) {
    const items = document.querySelectorAll("#heroBanner .banner-item");
    if (!items.length) return;
    setBannerSlide(currentBannerIndex + direction);
}

// AUTO-SLIDE - simple timer based
document.addEventListener('DOMContentLoaded', function() {
    // Initialize first slide
    setBannerSlide(0);
    
    // Start auto-slide every 5 seconds
    autoSlideInterval = setInterval(() => {
        scrollBanner(1);
    }, 5000);
    
    // Handle arrow button clicks
    const leftArrow = document.querySelector('.banner-arrow-left');
    const rightArrow = document.querySelector('.banner-arrow-right');
    
    if (leftArrow) {
        leftArrow.addEventListener('click', function() {
            scrollBanner(-1);
        });
    }
    
    if (rightArrow) {
        rightArrow.addEventListener('click', function() {
            scrollBanner(1);
        });
    }
    
    // Pause auto-slide on mouse enter
    const heroBanner = document.getElementById("heroBanner");
    if (heroBanner) {
        heroBanner.addEventListener("mouseenter", () => {
            clearInterval(autoSlideInterval);
        });
        
        heroBanner.addEventListener("mouseleave", () => {
            autoSlideInterval = setInterval(() => {
                scrollBanner(1);
            }, 5000);
        });
    }
    
    // Enable touch swipe
    let touchStartX = null;
    
    function handleBannerTouchStart(e) {
        if (!e.touches || !e.touches.length) return;
        touchStartX = e.touches[0].clientX;
    }
    
    function handleBannerTouchEnd(e) {
        if (touchStartX === null || !e.changedTouches || !e.changedTouches.length) return;
        const touchEndX = e.changedTouches[0].clientX;
        const diffX = touchEndX - touchStartX;
        const threshold = 50;

        if (Math.abs(diffX) > threshold) {
            if (diffX < 0) {
                scrollBanner(1);
            } else {
                scrollBanner(-1);
            }
        }
        touchStartX = null;
    }
    
    const heroBannerElement = document.getElementById("heroBanner");
    if (heroBannerElement) {
        heroBannerElement.addEventListener("touchstart", handleBannerTouchStart, { passive: true });
        heroBannerElement.addEventListener("touchend", handleBannerTouchEnd, { passive: true });
    }
});