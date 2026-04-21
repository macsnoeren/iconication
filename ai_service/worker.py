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
import subprocess
import tempfile
import os
from pathlib import Path

import requests
from option_generator import generate_followup
from intent_state import IntentState

# ─── Config laden ────────────────────────────────────────────────────────────

def load_config(path: str) -> configparser.ConfigParser:
    cfg = configparser.ConfigParser(inline_comment_prefixes=("#", ";"))
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


DISCOVERY_PROMPT = """Je bent een specialist in augmentatieve en alternatieve communicatie (AAC).

Maak een DIEP adaptieve ontdekkers-beslisboom (3-4 niveaus, PRECIES 4 opties per node).
Doel: achterhalen wat een niet-verbale gebruiker PRECIES wil overbrengen, inclusief complexe intenties zoals:
- "Ik wil graag buiten wandelen"
- "Ik wil een spelletje spelen"
- "Vertel mijn broer dat ik hem graag wil zien"
- "Ik heb pijn in mijn been"
- "Ik wil graag muziek luisteren"

Structuur:
- Niveau 1: Brede categorie (lichaam / activiteit / persoon / gevoel)
- Niveau 2: Verfijning (welk deel? wat voor activiteit? wie?)
- Niveau 3: Actie of detail
- Niveau 4 (leaf): Specifieke intentie met suggested_message

Regels:
- PRECIES 4 opties per node
- Minimaal 3, maximaal 4 niveaus
- Labels: max 5 woorden, concreet en visueel
- image_hint: 1-2 Nederlandse woorden voor ARASAAC
- Eindnodes: next_node_id=null, target_topic=kort onderwerp, suggested_message=volledige zin

Voorbeeldblad: {{"label": "Broer uitnodigen", "image_hint": "broer bezoek", "next_node_id": null, "target_topic": "Familie", "suggested_message": "Vertel mijn broer dat ik hem graag wil zien"}}
Voorbeeldblad: {{"label": "Buiten wandelen", "image_hint": "wandelen buiten", "next_node_id": null, "target_topic": "Wandelen", "suggested_message": "Ik wil graag buiten gaan wandelen"}}

Geef ALLEEN valide JSON terug (geen extra tekst):
{{
  "topic": "ontdekking",
  "nodes": [
    {{
      "id": 1,
      "options": [
        {{"label": "Iets met mijn lichaam", "image_hint": "lichaam", "next_node_id": 2}},
        {{"label": "Ik wil iets doen", "image_hint": "activiteit doen", "next_node_id": 6}},
        {{"label": "Iets met een persoon", "image_hint": "mensen praten", "next_node_id": 10}},
        {{"label": "Hoe ik me voel", "image_hint": "gevoel emotie", "next_node_id": 14}}
      ]
    }}
  ]
}}

Gebruik opeenvolgende integer IDs vanaf 1. Elke node heeft PRECIES 4 opties."""


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
    text = resp.text.strip()
    if not text:
        raise ValueError("Lege respons — controleer PHP-fouten of API key")
    try:
        data = json.loads(text)
    except json.JSONDecodeError as e:
        preview = text[:300].replace("\n", " ")
        raise ValueError(f"Ongeldige JSON van server ({e}). Response: {preview}")
    if not isinstance(data, list):
        raise ValueError(f"Server gaf geen lijst terug: {data}")
    return data


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


# ─── ARASAAC pictogram zoeken ─────────────────────────────────────────────────

def find_icon(hint: str, language: str = "nl") -> str:
    """Zoek een pictogram via ARASAAC. Geeft de afbeeldings-URL terug of lege string."""
    if not hint.strip():
        return ""
    try:
        resp = requests.get(
            f"https://api.arasaac.org/v1/pictograms/{language}/search/{hint}",
            timeout=8,
        )
        resp.raise_for_status()
        results = resp.json()
        if not results:
            return ""
        pic_id = results[0]["_id"]
        return f"https://api.arasaac.org/v1/pictograms/{pic_id}"
    except Exception as e:
        print(f"  [icon] Zoekfout voor '{hint}': {e}")
        return ""


