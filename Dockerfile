# Run the tests in docker
FROM php:7.4

RUN apt-get update -y
RUN apt-get install -y openssl zip unzip git iputils-ping libpng-dev libzip-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir -p /home/app
WORKDIR /home/app
ADD . /home/app

RUN composer install

CMD ./vendor/bin/phpunit tests; sleep 10
