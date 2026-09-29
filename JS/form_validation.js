"use strict";

console.log("hello");

let nameInput = document.getElementById("name");
let emailInput = document.getElementById("email");
let mobileNumber = document.getElementById("mobileNumber");
let male = document.getElementById("gen-male");
let female = document.getElementById("gen-female");
let password = document.getElementById("password");
let confirmPassword = document.getElementById("confirmPassword");

let submit = document.getElementById("btn");

let nameError = document.getElementById("nameError");
let emailError = document.getElementById("emailError");
let mobileError = document.getElementById("mobileError");
let genderError = document.getElementById("genderError");


let namePattern = /^[A-Za-z ]+$/;
let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
let mobilePattern = /^[0-9]{10}$/;

submit.addEventListener("click", function(event) {

    event.preventDefault();

    let name = nameInput.value.trim();
    let email = emailInput.value.trim();
    let mobile = mobileNumber.value.trim();

    if (name === "") {
        nameError.innerHTML = "Name is required";
        nameInput.focus();
    }
    else if (!namePattern.test(name)) {
        nameError.innerHTML = "Name must contain only alphabets";
        nameInput.focus();
    }
    else {
        nameError.innerHTML = "";
    }

    if(email === "") {
        emailError.innerHTML = "Email is required";
        emailInput.focus();
    }
    else if (!emailPattern.test(email)) {
        emailError.innerHTML = "Email is not valid";
        emailInput.focus();
    }
    else {
        emailError.innerHTML = "";
    }

    if(mobile === "") {
        mobileError.innerHTML = "Mobile number is required";
        mobileNumber.focus();
    }
    else if (!mobilePattern.test(mobile)) {
        mobileError.innerHTML = "Mobile number is not valid";
        mobileNumber.focus();
    }
    else {
        mobileError.innerHTML = "";
    }

    if(!male.checked && !female.checked) {
        genderError.innerHTML = "Please select a gender";
    }

    alert("Form submitted successfully!");

    

    
});