def enrich_with_icons(result: dict, language: str = "nl") -> dict:
    """Voeg image_url toe aan elke optie op basis van image_hint (skip als al ingevuld)."""
    for node in result["nodes"]:
        for opt_key in ("option_a", "option_b"):
            opt  = node[opt_key]
            if opt.get("image_url"):
                print(f"  [icon] '{opt.get('image_hint', '')}' → behouden ({opt['image_url']})")
                continue
            hint = opt.get("image_hint", "")
            url  = find_icon(hint, language)
            opt["image_url"] = url
            if url:
                print(f"  [icon] '{hint}' → {url}")
            else:
                print(f"  [icon] '{hint}' → geen resultaat")
    return result


# ─── AI generatie via Ollama ──────────────────────────────────────────────────

def _generate_discovery(cfg: configparser.ConfigParser) -> dict:
    ollama_url = get(cfg, "ai", "ollama_url", "http://localhost:11434/api/generate")
    model      = active_model(cfg)
    response   = requests.post(ollama_url, json={"model": model, "prompt": DISCOVERY_PROMPT, "stream": False}, timeout=600)
    response.raise_for_status()
    raw  = response.json().get("response", "")
    data = _parse_discovery(raw)
    language = get(cfg, "ai", "arasaac_language", "nl")
    return _enrich_discovery(data, language)


def _parse_discovery(content: str) -> dict:
    match = re.search(r'\{[\s\S]*\}', content)
    if not match:
        raise ValueError("Geen valide JSON in discovery-respons")
    data = json.loads(match.group())
    if "nodes" not in data or not data["nodes"]:
        raise ValueError("Discovery tree bevat geen nodes")
    node_ids = {n["id"] for n in data["nodes"]}
    for node in data["nodes"]:
        opts = node.get("options", [])
        if len(opts) < 2:
            raise ValueError(f"Node {node['id']} heeft te weinig opties ({len(opts)})")
        # Pad to 4 if AI returned fewer (safety fallback)
        while len(opts) < 4:
            opts.append({"label": "...", "image_hint": "meer", "next_node_id": None,
                         "target_topic": "Overig", "suggested_message": "Ik wil iets anders zeggen"})
        node["options"] = opts[:4]
        for opt in node["options"]:
            nxt = opt.get("next_node_id")
            if nxt is not None and nxt not in node_ids:
                opt["next_node_id"] = None  # Fix broken refs instead of crashing
                if "target_topic" not in opt:
                    opt["target_topic"] = opt.get("label", "Onderwerp")
                if "suggested_message" not in opt:
                    opt["suggested_message"] = "Ik wil " + opt.get("label", "iets").lower()
    # Ensure all leaf nodes have suggested_message
    for node in data["nodes"]:
        for opt in node["options"]:
            if opt.get("next_node_id") is None:
                if "suggested_message" not in opt or not opt["suggested_message"]:
                    opt["suggested_message"] = "Ik wil " + opt.get("label", "iets").lower()
                if "target_topic" not in opt or not opt["target_topic"]:
                    opt["target_topic"] = opt.get("label", "Onderwerp")
    return data


def _enrich_discovery(data: dict, language: str) -> dict:
    for node in data["nodes"]:
        for opt in node.get("options", []):
            if opt.get("image_url"):
                continue
            hint = opt.get("image_hint", "")
            url  = find_icon(hint, language)
            opt["image_url"] = url
            print(f"  [icon/disc] '{hint}' → {url or 'geen'}")
    return data


def generate_with_ollama(cfg: configparser.ConfigParser, topic: str, goal: str,
                         model: str = "") -> dict:
    ollama_url = get(cfg, "ai", "ollama_url", "http://localhost:11434/api/generate")
    if not model:
        model = active_model(cfg)

    payload = {
        "model":  model,
        "prompt": build_prompt(topic, goal),
        "stream": False,
    }

    response = requests.post(ollama_url, json=payload, timeout=600)
    response.raise_for_status()

    raw    = response.json().get("response", "")
    result = parse_and_validate(raw)
    return result


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


