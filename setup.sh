#!/bin/bash

# Kleuren voor output
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}Starten van Iconication setup...${NC}"

# 1. Maak de mappenstructuur aan
echo "Mappenstructuur aanmaken..."
mkdir -p app/Controllers
mkdir -p app/Core
mkdir -p app/Models
mkdir -p public
mkdir -p views
mkdir -p storage

# 2. Verplaats bestanden naar de juiste PSR-4 locaties (indien ze in de root staan)
echo "Bestanden organiseren..."
[ -f "TopicController.php" ] && mv TopicController.php app/Controllers/TopicController.php
[ -f "Database.php" ] && mv Database.php app/Core/Database.php
[ -f "home.php" ] && mv home.php views/home.php
[ -f "node.php" ] && mv node.php views/node.php
[ -f "layout.php" ] && mv layout.php views/layout.php

# Verwijder dubbele bestanden in de root om autoloader errors te voorkomen
echo "Opschonen root map..."
[ -f "index.php" ] && rm index.php
[ -f ".htaccess" ] && rm .htaccess

# 3. Rechten instellen
# We gaan ervan uit dat de webserver draait als www-data (standaard voor Nginx op Ubuntu/Debian)
echo "Instellen van eigenaarsrechten en permissies..."
if [ -d "storage" ]; then
    sudo chown -R www-data:www-data storage
    sudo chmod -R 775 storage
fi

echo -e "${GREEN}Setup voltooid!${NC}"
echo ""
echo "BELANGRIJK: Nginx configuratie"
echo "Omdat je in een submap draait (/iconication/public/), moet Nginx weten hoe hij de routes moet doorsturen."
echo "Voeg het volgende toe aan je Nginx server block (bijv. in /etc/nginx/sites-available/default):"
echo ""
echo "location /iconication/public/ {"
echo "    index index.php;"
echo "    try_files \$uri \$uri/ /iconication/public/index.php?\$query_string;"
echo "}"
echo ""