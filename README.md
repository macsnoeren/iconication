# Iconication - AAC Communicatie Applicatie

Iconication is een moderne webapplicatie voor Augmentatieve en Alternatieve Communicatie (AAC). Het is ontworpen om mensen met communicatieve beperkingen te helpen hun behoeften en gevoelens te uiten via een intuïtieve beslisboom (decision tree) met pictogrammen.

## Kernfuncties

- **Eenvoudige Navigatie**: Gebruikers kiezen telkens uit twee grote, duidelijke opties met pictogrammen.
- **Decision Trees**: Onderwerpen (zoals eten, drinken, gevoelens) zijn gestructureerd in een logische boomstructuur.
- **Admin Dashboard**: Volledig beheer van onderwerpen, nodes en opties.
- **Afbeeldingenbibliotheek**: Zoek en hergebruik bestaande pictogrammen in het beheersysteem.
- **AI-Ondersteuning**: Genereer automatisch nieuwe communicatie-onderwerpen met behulp van AI (via de meegeleverde Python AI Service).
- **Geen Dependencies**: De PHP-applicatie is "vanilla" geschreven (zonder Composer) en werkt op vrijwel elke webserver met PHP 8.1+.

## Projectstructuur

- `/app`: Bevat de kernlogica (Controllers, Models, Database en Routing).
- `/public`: Het publieke startpunt (`index.php`) en assets.
- `/views`: Alle HTML-templates en layouts.
- `/storage`: Bevat de SQLite database (`database.sqlite`).
- `/ai_service`: Een optionele Python-service voor AI-generatie van content.

## Installatie

1. **Uploaden**: Kopieer alle bestanden naar je webserver of projectmap.
2. **Rechten & Mappen**: Voer het setup-script uit om de mappenstructuur te controleren en de juiste schrijfrechten op de `storage/` map in te stellen:
   ```bash
   bash setup.sh
   ```
3. **Eerste Setup**:
   - Navigeer in je browser naar de `public/` map van het project.
   - De applicatie detecteert automatisch dat er nog geen gebruikers zijn en leidt je naar de **Setup-pagina**.
   - Maak hier het eerste Administrator-account aan.
4. **Inloggen**: Gebruik je nieuwe account om toegang te krijgen tot het Admin Dashboard via de "Admin" knop in de navigatiebalk.

## Nginx Configuratie

De applicatie werkt via query-parameters (`index.php?action=...`), waardoor complexe URL-rewriting niet strikt noodzakelijk is. Het wordt echter aangeraden om de `root` van je Nginx-server naar de `public` map te laten wijzen:

```nginx
location /iconication/public/ {
    index index.php;
    try_files $uri $uri/ /iconication/public/index.php?$query_string;
}
```

## AI Service (Optioneel)

De AI-service stelt je in staat om razendsnel nieuwe onderwerpen te genereren op basis van een korte omschrijving of doelstelling.

1. Ga naar de map `ai_service/`.
2. Configureer `config.ini` met de API-key die je vindt in je Admin Dashboard.
3. Installeer de Python-vereisten: `pip install requests`.
4. Start de worker: `python worker.py`.

Zie de ai_service/README.md voor meer details over AI-generatie en het trainen van het model.

## Gebruikersinterface

De interface is specifiek ontworpen voor touch-screens (zoals tablets) met grote touch-targets, visuele feedback bij interactie en een minimalistisch ontwerp om afleiding voor de gebruiker te voorkomen.

## Wetenschappelijke Onderbouwing & Bronnen

De ontwikkeling van Iconication is gebaseerd op erkende principes binnen de Augmentatieve en Alternatieve Communicatie (AAC):

- **Beukelman, D. R., & Light, J. C. (2020). *Augmentative & Alternative Communication: Supporting Children and Adults with Complex Communication Needs*.**  
  Dit standaardwerk benadrukt het belang van visuele ondersteuning en de selectie van een passend vocabulaire om de autonomie van gebruikers met communicatieve beperkingen te vergroten.

- **Thistle, J. J., & Wilkinson, K. M. (2013). *Working memory and AAC: A comparison of two different layouts*.**  
  Dit onderzoek toont aan dat eenvoudige lay-outs en een beperkt aantal keuzes per scherm de cognitieve belasting verminderen, wat essentieel is voor gebruikers met beperkte werkgeheugencapaciteit.

- **Light, J., & McNaughton, D. (2014). *Communicative Competence for Individuals who require AAC*.**  
  Dit artikel beschrijft de operationele en taalkundige competenties die nodig zijn voor effectief AAC-gebruik, waarbij eenvoud in navigatie (zoals beslisbomen) de leercurve voor nieuwe gebruikers verkort.

---
*Ontwikkeld voor toegankelijkheid en ondersteunde communicatie.*