# ─── AI Tuning via Ollama Modelfile ──────────────────────────────────────────

def fetch_training_examples(php_url: str, api_key: str) -> list:
    resp = requests.get(
        f"{php_url}?action=api_training_examples",
        headers=headers(api_key),
        timeout=10,
    )
    resp.raise_for_status()
    return resp.json()


def build_modelfile(base_model: str, examples: list) -> str:
    """Genereer een Ollama Modelfile met few-shot voorbeelden uit de database."""

    system_prompt = (
        "Je bent een specialist in augmentatieve en alternatieve communicatie (AAC). "
        "Je maakt decision trees voor communicatie met gebruikers die moeite hebben met spreken. "
        "Regels: precies 2 keuzes per node, simpele concrete taal (max 5 woorden), "
        "maximaal 4 niveaus diep, alleen visueel voorstelbare concepten. "
        "Geef altijd alleen valide JSON terug, geen extra tekst."
    )

    lines = [
        f"FROM {base_model}",
        f'SYSTEM """{system_prompt}"""',
        "",
    ]

    for ex in examples:
        topic = ex["topic"]
        notes = ex.get("notes", "")
        user_msg = f'Maak een decision tree voor het onderwerp: "{topic}"'
        if notes:
            user_msg += f" ({notes})"

        # Normaliseer node-IDs naar 1-gebaseerde volgorde voor consistente few-shot patronen
        id_map   = {n["id"]: i + 1 for i, n in enumerate(ex["nodes"])}
        norm_nodes = []
        for n in ex["nodes"]:
            def remap(opt):
                nxt = opt.get("next_node_id")
                return {**opt, "next_node_id": id_map[nxt] if nxt in id_map else None}
            norm_nodes.append({
                "id":       id_map[n["id"]],
                "option_a": remap(n["option_a"]),
                "option_b": remap(n["option_b"]),
            })

        assistant_msg = json.dumps(
            {"topic": topic, "nodes": norm_nodes},
            ensure_ascii=False, indent=2
        )

        lines += [
            f'MESSAGE user "{user_msg}"',
            f'MESSAGE assistant "{assistant_msg}"',
            "",
        ]

    return "\n".join(lines)


def run_tune(cfg: configparser.ConfigParser, php_url: str, api_key: str) -> None:
    base_model   = get(cfg, "ai", "ollama_model", "llama3")
    tuned_model  = get(cfg, "ai", "tuned_model_name", "iconication-aac")
    ollama_bin   = get(cfg, "ai", "ollama_bin", "ollama")

    print(f"Trainingsvoorbeelden ophalen van {php_url} ...")
    examples = fetch_training_examples(php_url, api_key)

    if not examples:
        print("Geen trainingsvoorbeelden gevonden. Markeer topics via Admin → AI Trainen.")
        return

    print(f"{len(examples)} voorbeeld(en) geladen: {[e['topic'] for e in examples]}")

    modelfile_content = build_modelfile(base_model, examples)

    # Schrijf Modelfile naar een tijdelijk bestand
    tmp = tempfile.NamedTemporaryFile(mode="w", suffix=".Modelfile",
                                     delete=False, encoding="utf-8")
    tmp.write(modelfile_content)
    tmp.close()

    print(f"\nModelfile geschreven naar: {tmp.name}")
    print(f"Aanmaken model '{tuned_model}' op basis van '{base_model}' ...")

    try:
        result = subprocess.run(
            [ollama_bin, "create", tuned_model, "-f", tmp.name],
            capture_output=False,
            text=True,
        )
        if result.returncode == 0:
            print(f"\n✓ Model '{tuned_model}' succesvol aangemaakt.")
            print(f"  De worker gebruikt dit model automatisch bij de volgende jobs.")
        else:
            print(f"\n✗ Ollama gaf een foutcode terug ({result.returncode}).")
    finally:
        os.unlink(tmp.name)


