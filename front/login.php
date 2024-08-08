<?php include '../config_default.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Login Form</title>
  <!---Custom CSS File--->
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
  <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.3/jquery.validate.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
  <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
  <script src="utils.js"></script>
  <script src="user-service.js"></script>
</head>

<body>
  <div id="qrCodeLinkContainer"></div>
  <div class="container">
    <input type="checkbox" id="check" />

    <div class="login form">
      <header>Login</header>
      <form action="#" id="login-form" method="post">
        <input type="text" name="username" placeholder="Enter your email" />
        <div class="password-container">
          <input type="password" name="password" id="password" placeholder="Enter your password" />
          <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
        </div>
        <a href="forgetPassword.php">Forgot password?</a>
        <input type="submit" class="button" value="Login" />
        <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>" style="display: none;"
          id="captcha-container"></div>
        <br />
      </form>
      <div class="signup">
        <span class="signup">Don't have an account?
          <a href="index.html">Signup</a>
        </span>
      </div>
    </div>
  </div>
  <script>
    $(document).ready(function () {
      Utils.password_toggle();




      $("#login-form").validate({
        focusCleanup: true,
        errorElement: "em",
        rules: {
          username: {
            required: true,
            minlength: 4, 
          },
          password: {
            required: true,
            minlength: 8, 
          },
        },
        messages: {
          username: {
            required: "Username is required.",
            minlength: "Username must be at least 4 characters.",
          },
          password: {
            required: "Password is required.",
            minlength: "Password must be at least 8 characters.",
          },
        },
        submitHandler: function (form) {

          var formData = $(form).serialize(); // Serialize the form data.
          var captchaResponse = hcaptcha.getResponse();

          formData += '&h-captcha-response=' + encodeURIComponent(captchaResponse);

          $.post("../api/login", formData)
            .done(function (response) {
              localStorage.setItem("user_token", response.token);
              alert("Details: \n" + response.message);

              if (response.link) {
                //link will be sent only first time, every other time, there will be no link
                var qrLinkContainer = document.getElementById(
                  "qrCodeLinkContainer"
                );
                qrLinkContainer.innerHTML = `<p>Show QR Code:</p><a href="${response.link}" target="_blank">${response.link}</a>`;
                qrLinkContainer.innerHTML +=
                  '<button id="nextButton" class="next-button">Next</button>';

                // Add event listener to Next button
                document
                  .getElementById("nextButton")
                  .addEventListener("click", function () {
                    window.location.href = "choose2FAMethod.html";
                  });
              } else {
                window.location.href = "choose2FAMethod.html";
              }
            })
            .fail(function (jqXHR) {
              alert("Login failed: " + jqXHR.responseText);
              var response = JSON.parse(jqXHR.responseText);
              if (response.captchaRequired) {
                $("#captcha-container").show();
              }
            });
        },

        highlight: function (element) {
          $(element).fadeOut(function () {
            $(element).fadeIn();
          });
        },
      });
    });
  </script>
</body>

</html>