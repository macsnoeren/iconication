#!/usr/bin/env python3
"""
Iconication AI Worker
─────────────────────
Haalt pending jobs op via de PHP API, genereert een decision tree met een LLM,
en stuurt het resultaat terug. Stopt daarna — geen daemon, geen server.

Gebruik:
    python worker.py

Via cron (elke 5 minuten):
    */5 * * * * cd /pad/naar/ai_service && python worker.py >> worker.log 2>&1
"""

import os
import sys
import json
import re
import requests
from dotenv import load_dotenv

load_dotenv()

PHP_APP_URL  = os.environ.get("PHP_APP_URL", "").rstrip("/")
API_KEY      = os.environ.get("ICONICATION_API_KEY", "")
AI_PROVIDER  = os.environ.get("AI_PROVIDER", "anthropic").lower()

PROMPT_TEMPLATE = """Je bent een specialist in augmentatieve en alternatieve communicatie (AAC).
Maak een decision tree voor communicatie met een gebruiker over het onderwerp: "{topic}"
{goal_line}
Regels:
- PRECIES 2 keuzes per node (option_a en option_b)
- Maximaal 4 niveaus diep
- Labels: simpele, concrete taal, maximaal 5 woorden
- Alleen visueel voorstelbare concepten (geschikt voor pictogrammen/foto's)
- Geen abstracte begrippen
- image_hint: 1-2 Nederlandse woorden die het beste pictogram beschrijven
- De boom moet logisch zijn voor communicatie met een patiënt/gebruiker

Geef ALLEEN valide JSON terug, geen extra uitleg, alleen JSON:
{{
  "topic": "...",
  "nodes": [
    {{
      "id": 1,
      "option_a": {{"label": "...", "image_hint": "...", "next_node_id": 2}},
      "option_b": {{"label": "...", "image_hint": "...", "next_node_id": 3}}
    }},
    {{
      "id": 2,
      "option_a": {{"label": "...", "image_hint": "...", "next_node_id": null}},
      "option_b": {{"label": "...", "image_hint": "...", "next_node_id": null}}
    }},
    {{
      "id": 3,
      "option_a": {{"label": "...", "image_hint": "...", "next_node_id": null}},
      "option_b": {{"label": "...", "image_hint": "...", "next_node_id": null}}
    }}
  ]
}}

Node 1 is altijd de root node. next_node_id is null als het een eindpunt is.
Gebruik opeenvolgende integer IDs beginnend bij 1."""


def _check_config() -> None:
    if not PHP_APP_URL:
        sys.exit("FOUT: PHP_APP_URL is niet ingesteld in .env")
    if not API_KEY:
        sys.exit("FOUT: ICONICATION_API_KEY is niet ingesteld in .env")


def _headers() -> dict:
    return {
        "Authorization": f"Bearer {API_KEY}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }


def fetch_pending_jobs() -> list:
    url = f"{PHP_APP_URL}?action=api_pending_jobs"
    try:
        resp = requests.get(url, headers=_headers(), timeout=10)
        resp.raise_for_status()
        return resp.json()
    except requests.RequestException as e:
        sys.exit(f"FOUT: Kan PHP app niet bereiken ({url}): {e}")


def submit_result(job_id: int, result: dict) -> None:
    url = f"{PHP_APP_URL}?action=api_submit_result"
    requests.post(url, headers=_headers(), json={"job_id": job_id, "result": result}, timeout=10)


def submit_error(job_id: int, error: str) -> None:
    url = f"{PHP_APP_URL}?action=api_submit_result"
    requests.post(url, headers=_headers(), json={"job_id": job_id, "error": error}, timeout=10)


def build_prompt(topic: str, goal: str) -> str:
    goal_line = f"Doel: {goal}" if goal.strip() else ""
    return PROMPT_TEMPLATE.format(topic=topic, goal_line=goal_line)


def generate_with_anthropic(topic: str, goal: str) -> dict:
    try:
        import anthropic
    except ImportError:
        raise RuntimeError("anthropic pakket niet gevonden. Voer: pip install anthropic")

    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        raise RuntimeError("ANTHROPIC_API_KEY is niet ingesteld in .env")

    client = anthropic.Anthropic(api_key=api_key)
    message = client.messages.create(
        model=os.environ.get("ANTHROPIC_MODEL", "claude-haiku-4-5-20251001"),
        max_tokens=2048,
        messages=[{"role": "user", "content": build_prompt(topic, goal)}],
    )
    return parse_and_validate(message.content[0].text)


def generate_with_openai(topic: str, goal: str) -> dict:
    try:
        from openai import OpenAI
    except ImportError:
        raise RuntimeError("openai pakket niet gevonden. Voer: pip install openai")

    api_key = os.environ.get("OPENAI_API_KEY")
    if not api_key:
        raise RuntimeError("OPENAI_API_KEY is niet ingesteld in .env")

    client = OpenAI(api_key=api_key)
    response = client.chat.completions.create(
        model=os.environ.get("OPENAI_MODEL", "gpt-4o-mini"),
        messages=[{"role": "user", "content": build_prompt(topic, goal)}],
        response_format={"type": "json_object"},
    )
    return parse_and_validate(response.choices[0].message.content)


def parse_and_validate(content: str) -> dict:
    match = re.search(r'\{[\s\S]*\}', content)
    if not match:
        raise ValueError("Geen valide JSON in AI-respons")

    data = json.loads(match.group())

    if "topic" not in data or "nodes" not in data:
        raise ValueError("Ongeldige structuur: 'topic' of 'nodes' ontbreekt")
    if not isinstance(data["nodes"], list) or not data["nodes"]:
        raise ValueError("Decision tree bevat geen nodes")

    node_ids = {n["id"] for n in data["nodes"]}
    for node in data["nodes"]:
        for opt_key in ("option_a", "option_b"):
            opt = node[opt_key]
            next_id = opt.get("next_node_id")
            if next_id is not None and next_id not in node_ids:
                raise ValueError(
                    f"Node {node['id']} verwijst naar onbekende next_node_id {next_id}"
                )
    return data


def process_job(job: dict) -> None:
    job_id = job["id"]
    topic  = job["topic"]
    goal   = job.get("goal", "")

    print(f"  → Verwerken job #{job_id}: '{topic}'")
    try:
        if AI_PROVIDER == "openai":
            result = generate_with_openai(topic, goal)
        else:
            result = generate_with_anthropic(topic, goal)

        submit_result(job_id, result)
        print(f"  ✓ Job #{job_id} succesvol verwerkt ({len(result['nodes'])} nodes)")
    except Exception as e:
        error_msg = str(e)
        submit_error(job_id, error_msg)
        print(f"  ✗ Job #{job_id} mislukt: {error_msg}")


def main() -> None:
    _check_config()

    print(f"Iconication Worker — {PHP_APP_URL}")
    jobs = fetch_pending_jobs()

    if not jobs:
        print("Geen pending jobs gevonden.")
        return

    print(f"{len(jobs)} job(s) gevonden.")
    for job in jobs:
        process_job(job)

    print("Klaar.")


if __name__ == "__main__":
    main()
