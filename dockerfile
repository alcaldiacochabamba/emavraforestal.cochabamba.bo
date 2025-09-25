FROM php:8.3-apache

COPY . .
RUN chown -R www-data:www-data /var/www/html \
	&& chmod -R 755 /var/www/html

RUN apt-get update && apt-get install -y \
	default-mysql-client \
	git \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli

EXPOSE 80