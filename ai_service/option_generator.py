#!/usr/bin/env python3
"""
Option Generator — Step 2
──────────────────────────
Genereert een vervolg-decision-tree op basis van de huidige IntentState.
Wordt aangeroepen als de gebruiker een boom heeft afgelopen en verdere
communicatie gewenst is.
"""

import json
import re
import requests
import configparser

from intent_state import IntentState, top_intent, state_summary


FOLLOWUP_PROMPT = """Je bent een specialist in augmentatieve en alternatieve communicatie (AAC).

Een gebruiker heeft zojuist een communicatie-sessie afgerond over het onderwerp "{topic}".
Dit zijn de keuzes die de gebruiker heeft gemaakt:
{history_lines}

Meest waarschijnlijke intentie: {top_intent}

Maak nu een VERVOLG-decision-tree die:
1. Voortbouwt op wat de gebruiker al heeft gecommuniceerd
2. Niet herhaalt wat al gevraagd/gekozen is
3. Helpt de intentie verder te verduidelijken of af te ronden

Regels:
- PRECIES 2 keuzes per node (option_a en option_b)
- Maximaal 3 niveaus diep
- Labels: simpele, concrete taal, maximaal 5 woorden
- Alleen visueel voorstelbare concepten
- image_hint: 1-2 Nederlandse woorden voor pictogramzoekopdracht
- Geen abstracte begrippen

Geef ALLEEN valide JSON terug:
{{
  "topic": "{topic}",
  "nodes": [
    {{
      "id": 1,
      "option_a": {{"label": "...", "image_hint": "...", "next_node_id": 2}},
      "option_b": {{"label": "...", "image_hint": "...", "next_node_id": 3}}
    }}
  ]
}}

Node 1 is de root. next_node_id is null bij eindpunten.
Gebruik opeenvolgende integer IDs vanaf 1."""


def build_followup_prompt(state: IntentState) -> str:
    history_lines = "\n".join(
        f"  - Stap {i+1}: '{label}'" for i, (_, _, label) in enumerate(state.history)
    )
    if not history_lines:
        history_lines = "  (geen keuzes gemaakt)"

    intent = top_intent(state) or "onbekend"

    return FOLLOWUP_PROMPT.format(
        topic=state.topic,
        history_lines=history_lines,
        top_intent=intent,
    )


def generate_followup(cfg: configparser.ConfigParser, state: IntentState) -> dict:
    """
    Vraag Ollama om een vervolg-tree op basis van de doorlopen IntentState.
    Geeft een gevalideerde tree-dict terug (zelfde formaat als de normale generator).
    """
    ollama_url = cfg.get("ai", "ollama_url", fallback="http://localhost:11434/api/generate")
    model = _active_model(cfg)

    prompt = build_followup_prompt(state)

    response = requests.post(
        ollama_url,
        json={"model": model, "prompt": prompt, "stream": False},
        timeout=600,
    )
    response.raise_for_status()

    raw = response.json().get("response", "")
    return _parse_and_validate(raw)


def _active_model(cfg: configparser.ConfigParser) -> str:
    import subprocess
    tuned    = cfg.get("ai", "tuned_model_name", fallback="iconication-aac").strip()
    base     = cfg.get("ai", "ollama_model",     fallback="llama3").strip()
    bin_path = cfg.get("ai", "ollama_bin",       fallback="ollama").strip()
    if not tuned:
        return base
    try:
        result = subprocess.run([bin_path, "list"], capture_output=True, text=True, timeout=5)
        if tuned in result.stdout:
            return tuned
    except Exception:
        pass
    return base


def _parse_and_validate(content: str) -> dict:
    match = re.search(r'\{[\s\S]*\}', content)
    if not match:
        raise ValueError("Geen valide JSON in AI-respons")
    data = json.loads(match.group())
    if "topic" not in data or "nodes" not in data:
        raise ValueError("Ongeldige structuur: 'topic' of 'nodes' ontbreekt")
    if not isinstance(data["nodes"], list) or not data["nodes"]:
        raise ValueError("Vervolg-tree bevat geen nodes")
    node_ids = {n["id"] for n in data["nodes"]}
    for node in data["nodes"]:
        for opt_key in ("option_a", "option_b"):
            nxt = node[opt_key].get("next_node_id")
            if nxt is not None and nxt not in node_ids:
                raise ValueError(
                    f"Node {node['id']} verwijst naar onbekende next_node_id {nxt}"
                )
    return data
