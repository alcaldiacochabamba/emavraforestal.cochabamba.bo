FROM php:8.3-apache

# Install MySQL client, server, and other dependencies
RUN apt-get update && \
	apt-get install -y \
	default-mysql-client \
	default-mysql-server \
	git \
	&& apt-get clean \
	&& rm -rf /var/lib/apt/lists/*

# Install mysqli PHP extension for MySQL support
RUN docker-php-ext-install mysqli