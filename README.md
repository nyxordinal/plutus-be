# Plutus BE

## Description

Backend service of Plutus, a monetary management system from Nyxordinal

## Development Tools

-   [Lumen v8.0.1](https://lumen.laravel.com/)
-   [JSON Web Token Authentication for Laravel & Lumen](https://github.com/tymondesigns/jwt-auth)
-   [Faker](https://github.com/fzaninotto/Faker)
-   [Lumen Generator](https://github.com/flipboxstudio/lumen-generator)
-   [Laravel CORS](https://github.com/fruitcake/laravel-cors)

## Set Up

1. Clone this repo and change directory to project folder  
   `git clone https://github.com/nyxordinal/plutus-be.git && cd /plutus-be`
2. Install dependencies  
   `composer install`
3. Copy .env.example to .env  
   `cp .env.example .env`
4. Generate aplication key  
   `php artisan key:generate`
5. Generate JWT secret key  
   `php artisan jwt:secret`
6. Set your database credential in .env on key DB\_\*
8. If you are in production, do not forget to set APP_ENV in .env to "production" and set APP_DEBUG to "false"

## Run Dev Server

1. Open terminal in your project folder
2. Use below command in the terminal
   `php artisan serve`
3. Access development server in http://localhost:8000

## Developer Team

Developed with passion by [Nyxordinal](https://github.com/nyxordinal)
