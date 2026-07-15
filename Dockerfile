FROM php:8.2-apache

# Aktifkan modul mod_rewrite untuk Apache (berguna untuk routing PHP)
RUN a2enmod rewrite

# Salin semua file dari repositori kamu ke dalam folder web server
COPY . /var/www/html/

# Atur hak akses agar server bisa membaca file kamu
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
