<?php include '../config_default.php'; ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Forget Password Form</title>
    <!---Custom CSS File--->
    <link rel="stylesheet" href="style.css" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.3/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <script src="utils.js"></script>
    <script src="user-service.js"></script>
  </head>
  <body>
    <div class="container">
      <input type="checkbox" id="check" />
      <div class="login form">
        <header>Reset Your Password</header>
        <form action="#" id="forget-password-form" method="post">
            <p> Enter your user account's verified email address and we will send you a password reset link. </p>
            <br>
          <input type="email" name="email" placeholder="Enter your email" />
          <input type="submit" class="button" value="Send" />
        </form>
        <div
            class="h-captcha"
            data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"
            style="display: none;"
            id="captcha-container"
          ></div>
      </div>
    </div>
    <script>
    $(document).ready(function () {
      // Initialize form validation on the forget password form
      $("#forget-password-form").validate({
        focusCleanup: true,
        errorElement: "em",
        rules: {
          email: {
            required: true,
            email: true, // Ensures the field contains a valid email address
          },
        },
        messages: {
          email: {
            required: "Email is required.",
            email: "Please enter a valid email address.",
          },
        },
        submitHandler: function (form) {
          var formData = $(form).serialize(); 
          var captchaResponse = hcaptcha.getResponse();

          formData += '&h-captcha-response=' + encodeURIComponent(captchaResponse);

          $.post("../api/forgetpassword", formData)
            .done(function (response) {
              // Display success message using toastr
              alert("Success: " + response.message);
            })
            .fail(function (jqXHR) {
              alert("Error: " + jqXHR.responseText);
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