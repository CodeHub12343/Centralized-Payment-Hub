# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    cron \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    gd \
    pdo \
    pdo_mysql \
    intl \
    mbstring \
    && docker-php-ext-enable pdo pdo_mysql mbstring

# Copy application code
COPY . /var/www/html/

# Copy and make entrypoint script executable
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Remove .env from Docker (use environment variables instead)
RUN rm -f /var/www/html/.env

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot to serve from public_html
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public_html|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|<Directory /var/www/>|<Directory /var/www/html/public_html>|g' /etc/apache2/sites-available/000-default.conf

# Add Apache configuration for proper rewriting
RUN cat > /etc/apache2/sites-available/api.conf << 'EOF'
<VirtualHost *:80>
    DocumentRoot /var/www/html/public_html
    <Directory /var/www/html/public_html>
        Options -MultiViews
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
EOF

RUN a2ensite api.conf
RUN a2dissite 000-default.conf

# Create logs directory FIRST before changing permissions
RUN mkdir -p /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/logs

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

# Use entrypoint script to validate environment and start Apache
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