# ─── Dynamische opties generatie ─────────────────────────────────────────────

AAC_GUESS_PROMPT_START = """Je bent een AI component binnen een AAC (Augmentative and Alternative Communication) systeem.

Dit is het BEGIN van het gesprek. Er is nog geen context.

Jouw taak: genereer de eerste 4 opties om stap voor stap te achterhalen wat de gebruiker wil zeggen.
Begin BREED met basiscategorieën voor de meest voorkomende behoeften van AAC-gebruikers.

HARDE REGELS:
- PRECIES 4 opties, niet meer, niet minder
- Optie 1, 2, 3: brede basiscategorieën (pijn/lichaam, eten/drinken, gevoel, activiteit, persoon, hulp)
- Optie 4: ALTIJD exact label "Iets anders" met image_query "anders"
- Labels: maximaal 1-3 woorden, concreet en visueel voorstelbaar

Geef ALLEEN valide JSON:
{{
  "options": [
    {{"id": "option_1", "label": "Pijn", "image_query": "pijn lichaam"}},
    {{"id": "option_2", "label": "Eten of drinken", "image_query": "eten drinken"}},
    {{"id": "option_3", "label": "Iets doen", "image_query": "activiteit doen"}},
    {{"id": "other",    "label": "Iets anders", "image_query": "anders"}}
  ],
  "reasoning": "Brede startcategorieën voor AAC",
  "is_complete": false,
  "sentence_so_far": "",
  "current_state": {{"hypothesis": "onbekend", "confidence": 0.0}}
}}"""


AAC_GUESS_PROMPT = """Je bent een AI component binnen een AAC (Augmentative and Alternative Communication) systeem.
Je speelt een raadspelletje: je probeert te raden wat de gebruiker wil zeggen.

Selectiegeschiedenis (wat de gebruiker tot nu toe koos):
{history_text}

Huidige intentie-hypothese:
{current_state_text}

Al getoonde opties (NOOIT herhalen):
{shown_text}

{andere_note}

TAAK:
Genereer PRECIES 4 opties EN beslis of je al een goede gok durft te doen.

HARDE REGELS voor de 4 opties:
- PRECIES 4 opties, niet meer, niet minder
- Optie 1, 2, 3: concreet, visueel, 1-2 woorden, duidelijk van elkaar verschillend
- Optie 4: ALTIJD exact label "Iets anders" met image_query "anders"
- VERBODEN: abstracte termen, medische diagnoses, lange zinnen, synoniemen die te veel op elkaar lijken
- Herhaal NOOIT opties uit "Al getoonde opties"

RAAD-LOGICA (has_guess):
- Als je voldoende zeker bent (confidence >= 0.75): zet has_guess op true
- Schrijf dan een guess_sentence: een korte, natuurlijke volledige zin die de gebruiker wil zeggen
  (bijv. "Ik heb hoofdpijn" of "Ik wil graag een broodje eten")
- De 4 opties blijven altijd aanwezig als fallback voor als de gok fout is
- Na 2-3 keuzes mag je al gokken als het duidelijk is

GEDRAG:
- Begin breed, word specifieker na elke keuze
- Doel: zo snel mogelijk een goede gok doen

OUTPUT (STRICT JSON):
{{
  "options": [
    {{"id": "option_1", "label": "...", "image_query": "..."}},
    {{"id": "option_2", "label": "...", "image_query": "..."}},
    {{"id": "option_3", "label": "...", "image_query": "..."}},
    {{"id": "other",    "label": "Iets anders", "image_query": "anders"}}
  ],
  "has_guess": false,
  "guess_sentence": "",
  "reasoning": "korte uitleg",
  "sentence_so_far": "...",
  "current_state": {{"hypothesis": "...", "confidence": 0.0}}
}}

Geef ALLEEN valide JSON terug, geen extra tekst."""


