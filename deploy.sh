#!/bin/bash

# Configuration
DEPLOY_PATH="/var/www/html/restaurant-app"
BACKUP_PATH="/var/www/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup
echo "Creating backup..."
mkdir -p "$BACKUP_PATH"
if [ -d "$DEPLOY_PATH" ]; then
    tar -czf "$BACKUP_PATH/backup_$TIMESTAMP.tar.gz" -C "$DEPLOY_PATH" .
fi

# Build React app
echo "Building React application..."
npm run build

# Deploy backend
echo "Deploying backend..."
rsync -av --exclude 'node_modules' --exclude '.git' \
    --exclude '.env' --exclude 'deploy.sh' \
    ./ "$DEPLOY_PATH/"

# Copy new .env file
echo "Updating configuration..."
cp .env "$DEPLOY_PATH/.env"

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;

# Clear cache
echo "Clearing cache..."
php "$DEPLOY_PATH/api/cache/clear.php"

echo "Deployment completed successfully!" 