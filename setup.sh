#!/bin/bash

# Kleuren voor output
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}Starten van Iconication setup...${NC}"

# 1. Maak de storage map aan als deze niet bestaat
if [ ! -d "storage" ]; then
    echo "Aanmaken van storage map..."
    mkdir -p storage
fi

# 2. Rechten instellen
# We gaan ervan uit dat de webserver draait als www-data (standaard voor Nginx op Ubuntu/Debian)
echo "Instellen van eigenaarsrechten en permissies..."
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

echo -e "${GREEN}Setup voltooid!${NC}"
echo ""
echo "BELANGRIJK: Nginx configuratie"
echo "Nginx negeert .htaccess bestanden. Voeg de volgende regel toe aan je server block:"
echo ""
echo "location / {"
echo "    try_files \$uri \$uri/ /index.php?\$query_string;"
echo "}"
echo ""