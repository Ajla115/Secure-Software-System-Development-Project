var UserService = {
  formToJson: function (form) {
    var array = $(form).serializeArray(); // jQuery method
    var json = {}; // JavaScript object

    $.each(array, function () {
      json[this.name] = this.value || "";
    });

    return json;
  },

  validateForm: function () {
    var self = this; // This is a reference to the current object of this validateMethod, context of this changes with different callbacks

    // Define the custom alphanumeric method before setting up validation rules
    $.validator.addMethod(
      "alphanumeric",
      function (value, element) {
        return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
      },
      "Letters and numbers only please"
    );

    $("#register-form").validate({
      focusCleanup: true, // Element with error, hides error when it gets focus
      errorElement: "em", // Type of HTML element that should wrap the error message

      rules: {
        fullname: "required",
        username: {
          required: true,
          minlength: 4,
          alphanumeric: true,
        },
        password: {
          required: true,
          minlength: 8,
        },
        email: {
          required: true,
          email: true,
        },
        phone: "required",
      },
      messages: {
        fullname: "Fullname is required.",
        username: {
          required: "Username is required.",
          minlength: "Username should be longer than 3 characters.",
          alphanumeric: "Only letters and numbers can be used.",
        },
        email: "Please enter a valid email format.",
        phone: "Phone is required.",
      },

      highlight: function (element, errorClass) {
        $(element).fadeOut(function () {
          $(element).fadeIn();
        });
      },

      errorContainer: "#messageBox1",
      errorLabelContainer: "#messageBox1 ul",
      wrapper: "li",

      submitHandler: function (form) {
        const data = self.formToJson(form); // 'self' is referring to this JS object

        $.post("../api/register", data)
          .done(function (response) {
            const token = response.token;
            // Storing the JWT token in localStorage
            localStorage.setItem("user_token", token);
            //toastr.success("User added to the database");
            // Displaying customer information
            alert(
              "Details: " +
                response.message
            );
            // alert()
            // $("#customerName").text(customer.status);
            // $("#customerDetails").text("Details: " + JSON.stringify(customer));
            form.reset();

            setTimeout(function () {
              window.location.replace("login.php");
            }, 1000);
          })
          .fail(function (jqXHR, textStatus, errorThrown) {
            alert("Error: " + jqXHR.responseText);
            console.log("Error details:", textStatus, errorThrown);
            console.log(jqXHR.responseText);
            //toastr.error(jqXHR.responseText || "An error occurred during registration");
          });
      },

      invalidHandler: function (event, validator) {
        var errors = validator.numberOfInvalids();
        toastr.error("Error");
        if (errors) {
          var message =
            errors == 1
              ? "You missed 1 field."
              : "You missed " + errors + " fields.";
          $("div.error span").html(message);
          $("div.error").show();
        } else {
          $("div.error").hide();
        }
      },
    });
  },



  init: function () {
    this.validateForm();
  },

  checkToken: function () {
    var token = localStorage.getItem("user_token");
    if (!token) {
      window.location.replace("index.html");
    }
  },
};
