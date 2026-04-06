FROM php:7.4-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
RUN apt-get update && apt-get install -y locales &&     echo "en_US.UTF-8 UTF-8" > /etc/locale.gen &&     locale-gen
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

EXPOSE 80
