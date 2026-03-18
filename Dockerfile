# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    intl \
    && docker-php-ext-enable pdo pdo_mysql

# Copy application code
COPY . /var/www/html/

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache DocumentRoot
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public_html|g' /etc/apache2/sites-available/000-default.conf

# Create .htaccess for public_html
RUN cat > /var/www/html/public_html/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
EOF

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Render uses this)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
