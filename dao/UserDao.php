<?php
require_once "BaseDao.php";


class UserDao extends BaseDao
{

  public function __construct()
  {
    parent::__construct("users");
  }

  public function checkExistenceForEmail($username)
  {
    try {
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
      $stmt->bindParam(':email', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      return $user; // I am returning the whole object here
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }



  public function checkExistenceForUsername($username)
  {
    try {
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username");
      $stmt->bindParam(':username', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      return $user;
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }
  public function getUserByEmail($username)
  {
    //return $this->query("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    try {
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
      $stmt->bindParam(':email', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {
        return $user['password']; // Return just the password
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }

  public function getUserByUsername($username)
  {
    try {
      $stmt = $this->conn->prepare("SELECT password FROM users WHERE username = :username");
      $stmt->bindParam(':username', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {
        return $user['password']; // Return just the password
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }

  public function getLoginCountByUsername($username)
  {
    try {
      $stmt = $this->conn->prepare("SELECT password FROM users WHERE username = :username");
      $stmt->bindParam(':username', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {
        return $user['login_count']; // Return just login_count field
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }

  public function getLoginCountByEmail($username)
  {
    try {
      $stmt = $this->conn->prepare("SELECT password FROM users WHERE email = :email");
      $stmt->bindParam(':username', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {
        return $user['login_count']; // Return just login_count field
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }

  public function updateLoginCount($email)
  {
    try {
      $stmt = $this->conn->prepare("UPDATE users SET login_count = login_count + 1 WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      //the return here is not necessary
      //return array ("status" => 200, "message" => "Successfull update.");
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }


  public function updatePassword($password, $email)
  {

    try {
      $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE email = :email");
      $stmt->bindParam(':password', $password);
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        return array("status" => 200, "message" => "Password has been successfully updated.");
      } else {
        return array("status" => 500, "message" => "Password update has failed.");
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return array("status" => 400, "message" => "Backend error");
    }

  }

  public function verifyuserviaemail($register_token)
  {
    try {
      $stmt = $this->conn->prepare("UPDATE users SET verified = 'verified' WHERE register_token = :register_token");
      $stmt->bindParam(':register_token', $register_token);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        return array("status" => 200, "message" => "User has been successfully verified.");
      } else {
        return array("status" => 500, "message" => "Verification failed. Invalid or already verified token.");
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return array("status" => 400, "message" => "Backend error");
    }

  }


  public function checkaccountverification($email)
  {
    try {
      $stmt = $this->conn->prepare("SELECT verified FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($user) {

        if ($user['verified'] === 'verified') {
          return array("status" => 200, "message" => "User is verified.");

        } else {
          return array("status" => 500, "message" => "User is  not verified.");

        }
      } else {
        return array("status" => 404, "message" => "User is not found.");

      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return array("status" => 401, "message" => "Backend error.");
    }
  }


  public function saveExpirationTokenAndCount($expirationJWT, $email)
  {
    try {
      $stmt = $this->conn->prepare("
          UPDATE users
          SET activation_token = :activation_token, activation_token_count = 0
          WHERE email = :email
      ");
      $stmt->bindParam(':activation_token', $expirationJWT);
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      //return array("status" => 200, "message" => "Activation token saved successfully.");
    } catch (PDOException $e) {
      error_log($e->getMessage());
      //return array("status" => 500, "message" => "An error occurred while saving the activation token.");
    }
  }

  public function checkTokenExistence($activationToken)
  {
    try {
      $stmt = $this->conn->prepare("
              SELECT COUNT(*) as count
              FROM users
              WHERE activation_token = :activation_token
          ");

      $stmt->bindParam(':activation_token', $activationToken);

      $stmt->execute();

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result['count'] > 0) {
        return array("status" => 200, "message" => "Token exists.");
      } else {
        return array("status" => 404, "message" => "Token does not exist.");
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return array("status" => 500, "message" => "An error occurred while checking the token.");
    }
  }


  public function checkTokenCount($activationToken)
  {
    try {
      $stmt = $this->conn->prepare("
            SELECT activation_token_count
            FROM users
            WHERE activation_token = :activation_token
        ");

      $stmt->bindParam(':activation_token', $activationToken);

      $stmt->execute();

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result) {
        if ($result['activation_token_count'] == 1) {
          return array("status" => 500, "message" => "Token was already used.");
        }
      } else {
        return array("status" => 404, "message" => "Token does not exist.");
      }
    } catch (PDOException $e) {

      error_log($e->getMessage());
      return array("status" => 401, "message" => "Backend error.");
    }


  }

  public function updateTokenCount($activationToken)
  {

    try {
      $stmt = $this->conn->prepare("UPDATE users SET activation_token_count = 1 WHERE activation_token = :activation_token");
      $stmt->bindParam(':activation_token', $activationToken);
      $stmt->execute();
      if ($stmt->rowCount() > 0) {
        return array("status" => 200, "message" => "Count has been successfully updated.");
      } else {
        return array("status" => 500, "message" => "Count update has failed.");
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return array("status" => 500, "message" => "Backend error.");
    }

  }

  public function updateSecretValue($secret, $username)
  {
    try {
      $stmt = $this->conn->prepare("UPDATE users SET secret = :secret WHERE username = :username");
      $stmt->bindParam(':secret', $secret);
      $stmt->bindParam(':username', $username);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      //the return here is not necessary
      //return array ("status" => 200, "message" => "Successfull update.");
    } catch (PDOException $e) {
      return $e->getMessage();
    }
  }



  public function saveRecoveryCodes($recoveryCodesList, $email)
  {
    try {
      // Prepare the SQL update statement
      $stmt = $this->conn->prepare("UPDATE users SET recovery_codes = :recovery_codes WHERE email = :email");

      // Bind the parameters
      $stmt->bindParam(':recovery_codes', $recoveryCodesList);
      $stmt->bindParam(':email', $email);

      // Execute the statement
      $stmt->execute();


      return array(
        "status" => 200,
        "message" => "Successfully updated recovery codes.",
        "recoveryCodes" => $recoveryCodesList
      );
    } catch (PDOException $e) {

      error_log($e->getMessage());
      return array("status" => 500, "message" => "Backend error.");
    }
  }


  public function checkRecoveryCodeExistence($recovery_code, $email)
  {
    try {
      // Find the user by email
      $stmt = $this->conn->prepare("SELECT recovery_codes FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user) {
        $recoveryCodesString = $user['recovery_codes'];

        //ako sam obrisala recovery codes, dosad koristeci ih
        if (empty($recoveryCodesString)) {
          return array("status" => 404, "message" => "Recovery codes list is empty. Generate new recovery codes.");
        }

        //posto je bila comma seperated list, sad cu prvo pretvoriti u array
        //radi lakseg rada sa built in funkcijama
        $recoveryCodesArray = explode(',', $recoveryCodesString);


        if (in_array($recovery_code, $recoveryCodesArray)) {

          //ako taj kod postoji u arrayu, napravit cu novi array koji nema taj jedan kod
          //i opet ga pretvoriti u listu
          $newRecoveryCodesArray = array_diff($recoveryCodesArray, array($recovery_code));

          // Convert the array back to a comma-separated string
          $newRecoveryCodesString = implode(',', $newRecoveryCodesArray);

          //i onda to spasiti na mjesto recovery_codes
          $updateStmt = $this->conn->prepare("UPDATE users SET recovery_codes = :recovery_codes WHERE email = :email");
          $updateStmt->bindParam(':recovery_codes', $newRecoveryCodesString);
          $updateStmt->bindParam(':email', $email);
          $updateStmt->execute();

          return array("status" => 200, "message" => "Recovery code successfully used and removed.");
        } else {
          return array("status" => 404, "message" => "Recovery code is not valid.");
        }
      } else {
        return array("status" => 404, "message" => "User not found.");
      }
    } catch (PDOException $e) {
      // Log the error message
      error_log($e->getMessage());
      return array("status" => 500, "message" => "Backend error.");
    }
  }


  public function trackFailedAttempts($ipAddress)
  {
    try {
      $stmt = $this->conn->prepare("SELECT * FROM logs WHERE ip_address = :ip_address");
      $stmt->bindParam(':ip_address', $ipAddress);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result) {
        $failedAttempts = $result['failed_login_count'] + 1;
        if ($failedAttempts >= 3) {
          $captchaRequired = 1;
        } else {
          $captchaRequired = 0;
        }
        $stmt = $this->conn->prepare("UPDATE logs SET failed_login_count = :failed_login_count, captcha_required = :captcha_required WHERE ip_address = :ip_address");
        $stmt->bindParam(':failed_login_count', $failedAttempts);
        $stmt->bindParam(':captcha_required', $captchaRequired);
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->execute();
      } else {
        $stmt = $this->conn->prepare("INSERT INTO logs (ip_address, failed_login_count, captcha_required) VALUES (:ip_address, 1, 0)");
        $stmt->bindParam(':ip_address', $ipAddress);
        $stmt->execute();
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
    }
  }
  public function resetFailedAttempts($ipAddress)
  {
    try {
      $stmt = $this->conn->prepare("UPDATE logs SET failed_login_count = 0, captcha_required = 0 WHERE ip_address = :ip_address");
      $stmt->bindParam(':ip_address', $ipAddress);
      $stmt->execute();
    } catch (PDOException $e) {
      error_log($e->getMessage());
    }
  }

  public function numberOfFailedAttempts($ipAddress)
  {
    try {
      $stmt = $this->conn->prepare("SELECT failed_login_count FROM logs WHERE ip_address = :ip_address");
      $stmt->bindParam(':ip_address', $ipAddress);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result && $result['failed_login_count'] >= 3;
    } catch (PDOException $e) {
      error_log($e->getMessage());
    }
  }


  public function getForgetPasswordCount($email)
  {
    try {
      $stmt = $this->conn->prepare("SELECT forget_password_count FROM logs WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result && $result['forget_password_count'] >= 2;
    } catch (PDOException $e) {
      error_log($e->getMessage());
    }
  }

  public function resetForgetPasswordCount($email)
  { {
      try {
        $stmt = $this->conn->prepare("UPDATE logs SET forget_password_count = 0, captcha_required = 0 WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
      } catch (PDOException $e) {
        error_log($e->getMessage());
      }
    }
  }

  public function incrementForgetPasswordCount($email)
  {
    try {
      $stmt = $this->conn->prepare("SELECT forget_password_count, timestamps FROM logs WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      $currentTime = time();
      $timestamps = [];

      if ($result) {
        // If email exists, I will increment the forget_password_count and update the timestamps
        $forgetPasswordCount = $result['forget_password_count'] + 1;
        if ($result['timestamps']) {
          $timestamps = json_decode($result['timestamps'], true);
        }
        $timestamps[] = $currentTime;
        $timestamps = json_encode($timestamps);
        $stmt = $this->conn->prepare("UPDATE logs SET forget_password_count = :forget_password_count, timestamps = :timestamps WHERE email = :email");
        $stmt->bindParam(':forget_password_count', $forgetPasswordCount);
        $stmt->bindParam(':timestamps', $timestamps);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
      } else {
        // If email does not exist, I will insert a new record with forget_password_count set to 1 and current timestamp
        $timestamps[] = $currentTime;
        $timestamps = json_encode($timestamps);
        $stmt = $this->conn->prepare("INSERT INTO logs (email, forget_password_count, timestamps) VALUES (:email, 1, :timestamps)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':timestamps', $timestamps);
        $stmt->execute();
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
    }
  }


  public function checkTimeForRequests($email)
  {
    try {
      $stmt = $this->conn->prepare("SELECT timestamps FROM logs WHERE email = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result && $result['timestamps']) {
        $timestamps = json_decode($result['timestamps'], true);
        $currentTime = time();
        $validTimestamps = [];

        foreach ($timestamps as $timestamp) {
          $timeDifference = ($currentTime - $timestamp) / 60; //here, I calculate difference in minutes
          if ($timeDifference <= 10) {
            $validTimestamps[] = $timestamp;
          }
        }

        // Check if two requests have been made in the last 10 minutes
        if (count($validTimestamps) >= 2) {
          return true;
        }
      }

      return false;
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return false;
    }
  }

  public function getName($email)
  {
    try {
      $stmt = $this->conn->prepare("SELECT fullname FROM users WHERE email = :email");
      $stmt->bindParam(':email', $email, PDO::PARAM_STR);
      $stmt->execute();

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result) {
        return $result['fullname'];
      } else {
        return null;
      }
    } catch (PDOException $e) {
      error_log($e->getMessage());
      return null;
    }
  }




}









































