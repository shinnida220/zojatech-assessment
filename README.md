<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## About This Application

This application is built with Laravel and was written as an assessment based on the requirements below:

Highlighted below is a snippet of user story, you are required to build an API that meets both the functional and non-functional requirements.

1. As a user:
   i. I should be able to create an account, verify my email via OTP and gain access to the system
   ii. I should own a virtual wallet upon account creation.
   iii. I should be able to withdraw from my wallet.
   iv. I should be logged out after 2 mins of inactivity
   v. I should get notified when an administrative action is taken on my account

As an admin user:
i. I should be able to invite other users individually via email
ii. Bulk upload users email and invite en-mass
iii. I should be able to manage other users account( perform administrative actions such as: Suspend, new Role Assignment).
iv. I should be able to fund users wallet
v. I should be notified on withdrawal attempt/request

Expected language is PHP(Laravel) Please ensure your code is tested and code coverage is at a minimum of 80%.

PS: assume you are building a live application, take all necessary security measures.\*

The application has been buuilt to handle all of the requirements highlighted above.

## Installation

To install this application, please do the following:

-   Clone this git repository.
-   Navigate into the repository folder
-   Install composer dependencies:
    `composer install`
-   Setup your **.env** file. Add your database configuration, mail configuration and your queue configuration
-   Run the database migration.
    `php artisan migrate --seed`
    If you have not created a database before, you will be asked whether to create a new database
    The database is seeded with 1 admin account and 10 regular accounts
    The admin account details is ***admin@zojatech.com/Password@123*** . The other users have a default password of **_password_**
-   Finally run the application using
    `php artisan serve`
-   Mail notifications are queued and ran from the background, endeavor to start the queue monitor using
    `php artisan queue:work`
-   The default timeout is set to 2 mins.

**_The routes are separated into admin and user route. To view the available routes in details, please use the postman collection in this repository <a href="/Zojatech Assessment.postman_collection.json">Zojatech Assessment.postman_collection.json</a>._**

## Issues/Suggestions

If you would like to discuss concerns or feedback, please use the issues tab or create a pull request.
