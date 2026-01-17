#!/bin/bash
set -e

echo "========================================="
echo "Starting Application Setup"
echo "========================================="

# Tạo .env file từ Render env variables
echo "Creating .env file..."
cat > /var/www/html/.env <<EOF
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------
CI_ENVIRONMENT = production
JWT_SECRET_KEY = ${JWT_SECRET_KEY}
#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = ${APP_BASE_URL}

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = ${DB_HOST}
database.default.database = ${DB_NAME}
database.default.username = ${DB_USER}
database.default.password = ${DB_PASSWORD}
database.default.DBDriver = MySQLi
database.default.port = ${DB_PORT}
database.default.DBDebug = false

#--------------------------------------------------------------------
# CLOUDINARY
#--------------------------------------------------------------------
CLOUDINARY_NAME = ${CLOUDINARY_NAME}
CLOUDINARY_KEY = ${CLOUDINARY_KEY}
CLOUDINARY_SECRET = ${CLOUDINARY_SECRET}
EOF

echo ""
echo "✅ .env file created:"
echo "---"
cat /var/www/html/.env | grep -v -i password | grep -v -i secret
echo "---"
echo ""

# Set permissions
chown www-data:www-data /var/www/html/.env
chmod 644 /var/www/html/.env

# Clear cache
echo "Clearing cache..."
rm -rf /var/www/html/writable/cache/* 2>/dev/null || true
rm -rf /var/www/html/writable/session/* 2>/dev/null || true
rm -rf /var/www/html/writable/debugbar/* 2>/dev/null || true

echo "========================================="
echo "Starting Apache..."
echo "========================================="

# Start Apache (exec = replace process)
exec apache2-foreground