#!/usr/bin/env python3
"""
Iconication AI Worker
─────────────────────
Polt de PHP webapplicatie op pending jobs, genereert een decision tree met een LLM,
en stuurt het resultaat terug. Blijft draaien totdat je Ctrl+C drukt.

Gebruik:
    python worker.py
    python worker.py --config /pad/naar/config.ini
"""

import sys
import json
import re
import time
import argparse
import configparser
from pathlib import Path

import requests

# ─── Config laden ────────────────────────────────────────────────────────────

def load_config(path: str) -> configparser.ConfigParser:
    cfg = configparser.ConfigParser()
    if not Path(path).exists():
        sys.exit(
            f"FOUT: Config bestand niet gevonden: {path}\n"
            f"Kopieer config.ini.example naar config.ini en vul de waarden in."
        )
    cfg.read(path, encoding="utf-8")
    return cfg


def get(cfg: configparser.ConfigParser, section: str, key: str, fallback: str = "") -> str:
    return cfg.get(section, key, fallback=fallback).strip()


# ─── Prompt ──────────────────────────────────────────────────────────────────

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


def build_prompt(topic: str, goal: str) -> str:
    goal_line = f"Doel: {goal}" if goal.strip() else ""
    return PROMPT_TEMPLATE.format(topic=topic, goal_line=goal_line)


# ─── HTTP helpers ─────────────────────────────────────────────────────────────

def headers(api_key: str) -> dict:
    return {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }


def fetch_pending_jobs(php_url: str, api_key: str) -> list:
    resp = requests.get(
        f"{php_url}?action=api_pending_jobs",
        headers=headers(api_key),
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()


def submit_result(php_url: str, api_key: str, job_id: int, result: dict) -> None:
    requests.post(
        f"{php_url}?action=api_submit_result",
        headers=headers(api_key),
        json={"job_id": job_id, "result": result},
        timeout=10,
    )


def submit_error(php_url: str, api_key: str, job_id: int, error: str) -> None:
    requests.post(
        f"{php_url}?action=api_submit_result",
        headers=headers(api_key),
        json={"job_id": job_id, "error": error},
        timeout=10,
    )


# ─── AI generatie ─────────────────────────────────────────────────────────────

def generate_with_anthropic(cfg: configparser.ConfigParser, topic: str, goal: str) -> dict:
    try:
        import anthropic
    except ImportError:
        raise RuntimeError("anthropic pakket niet gevonden. Voer: pip install anthropic")

    api_key = get(cfg, "ai", "anthropic_api_key")
    if not api_key or api_key.startswith("sk-ant-..."):
        raise RuntimeError("anthropic_api_key is niet ingesteld in config.ini")

    model = get(cfg, "ai", "anthropic_model", "claude-haiku-4-5-20251001")
    client = anthropic.Anthropic(api_key=api_key)
    message = client.messages.create(
        model=model,
        max_tokens=2048,
        messages=[{"role": "user", "content": build_prompt(topic, goal)}],
    )
    return parse_and_validate(message.content[0].text)


def generate_with_openai(cfg: configparser.ConfigParser, topic: str, goal: str) -> dict:
    try:
        from openai import OpenAI
    except ImportError:
        raise RuntimeError("openai pakket niet gevonden. Voer: pip install openai")

    api_key = get(cfg, "ai", "openai_api_key")
    if not api_key or api_key.startswith("sk-..."):
        raise RuntimeError("openai_api_key is niet ingesteld in config.ini")

    model = get(cfg, "ai", "openai_model", "gpt-4o-mini")
    client = OpenAI(api_key=api_key)
    response = client.chat.completions.create(
        model=model,
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
            next_id = node[opt_key].get("next_node_id")
            if next_id is not None and next_id not in node_ids:
                raise ValueError(
                    f"Node {node['id']} verwijst naar onbekende next_node_id {next_id}"
                )
    return data


# ─── Job verwerking ──────────────────────────────────────────────────────────

def process_job(cfg: configparser.ConfigParser, php_url: str, api_key: str, job: dict) -> None:
    job_id   = job["id"]
    topic    = job["topic"]
    goal     = job.get("goal", "")
    provider = get(cfg, "ai", "provider", "anthropic").lower()

    print(f"  → Job #{job_id}: '{topic}'")
    try:
        if provider == "openai":
            result = generate_with_openai(cfg, topic, goal)
        else:
            result = generate_with_anthropic(cfg, topic, goal)

        submit_result(php_url, api_key, job_id, result)
        print(f"  ✓ Job #{job_id} klaar ({len(result['nodes'])} nodes)")
    except Exception as e:
        submit_error(php_url, api_key, job_id, str(e))
        print(f"  ✗ Job #{job_id} mislukt: {e}")


# ─── Main ─────────────────────────────────────────────────────────────────────

def main() -> None:
    parser = argparse.ArgumentParser(description="Iconication AI Worker")
    parser.add_argument("--config", default="config.ini", help="Pad naar config bestand")
    args = parser.parse_args()

    cfg = load_config(args.config)

    php_url  = get(cfg, "app", "php_app_url").rstrip("/")
    api_key  = get(cfg, "app", "api_key")
    interval = int(get(cfg, "app", "poll_interval", "10"))
    provider = get(cfg, "ai", "provider", "anthropic")

    if not php_url:
        sys.exit("FOUT: php_app_url is niet ingesteld in config.ini")
    if not api_key:
        sys.exit("FOUT: api_key is niet ingesteld in config.ini")

    print(f"Iconication Worker gestart — {php_url}")
    print(f"Poll-interval: {interval}s  |  Provider: {provider}  |  Ctrl+C om te stoppen\n")

    try:
        while True:
            try:
                jobs = fetch_pending_jobs(php_url, api_key)
                if jobs:
                    print(f"[poll] {len(jobs)} job(s) gevonden.")
                    for job in jobs:
                        process_job(cfg, php_url, api_key, job)
                else:
                    print(f"[poll] Geen jobs. Wachten {interval}s...", end="\r", flush=True)
            except requests.RequestException as e:
                print(f"[poll] Verbindingsfout: {e}")
            except Exception as e:
                print(f"[poll] Fout: {e}")

            time.sleep(interval)
    except KeyboardInterrupt:
        print("\nWorker gestopt.")


if __name__ == "__main__":
    main()