def generate_dynamic_options(cfg: configparser.ConfigParser, history: list,
                             shown_options: list = None,
                             current_state: dict = None) -> dict:
    if not history:
        prompt = AAC_GUESS_PROMPT_START
    else:
        effective_history = list(history)
        andere_note = ""
        if effective_history and effective_history[-1].lower() in ("iets anders", "something else"):
            effective_history.pop()
            andere_note = (
                "LET OP: De gebruiker koos 'Iets anders' — de vorige opties pasten niet. "
                "Ga een ANDERE richting op, herhaal geen eerdere categorieën, "
                "en herhaal NOOIT opties uit de 'Al getoonde opties' lijst."
            )

        history_text = "\n".join(f"  {i+1}. {label}" for i, label in enumerate(effective_history))
        shown        = shown_options or []
        shown_text   = "\n".join(f"  - {label}" for label in shown) if shown else "  (nog geen opties getoond)"
        state        = current_state or {}
        current_state_text = (
            f"  Hypothese: {state.get('hypothesis', 'onbekend')}  "
            f"(zekerheid: {int(state.get('confidence', 0) * 100)}%)"
            if state.get("hypothesis") else "  (nog onbekend)"
        )
        prompt = AAC_GUESS_PROMPT.format(
            history_text=history_text,
            shown_text=shown_text,
            current_state_text=current_state_text,
            andere_note=andere_note,
        )

    ollama_url = get(cfg, "ai", "ollama_url", "http://localhost:11434/api/generate")
    model      = active_model(cfg)
    response   = requests.post(ollama_url,
                               json={"model": model, "prompt": prompt, "stream": False},
                               timeout=600)
    response.raise_for_status()
    return _parse_dynamic_options(response.json().get("response", ""))


def _parse_dynamic_options(content: str) -> dict:
    match = re.search(r'\{[\s\S]*\}', content)
    if not match:
        raise ValueError("Geen valide JSON in dynamic-options respons")
    data    = json.loads(match.group())
    options = data.get("options", [])

    if len(options) < 2:
        raise ValueError(f"Te weinig opties in respons: {len(options)}")

    # Zet "Iets anders" altijd als laatste optie
    andere  = [o for o in options if o.get("label", "").strip().lower() == "iets anders"]
    regular = [o for o in options if o.get("label", "").strip().lower() != "iets anders"]
    if not andere:
        andere = [{"id": "other", "label": "Iets anders", "image_query": "anders"}]
    options = regular[:3] + andere[:1]

    has_guess     = bool(data.get("has_guess", data.get("is_complete", False)))
    guess_sentence = data.get("guess_sentence", "").strip()
    sentence      = data.get("sentence_so_far", "")
    current_state = data.get("current_state", {"hypothesis": "onbekend", "confidence": 0.0})

    # Als er een gok is maar geen guess_sentence, gebruik sentence_so_far als fallback
    if has_guess and not guess_sentence:
        guess_sentence = sentence

    return {
        "has_guess":       has_guess,
        "guess_sentence":  guess_sentence,
        "sentence_so_far": sentence,
        "options":         options,
        "current_state":   current_state,
    }


# ─── Job verwerking ──────────────────────────────────────────────────────────

