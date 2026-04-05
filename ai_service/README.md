# Iconication AI Service

Deze service fungeert als een "Worker" voor de Iconication webapplicatie. Het haalt verzoeken op uit de wachtrij, genereert logische communicatiebomen met behulp van een Large Language Model (LLM), zoekt bijpassende pictogrammen en stuurt het resultaat terug naar de database.

## Functionaliteiten
- **Decision Tree Generatie**: Maakt hiërarchische keuzemenu's op basis van een onderwerp en doelstelling.
- **Pictogram Verrijking**: Zoekt automatisch naar officiële AAC-pictogrammen via de ARASAAC API.
- **Ollama Support**: Draait lokaal op je eigen hardware voor volledige privacy.
- **Few-shot Tuning**: Kan zichzelf trainen (finetunen via Modelfile) op basis van handmatig goedgekeurde voorbeelden in de app.

## Installatie

1. **Vereisten**:
   - Python 3.8+
   - Ollama (indien je lokale modellen wilt gebruiken)

2. **Dependencies installeren**:
   ```bash
   pip install requests fastapi pydantic python-dotenv
   ```

3. **Configuratie**:
   - Kopieer `config.ini` (of gebruik het bestaande bestand).
   - Vul de `php_app_url` in (verwijs naar de `index.php` van je webapp).
   - Vul de `api_key` in die je vindt in het Admin-paneel van de webapp.

## Gebruik

### De Worker starten
De worker blijft draaien en controleert elke 10 seconden op nieuwe opdrachten:
```bash
python worker.py
```

### Het model trainen (Tuning)
Als je in de webapplicatie onderwerpen hebt gemarkeerd als "Trainingsvoorbeelden", kun je het model verbeteren zodat het de stijl van jouw Iconication-app overneemt:
```bash
python worker.py --tune
```
Dit maakt een nieuw model aan in Ollama genaamd `iconication-aac`.

## Architectuur
1. **Polling**: De worker vraagt via `?action=api_pending_jobs` of er werk is.
2. **LLM**: Stuurt een prompt naar Ollama (of Anthropic/OpenAI indien geconfigureerd in `main.py`).
3. **Icon Search**: De `image_hint` uit de AI-respons wordt naar ARASAAC gestuurd voor een `image_url`.
4. **Submit**: Het resultaat wordt via JSON gepost naar `?action=api_submit_result`.

---
*Onderdeel van het Iconication AAC project.*