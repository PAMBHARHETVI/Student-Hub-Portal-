
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
