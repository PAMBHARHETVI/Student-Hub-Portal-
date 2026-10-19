console.log("hello");

fetch("JSON/Event.json")
    .then(response => response.json())
    .then(data => {
        console.log(data.events);
});

fetch("JSON/notice.json")
    .then(response => response.json())
    .then(data => {
        console.log(data.notices);
    });

fetch("JSON/faq.json")
    .then(response => response.json())
    .then(data => {
        console.log(data.faqs);
    });

fetch("JSON/Student.json")
    .then(response => response.json())
    .then(data => {
        console.log(data.students);
    });


//----------------------------------
//STUDENT HUB - DYNAMIC UI
//----------------------------------

//LIGHT / DARK THEME

document.addEventListener("DOMContentLoaded", function () {

    const themeButton = document.getElementById("themeButton");

    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark-mode");
    } else {
        document.body.classList.remove("dark-mode");
    }

    updateThemeButton();

    if (themeButton) {

        themeButton.addEventListener("click", function () {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
            } else {
                localStorage.setItem("theme", "light");
            }

            updateThemeButton();
        });
    }

    function updateThemeButton() {

        if (!themeButton) return;

        if (document.body.classList.contains("dark-mode")) {
            themeButton.innerHTML = " ☀️ ";
            themeButton.title = "Light Mode";
        } else {
            themeButton.innerHTML = " 🌙 ";
            themeButton.title = "Dark Mode";
        }
    }
});

// ==========================================
// HAMBURGER MENU - ALL NAVIGATION PAGES
// ==========================================

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.querySelector(".sidebar");
    const topbar = document.querySelector(".topbar");

    // Run only on pages with navigation
    if (!sidebar || !topbar) return;

    let hamburgerButton =
        document.getElementById("hamburgerButton");

    // Create the button if it doesn't exist
    if (!hamburgerButton) {

        hamburgerButton = document.createElement("button");

        hamburgerButton.id = "hamburgerButton";
        hamburgerButton.className = "hamburger-button";
        hamburgerButton.type = "button";
        hamburgerButton.textContent = "☰";
        hamburgerButton.setAttribute(
            "aria-label",
            "Open navigation"
        );

        let topbarLeft = topbar.querySelector(".topbar-left");

        // Create a title container if necessary
        if (!topbarLeft) {

            topbarLeft = document.createElement("div");
            topbarLeft.className = "topbar-left";

            const title = topbar.querySelector(".topbar-title");

            if (title) {
                title.parentNode.insertBefore(
                    topbarLeft,
                    title
                );

                topbarLeft.appendChild(title);
            } else {
                topbar.insertBefore(
                    topbarLeft,
                    topbar.firstChild
                );
            }
        }

        topbarLeft.insertBefore(
            hamburgerButton,
            topbarLeft.firstChild
        );
    }

    // Open and close sidebar
    hamburgerButton.addEventListener("click", function () {

        const isOpen =
            sidebar.classList.toggle("sidebar-open");

        hamburgerButton.textContent = isOpen ? "✕" : "☰";

        hamburgerButton.setAttribute(
            "aria-label",
            isOpen ? "Close navigation" : "Open navigation"
        );

    });

});


/* =====================================
   HOME PAGE IMAGE SLIDER
   ===================================== */

document.addEventListener("DOMContentLoaded", function () {

    const slider = document.getElementById("homeSlider");

    // Run only on pages containing the slider
    if (!slider) return;

    const slides = slider.querySelectorAll(".slide-image");
    const dots = slider.querySelectorAll(".slider-dot");
    const prevButton = document.getElementById("prevSlide");
    const nextButton = document.getElementById("nextSlide");

    if (slides.length === 0) return;

    let currentIndex = 0;
    let slideTimer;

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            slide.classList.toggle("active", i === currentIndex);
        });

        dots.forEach(function (dot, i) {
            dot.classList.toggle("active", i === currentIndex);

            if (i === currentIndex) {
                dot.setAttribute("aria-current", "true");
            } else {
                dot.removeAttribute("aria-current");
            }
        });
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    function startAutoSlide() {
        stopAutoSlide();

        // Change image every 4 seconds
        slideTimer = setInterval(nextSlide, 4000);
    }

    function stopAutoSlide() {
        clearInterval(slideTimer);
    }

    nextButton.addEventListener("click", function () {
        nextSlide();
        startAutoSlide();
    });

    prevButton.addEventListener("click", function () {
        prevSlide();
        startAutoSlide();
    });

    dots.forEach(function (dot, index) {
        dot.addEventListener("click", function () {
            showSlide(index);
            startAutoSlide();
        });
    });

    // Pause automatic sliding while the mouse is over it
    slider.addEventListener("mouseenter", stopAutoSlide);
    slider.addEventListener("mouseleave", startAutoSlide);

    // Show the first image and start
    showSlide(0);
    startAutoSlide();
});

/* =========================
   COLLAPSIBLE FAQ
   ========================= */

document.addEventListener("DOMContentLoaded", function () {

    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(function (item) {

        const question = item.querySelector(".faq-question");
        const answer = item.querySelector(".faq-answer");
        const icon = item.querySelector(".faq-icon");

        question.addEventListener("click", function () {

            const isOpen = question.getAttribute("aria-expanded") === "true";

            // Close all FAQ items first
            faqItems.forEach(function (otherItem) {
                const otherQuestion =
                    otherItem.querySelector(".faq-question");
                const otherAnswer =
                    otherItem.querySelector(".faq-answer");
                const otherIcon =
                    otherItem.querySelector(".faq-icon");

                otherQuestion.setAttribute("aria-expanded", "false");
                otherAnswer.hidden = true;
                otherIcon.textContent = "+";
            });

            // Open the clicked item if it was previously closed
            if (!isOpen) {
                question.setAttribute("aria-expanded", "true");
                answer.hidden = false;
                icon.textContent = "−";
            }
        });
    });
});
    
