FROM serversideup/php:8.4-fpm

USER root
RUN install-php-extensions bcmath
RUN echo 'open_basedir=' > /usr/local/etc/php/conf.d/zz-openbasedir.ini

USER www-data