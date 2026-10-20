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



/* =================================
   DYNAMIC FAQ FROM JSON/faq.json
   ================================= */

document.addEventListener("DOMContentLoaded", function () {

    const faqList = document.getElementById("faqList");

    // Only run on pages that contain the FAQ
    if (!faqList) return;

    async function loadFAQs() {
        try {
            const response = await fetch("JSON/faq.json");

            if (!response.ok) {
                throw new Error("Unable to load FAQ JSON");
            }

            const data = await response.json();
            faqList.replaceChildren();

            data.faqs.forEach(function (faq) {

                const item = document.createElement("div");
                item.className = "faq-item";

                const question = document.createElement("button");
                question.className = "faq-question";
                question.type = "button";
                question.setAttribute("aria-expanded", "false");

                const questionText = document.createElement("span");
                questionText.textContent = faq.question;

                const icon = document.createElement("span");
                icon.className = "faq-icon";
                icon.setAttribute("aria-hidden", "true");
                icon.textContent = "+";

                question.append(questionText, icon);

                const answer = document.createElement("div");
                answer.className = "faq-answer";
                answer.hidden = true;

                const paragraph = document.createElement("p");
                paragraph.textContent = faq.answer;

                answer.appendChild(paragraph);
                item.append(question, answer);
                faqList.appendChild(item);
            });

        } catch (error) {
            console.error("FAQ error:", error);
            faqList.textContent =
                "FAQs could not be loaded. Please try again.";
        }
    }

    // Event delegation works for dynamically created questions
    faqList.addEventListener("click", function (event) {

        const question = event.target.closest(".faq-question");

        if (!question || !faqList.contains(question)) return;

        const item = question.closest(".faq-item");
        const answer = item.querySelector(".faq-answer");
        const icon = item.querySelector(".faq-icon");
        const willOpen =
            question.getAttribute("aria-expanded") !== "true";

        // Close all questions
        faqList.querySelectorAll(".faq-item").forEach(function (other) {
            other.querySelector(".faq-question")
                .setAttribute("aria-expanded", "false");

            other.querySelector(".faq-answer").hidden = true;
            other.querySelector(".faq-icon").textContent = "+";
        });

        // Open the selected question
        if (willOpen) {
            question.setAttribute("aria-expanded", "true");
            answer.hidden = false;
            icon.textContent = "−";
        }
    });

    loadFAQs();
});


/* =================================
   DYNAMIC NOTICES FROM JSON/notice.json
   ================================= */

document.addEventListener("DOMContentLoaded", function () {

    const banner = document.getElementById("notificationBanner");
    const modal = document.getElementById("noticeModal");

    // Run only if this page has the notification components
    if (!banner) return;

    const bannerTitle = banner.querySelector("h3");
    const bannerDescription = banner.querySelector("p");

    const readButton = document.getElementById("readNoticeButton");
    const closeBanner = document.getElementById("closeBanner");

    const closeModalButton = document.getElementById("closeModal");
    const okButton = document.getElementById("modalOk");

    let selectedNotice = null;

    async function loadNotices() {
        try {
            const response = await fetch("JSON/notice.json");

            if (!response.ok) {
                throw new Error("Unable to load notices");
            }

            const data = await response.json();

            const priorityOrder = {
                High: 1,
                Medium: 2,
                Low: 3
            };

            // Sort a copy so the original JSON order is unchanged
            const notices = [...data.notices].sort(function (a, b) {
                return (priorityOrder[a.priority] ?? 99) -
                       (priorityOrder[b.priority] ?? 99);
            });

            // Select the first notice dated today or in the future.
            // If all notices are past, show the newest past notice.
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const upcoming = notices.filter(function (notice) {
                const date = new Date(notice.date + "T00:00:00");
                return date >= today;
            });

            const candidates = upcoming.length
                ? upcoming
                : notices.filter(function (notice) {
                    return notice.date;
                }).sort(function (a, b) {
                    return b.date.localeCompare(a.date);
                });

            selectedNotice = candidates[0];

            if (!selectedNotice) {
                banner.hidden = true;
                return;
            }

            bannerTitle.textContent = selectedNotice.title;
            bannerDescription.textContent =
                selectedNotice.description;

            banner.hidden = false;

        } catch (error) {
            console.error("Notice loading error:", error);
            bannerTitle.textContent = "Announcements unavailable";
            bannerDescription.textContent =
                "Please try again later.";
        }
    }

    function openNoticeModal() {
        if (!modal || !selectedNotice) return;

        document.getElementById("modalTitle").textContent =
            selectedNotice.title;

        document.getElementById("modalDate").textContent =
            "Date: " + selectedNotice.date;

        document.getElementById("modalCategory").textContent =
            "Category: " + selectedNotice.category;

        document.getElementById("modalPriority").textContent =
            "Priority: " + selectedNotice.priority;

        document.getElementById("modalDescription").textContent =
            selectedNotice.description;

        modal.hidden = false;
        document.body.classList.add("modal-open");

        if (closeModalButton) {
            closeModalButton.focus();
        }
    }

    function closeNoticeModal() {
        if (!modal) return;

        modal.hidden = true;
        document.body.classList.remove("modal-open");
    }

    if (readButton) {
        readButton.addEventListener("click", openNoticeModal);
    }

    if (closeBanner) {
        closeBanner.addEventListener("click", function () {
            banner.hidden = true;
        });
    }

    if (closeModalButton) {
        closeModalButton.addEventListener("click", closeNoticeModal);
    }

    if (okButton) {
        okButton.addEventListener("click", closeNoticeModal);
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeNoticeModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal && !modal.hidden) {
            closeNoticeModal();
        }
    });

    loadNotices();
});



