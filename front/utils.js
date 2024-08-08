var Utils = {
  password_toggle: function () {
    //this is for the password to be seen or invisible
    //first, look for tags with these ids
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");

    togglePassword.addEventListener("click", function () {
      // Toggle the type attribute
      const type =
        password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);
      // Toggle the eye slash icon
      togglePassword.classList.toggle("bi-eye-slash");
      // Toggle the eye icon
      togglePassword.classList.toggle("bi-eye");
    });
  },
};
