FROM php:8.2-apache

# Installation des dépendances système et extensions PHP (PostgreSQL)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Activation du module Apache mod_rewrite (utile pour le routing si besoin)
RUN a2enmod rewrite

# Copie des fichiers de l'application
COPY . /var/www/html/

# Configuration des permissions
RUN chown -R www-data:www-data /var/www/html/

# Port exposé (Render détectera automatiquement le port 80)
EXPOSE 80
