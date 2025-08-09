FROM php:8.2-apache

# Apache設定
RUN a2enmod rewrite
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイルをコピー
WORKDIR /var/www/html
COPY . .

# Composerインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader

# 権限設定
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80