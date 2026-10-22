"use strict"

console.log("hello");

let FnameInput = document.getElementById("Fname");
let LnameInput = document.getElementById("Lname");
let EmailInput = document.getElementById("Email");
let mobileInput = document.getElementById("mobile");
let dobInput = document.getElementById("dob");
let departmentInput = document.getElementById("department");
let instituteInput = document.getElementById("institute");
let divInput = document.getElementById("division");
let acadInput = document.getElementById("acadmic-year");
let passwordInput = document.getElementById("password");

let submit = document.getElementById("btn");

let FnameError = document.getElementById("FnameError");
let LnameError = document.getElementById("LnameError");
let EmailError = document.getElementById("EmailError");
let mobileError = document.getElementById("mobileError");
let dateError = document.getElementById("dateError");
let deptError = document.getElementById("deptError");
let instError = document.getElementById("instError");
let divError = document.getElementById("divError");
let acadError = document.getElementById("acadError");
let passError = document.getElementById("passError");

let FnamePattern = /^[A-Za-z ]+$/;
let LnamePattern = /^[A-Za-z ]+$/;
let EmailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
let mobilePattern = /^[0-9]{10}$/;
let passwordPattern =/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

submit.addEventListener("click", function(event)
{
    event.preventDefault();
    let isValid = true;

    let Fname = FnameInput.value.trim();
    let Lname = LnameInput.value.trim();
    let Email = EmailInput.value.trim();
    let mobile = mobileInput.value.trim();
    let dob = dobInput.value;
    let today = new Date();
    today.setHours(0, 0, 0, 0);
    dateError.textContent = "";
    let department = departmentInput.value;
    let institute = instituteInput.value;
    let division = divInput.value;
    let acad = acadInput.value;
    let password = passwordInput.value;

    if(Fname === "")
    {
        FnameError.innerHTML = "First Name is Required";
        isValid = false;
        FnameInput.focus();
    }
    else if(!FnamePattern.test(Fname))
    {
        FnameError.innerHTML = "First Name contain only alphabets";
        isValid = false;
        FnameInput.focus();
    }
    else
    {
        FnameError.innerHTML = "";

    }

    if(Lname === "")
    {
        LnameError.innerHTML = "Last Name is Required";
        isValid = false;
        LnameInput.focus();
    }
    else if(!LnamePattern.test(Lname))
    {
        LnameError.innerHTML = "Last Name contain only alphabets";
        isValid = false;
        LnameInput.focus();
    }
    else
    {
        LnameError.innerHTML = "";
    }

    if(Email === "") {
        EmailError.innerHTML = "Email is required";
        isValid = false;
        EmailInput.focus();
    }
    else if (!EmailPattern.test(Email)) {
        EmailError.innerHTML = "Email is not valid";
        isValid = false;
        EmailInput.focus();
    }
    else {
        EmailError.innerHTML = "";
    }

    if(mobile === "") {
        mobileError.innerHTML = "Mobile number is required";
        isValid = false;
        mobileInput.focus();
    }
    else if (!mobilePattern.test(mobile)) {
        mobileError.innerHTML = "Mobile number is not valid";
        isValid = false;
        mobileInput.focus();
    }
    else {
        mobileError.innerHTML = "";
    }

    if (dob === "") {
        dateError.textContent = "Date of Birth is required";
        isValid = false;
        dobInput.focus();
    }
    else {
        let selectedDate = new Date(dob + "T00:00:00");

        if (isNaN(selectedDate.getTime())) {
            dateError.textContent = "Please enter a valid date";
            isValid = false;
            dobInput.focus();
        }
        else if (selectedDate > today) {
            dateError.textContent =
                "Date of Birth cannot be in the future";
            isValid = false;
            dobInput.focus();
        }
        else {
            dateError.textContent = "";
            console.log("Date of Birth is valid:", dob);
        }
    }

    if (department === "") {
        deptError.textContent = "Please select your Department";
        departmentInput.focus();
        isValid = false;
    } else {
        deptError.textContent = "";
    }

    if (institute === "") {
        instError.textContent = "Please select your Institute";
        instituteInput.focus();
        isValid = false;
    } else {
        instError.textContent = "";
    }

    if (division === "") {
        divError.textContent = "Please select your Division";
        divInput.focus();
        isValid = false;
    } else {
        divError.textContent = "";
    }

    if (acad === "") {
        acadError.textContent = "Please select your Acadmic-Year";
        acadInput.focus();
        isValid = false;
    } else {
        acadError.textContent = "";
    }

    if (password === "") {
        passError.textContent = "Password is required";
        isValid = false;
    }
    else if (!passwordPattern.test(password)) {
        passError.textContent =
            "Password must have 8 characters, uppercase, lowercase, a number, and a special character";
        isValid = false;
    }
    else {
        passError.textContent = "";
    }

    if (isValid) {
        document.querySelector("form").submit();
    }
});