#!/bin/bash

# Docker Entrypoint - Validate environment and start Apache

echo "=== Payment Hub Backend - Starting ==="
echo "Environment: $APP_ENV"

# Check required environment variables
REQUIRED_VARS=("DB_HOST" "DB_USER" "DB_PASS" "DB_NAME" "JWT_SECRET" "PAWAPAY_API_TOKEN" "PAWAPAY_MERCHANT_ID")

for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        echo "ERROR: Required environment variable $var is not set!"
        exit 1
    fi
done

echo "✓ All required environment variables are set"

# Verify database connection
echo "Testing database connection..."
php -r "
try {
    \$db = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ':' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    echo \"✓ Database connection successful\n\";
} catch (Exception \$e) {
    echo \"✗ Database connection failed: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo "ERROR: Database connection test failed"
    exit 1
fi

# Ensure logs directory exists and is writable
mkdir -p /var/www/html/logs
chmod 755 /var/www/html/logs
chown www-data:www-data /var/www/html/logs

echo "✓ Logs directory permissions set"

# Export environment variables for PHP-FPM
env | grep -E '^(APP_|DB_|JWT_|PAWAPAY_|CORS_|FORCE_|SESSION_|RATE_|LOG_|MAIL_)' > /etc/environment

echo "✓ Environment variables exported"
echo ""
echo "=== Starting Apache ==="

# Start Apache in foreground
exec apache2-foreground
