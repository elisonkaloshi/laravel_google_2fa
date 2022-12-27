# Laravel Google 2fa
Package for 2fa Google authentication in Laravel Framework

### Installation

`composer require elison/laravel_google_2fa`

### Add the provider in the app.php

`config/app.php`

`\Elison\GoogleAuthenticator\GoogleAuthenticatorProvider::class`

### Add table that stores the secret keys

`php artisan migrate`


### Methods available

`1. GoogleAuthenticator::generateQrCodeUrl($applicationName, $width, $height, $level)` -> This method generates the QR Code which user needs to scan with Google Authenticator, and returns the secret key and qr code url

`2. GoogleAuthenticator::setCredentials($userId, $secretKey)` -> This method stores the secret key for the user that will be used later on, it may be used when the user has finished with code scanning

`3. GoogleAuthenticator::checkIfTwoFaIsActive($userId)` -> This method checks if the 2fa is active for the user, can be used to not display qr code again for an already authenticated user.

`4. GoogleAuthenticator::verifyIsCodeIsValid($userId, $code)` -> This method verifies if the code that the authenticated user enters is valid.
