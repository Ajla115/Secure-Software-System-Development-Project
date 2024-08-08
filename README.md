# Secure-Software-System-Development-Project
This project contain implementation of different security protocols in the backend to ensure data integrity and disable any attemp of frauds, false entries, and etc.

- To see all functionalities implemented in this project, I highly encourage you to check out PDF file SSSD 2024 - Project Requirements. The file is located on the folder's root level.

- For the project to work properly, you will need to setup and connect to the database properly. An empty database schema can also be found at the root level under the name SSSD_Project.sql

- On the root level, you will also find file named config_example.php. You MUST rename this to config_default.php and put it into the gitignore file, if you decide to store your configuration variables here. Otherwise, you would have to change all access points to configuration variables in different parts of the code.

- Additionally, you will have to run a command to install composer on your device.

- Also, for the email function to work properly. Go to services > UserService.php > send_email function. Inside the send_email function change the setFrom to be an actual existing email.
