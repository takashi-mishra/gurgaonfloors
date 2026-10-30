document.addEventListener("DOMContentLoaded", () => {

    // =====================================
    // 1. RESPONSIVE HAMBURGER MENU
    // =====================================

    const menuToggle = document.getElementById("menuToggle");
    const navMenu = document.getElementById("navMenu");

    if (menuToggle && navMenu) {

        function closeMenu() {
            navMenu.classList.remove("is-open");

            menuToggle.setAttribute("aria-expanded", "false");
            menuToggle.setAttribute("aria-label", "Open navigation menu");
        }

        function toggleMenu() {
            const isOpen = navMenu.classList.toggle("is-open");

            menuToggle.setAttribute(
                "aria-expanded",
                String(isOpen)
            );

            menuToggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );
        }

        menuToggle.addEventListener("click", toggleMenu);

        // Close the menu after selecting a link.
        navMenu.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", closeMenu);
        });

        // Close when clicking outside the navigation.
        document.addEventListener("click", (event) => {
            if (
                navMenu.classList.contains("is-open") &&
                !navMenu.contains(event.target) &&
                !menuToggle.contains(event.target)
            ) {
                closeMenu();
            }
        });

        // Close with Escape key.
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeMenu();
            }
        });

        // Reset the mobile menu when returning to desktop.
        window.addEventListener("resize", () => {
            if (window.innerWidth > 900) {
                closeMenu();
            }
        });
    }


    // =====================================
    // 2. HERO IMAGE SLIDER
    // =====================================

    const slides = Array.from(
        document.querySelectorAll(".hero-background[data-label]")
    );

    const dots = Array.from(
        document.querySelectorAll(".slider-dot")
    );

    const currentSlide = document.getElementById("currentSlide");
    const totalSlides = document.getElementById("totalSlides");
    const slideLocation = document.getElementById("slideLocation");
    const slideLabel = document.getElementById("slideLabel");

    // Stop safely if there are no valid slides.
    if (slides.length === 0) {
        console.warn(
            "Hero slider: No slides found. Check data/content.json."
        );

        return;
    }

    let activeIndex = 0;
    let sliderInterval = null;

    const SLIDE_DURATION = 5000;

    // Preload images to reduce delays during transitions.
    slides.forEach((slide) => {
        const background = slide.style.backgroundImage;
        const match = background.match(/url\(["']?(.*?)["']?\)/);

        if (match && match[1]) {
            const image = new Image();
            image.src = match[1];
        }
    });

    if (totalSlides) {
        totalSlides.textContent = String(slides.length).padStart(2, "0");
    }

    function showSlide(index) {

        activeIndex = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle("active", i === activeIndex);
        });

        dots.forEach((dot, i) => {
            const isActive = i === activeIndex;

            dot.classList.toggle("active", isActive);
            dot.setAttribute("aria-pressed", String(isActive));
        });

        const active = slides[activeIndex];

        if (currentSlide) {
            currentSlide.textContent = String(activeIndex + 1).padStart(2, "0");
        }

        if (slideLocation) {
            slideLocation.textContent =
                active.dataset.location || "Gurgaon, Haryana";
        }

        if (slideLabel) {
            slideLabel.textContent =
                active.dataset.label || "Luxury Living";
        }
    }

    function stopSlider() {
        if (sliderInterval !== null) {
            clearInterval(sliderInterval);
            sliderInterval = null;
        }
    }

    function startSlider() {
        stopSlider();

        // No need to animate if there is only one slide.
        if (slides.length < 2 || document.hidden) {
            return;
        }

        sliderInterval = setInterval(() => {
            showSlide(activeIndex + 1);
        }, SLIDE_DURATION);
    }

    // Allow visitors to select an image manually.
    dots.forEach((dot, index) => {
        dot.addEventListener("click", () => {
            showSlide(index);
            startSlider();
        });
    });

    // Pause while the browser tab is hidden.
    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            stopSlider();
        } else {
            startSlider();
        }
    });

    // Initialize the first slide.
    showSlide(0);
    startSlider();

});