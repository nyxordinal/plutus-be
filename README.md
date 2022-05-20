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
6. Set your database credential in .env on key `DB\_\*`
7. Generate a pair of public and private RSA key with `pkcs1` padding
8. Place your private RSA key in .env on key `RSA_PRIVATE_KEY`. If you want to make it as a one-line, make sure to use single quote
9. Save your previously generated public RSA key and use it in [plutus-fe](https://github.com/nyxordinal/plutus-fe)
10. If you are in production, do not forget to set APP_ENV in .env to "production" and set APP_DEBUG to "false"

## Run Dev Server

1. Open terminal in your project folder
2. Use below command in the terminal
   `php artisan serve`
3. Access development server in http://localhost:8000

## Deployment Database Setup

1. Create `plutus_be` database in your RDMBS
2. Run migration  
   `php artisan migrate`

## Docker

A. Publish Development Changes

1. Do your changes
2. Build plutus-be docker image  
   `docker build -t nyxordinal/plutus-be:{tag} .`
3. Push docker image to nyxordinal registry  
   `docker push nyxordinal/plutus-be:{tag}`

B. Deploy Docker Image (Production Server)

1. Pull plutus-be docker image  
   `docker pull nyxordinal/plutus-be:{tag}`
2. Create and start plutus-be container

```
docker run -d -p {host-port}:8001 --name plutus-be \
    --env APP_NAME=plutus-be \
    --env APP_ENV=production \
    --env APP_DEBUG=false \
    --env APP_URL=http://localhost:8001 \
    --env APP_TIMEZONE=UTC \
    --env DB_CONNECTION=mysql \
    --env DB_HOST={your-docker-host-ip} \
    --env DB_PORT=3306 \
    --env DB_DATABASE=plutus_be \
    --env DB_USERNAME={your-db-username} \
    --env DB_PASSWORD={your-db-password} \
    --env JWT_SECRET={your-jwt-secret} \
    --env RSA_PRIVATE_KEY={your-private-rsa-key} \
    --env MAIL_MAILER=smtp \
    --env MAIL_HOST=smtp.mailtrap.io \
    --env MAIL_PORT=2525 \
    --env MAIL_USERNAME={your-mail-username} \
    --env MAIL_PASSWORD={your-mail-password} \
    --env MAIL_ENCRYPTION=tls \
    --env MAIL_FROM_ADDRESS=hello@example.com \
    --env MAIL_FROM_NAME="Plutus Nyxordinal" \
    nyxordinal/plutus-be:{tag}
```

4. Access plutus-be in http://localhost:{host-port}/api

> **_NOTE:_** How to check your docker host IP, find out [in this link](https://nickjanetakis.com/blog/docker-tip-35-connect-to-a-database-running-on-your-docker-host)  
> Or you can add `--net="host"` in `docker run` command and then for DB_HOST you can use `"localhost"`.  
> If you use `--net="host"` in `docker run` command, `-p {host-port}:8001` must be removed from `docker run` command

## Developer Team

Developed with passion by [Nyxordinal](https://github.com/nyxordinal)