def process_job(cfg: configparser.ConfigParser, php_url: str, api_key: str, job: dict) -> None:
    job_id      = job["id"]
    topic       = job["topic"]
    goal        = job.get("goal", "")
    state_json  = job.get("state_json") or ""
    params_json = job.get("params_json") or ""
    job_type    = job.get("job_type") or "topic"
    language    = get(cfg, "ai", "arasaac_language", "nl")

    print(f"  → Job #{job_id}: '{topic}' [{job_type}]")
    try:
        if job_type == "dynamic_options":
            if params_json.strip() and params_json.strip() != "null":
                params        = json.loads(params_json)
                history       = params.get("history", [])
                shown_options = params.get("shown_options", [])
                current_state = params.get("current_state") or None
                language      = params.get("language", language)
            else:
                raw_state     = json.loads(state_json) if state_json.strip() and state_json.strip() != "null" else {}
                history       = raw_state.get("history", [])
                shown_options = []
                current_state = None
            result = generate_dynamic_options(cfg, history, shown_options, current_state)
            for opt in result["options"]:
                if not opt.get("image_url"):
                    # Gebruik image_query (nieuw formaat) of image_hint (oud formaat) als fallback
                    hint = opt.get("image_query") or opt.get("image_hint", "")
                    opt["image_url"] = find_icon(hint, language)
            submit_result(php_url, api_key, job_id, result)
            print(f"  ✓ Job #{job_id} klaar ({len(result['options'])} opties, complete={result['is_complete']})")

        elif job_type == "discovery":
            result = _generate_discovery(cfg)
            submit_result(php_url, api_key, job_id, result)
            print(f"  ✓ Job #{job_id} klaar (discovery, {len(result['nodes'])} nodes)")

        else:
            # Reguliere topic-boom of follow-up
            if state_json.strip() and state_json.strip() != "null":
                raw = json.loads(state_json)
                state = IntentState(
                    topic                = raw.get("topic", topic),
                    intent_probabilities = raw.get("intent_probabilities", {}),
                    history              = [(h["node"], "selected", h["label"]) for h in raw.get("history", [])],
                    current_node_id      = raw.get("current_node_id"),
                    completed            = raw.get("completed", False),
                )
                print(f"  [followup] intentie: {state.intent_probabilities}")
                result = generate_followup(cfg, state)
            else:
                result = generate_with_ollama(cfg, topic, goal)
            result = enrich_with_icons(result, language)
            submit_result(php_url, api_key, job_id, result)
            print(f"  ✓ Job #{job_id} klaar ({len(result['nodes'])} nodes)")

    except Exception as e:
        submit_error(php_url, api_key, job_id, str(e))
        print(f"  ✗ Job #{job_id} mislukt: {e}")


# ─── Main ─────────────────────────────────────────────────────────────────────

def active_model(cfg: configparser.ConfigParser) -> str:
    """Geeft het getuunde model terug als het bestaat in Ollama, anders het basis model."""
    tuned      = get(cfg, "ai", "tuned_model_name", "iconication-aac")
    base       = get(cfg, "ai", "ollama_model", "llama3")
    ollama_bin = get(cfg, "ai", "ollama_bin", "ollama")
    if not tuned:
        return base
    try:
        result = subprocess.run(
            [ollama_bin, "list"], capture_output=True, text=True, timeout=5
        )
        if tuned in result.stdout:
            return tuned
    except Exception:
        pass
    return base


def main() -> None:
    parser = argparse.ArgumentParser(description="Iconication AI Worker")
    parser.add_argument("--config", default="config.ini", help="Pad naar config bestand")
    parser.add_argument("--tune", action="store_true",
                        help="Maak een getuned Ollama-model op basis van de trainingsvoorbeelden")
    args = parser.parse_args()

    cfg = load_config(args.config)

    php_url = get(cfg, "app", "php_app_url").rstrip("/")
    api_key = get(cfg, "app", "api_key")

    if not php_url:
        sys.exit("FOUT: php_app_url is niet ingesteld in config.ini")
    if not api_key:
        sys.exit("FOUT: api_key is niet ingesteld in config.ini")

    # ── Tune modus ────────────────────────────────────────────────────
    if args.tune:
        run_tune(cfg, php_url, api_key)
        return

    # ── Poll modus ────────────────────────────────────────────────────
    interval = int(get(cfg, "app", "poll_interval", "10"))
    model    = active_model(cfg)

    print(f"Iconication Worker gestart — {php_url}")
    print(f"Model: {model}  |  Poll-interval: {interval}s  |  Ctrl+C om te stoppen\n")

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