/* =================================
   EVENTS FROM JSON/Event.json
   ================================= */

document.addEventListener("DOMContentLoaded", function () {

    const container = document.getElementById("eventsContainer");

    // Run only on pages containing the events section
    if (!container) return;

    async function loadEvents() {
        try {
            const response = await fetch("JSON/Event.json");

            if (!response.ok) {
                throw new Error("Unable to load events");
            }

            const data = await response.json();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Show events happening today or in the future
            const events = data.events
                .filter(function (event) {
                    const eventDate =
                        new Date(event.date + "T00:00:00");

                    return eventDate >= today;
                })
                .sort(function (a, b) {
                    return a.date.localeCompare(b.date);
                });

            container.replaceChildren();

            if (events.length === 0) {
                container.textContent = "No upcoming events available.";
                return;
            }

            events.forEach(function (event) {

                const card = document.createElement("article");
                card.className = "event-card";

                const category = document.createElement("span");
                category.className = "event-category";
                category.textContent = event.category;

                const title = document.createElement("h3");
                title.textContent = event.title;

                const date = document.createElement("p");
                date.textContent = "📅 " + event.date;

                const time = document.createElement("p");
                time.textContent = "🕒 " + event.time;

                const venue = document.createElement("p");
                venue.textContent = "📍 " + event.venue;

                const description = document.createElement("p");
                description.textContent = event.description;

                card.append(
                    category,
                    title,
                    date,
                    time,
                    venue,
                    description
                );

                container.appendChild(card);
            });

        } catch (error) {
            console.error("Event loading error:", error);
            container.textContent =
                "Events could not be loaded. Please try again later.";
        }
    }

    loadEvents();
});


/* =================================
   STUDENT PROFILE FROM JSON/Student.json
   ================================= */

document.addEventListener("DOMContentLoaded", function () {

    const profileName = document.getElementById("profileStudentName");
    const sidebarName = document.getElementById("sidebarStudentName");

    // Run only on pages containing student profile elements
    if (!profileName && !sidebarName) return;

    async function loadStudent() {
        try {
            const response = await fetch("JSON/Student.json");

            if (!response.ok) {
                throw new Error("Unable to load student data");
            }

            const data = await response.json();

            // Example: show the first student record
            const student = data.students[0];

            if (!student) {
                throw new Error("No student record found");
            }

            function setText(id, value) {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = value ?? "";
                }
            }

            // Sidebar
            setText("sidebarStudentName", student.name);
            setText(
                "sidebarStudentMeta",
                `Roll No: ${student.rollNo} | Batch ${student.division}`
            );

            // Main profile card
            setText("profileStudentName", student.name);
            setText("profileRollNo", `Roll Number: ${student.rollNo}`);
            setText("profileBatch", `Lab Batch: ${student.division}`);

            // Academic details
            setText("detailStudentName", student.name);
            setText("detailRollNo", student.rollNo);
            setText("detailEmail", student.email);
            setText("detailNumber", "+91 " + student.mobile);
            setText("detailDepartment", student.department);
            setText("detailSemester", student.semester);
            setText("detailDivision", student.division);

        } catch (error) {
            console.error("Student loading error:", error);

            setProfileError();
        }
    }

    function setProfileError() {
        ["profileStudentName", "sidebarStudentName"].forEach(function (id) {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = "Student information unavailable";
            }
        });
    }

    loadStudent();
});
