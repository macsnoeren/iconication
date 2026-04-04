import os
import json
import re
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from dotenv import load_dotenv

load_dotenv()

app = FastAPI(title="AAC AI Service")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

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


class TopicRequest(BaseModel):
    topic: str
    goal: str = ""


@app.post("/generate-topic")
async def generate_topic(request: TopicRequest):
    topic = request.topic.strip()
    goal  = request.goal.strip()
    if not topic:
        raise HTTPException(status_code=400, detail="Topic mag niet leeg zijn")
    if len(topic) > 200:
        raise HTTPException(status_code=400, detail="Topic is te lang (max 200 tekens)")
    if len(goal) > 500:
        raise HTTPException(status_code=400, detail="Doel is te lang (max 500 tekens)")

    provider = os.environ.get("AI_PROVIDER", "anthropic").lower()

    try:
        if provider == "openai":
            result = _generate_with_openai(topic, goal)
        else:
            result = _generate_with_anthropic(topic, goal)

        _validate_tree(result)
        return result
    except HTTPException:
        raise
    except json.JSONDecodeError as e:
        raise HTTPException(status_code=500, detail=f"AI gaf geen geldige JSON terug: {e}")
    except ValueError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Fout bij genereren: {e}")


def _build_prompt(topic: str, goal: str) -> str:
    goal_line = f"Doel: {goal}" if goal else ""
    return PROMPT_TEMPLATE.format(topic=topic, goal_line=goal_line)


def _generate_with_anthropic(topic: str, goal: str) -> dict:
    try:
        import anthropic
    except ImportError:
        raise ValueError("anthropic pakket niet geinstalleerd. Voer: pip install anthropic")

    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        raise ValueError("ANTHROPIC_API_KEY is niet ingesteld in .env")

    client = anthropic.Anthropic(api_key=api_key)
    prompt = _build_prompt(topic, goal)

    message = client.messages.create(
        model=os.environ.get("ANTHROPIC_MODEL", "claude-haiku-4-5-20251001"),
        max_tokens=2048,
        messages=[{"role": "user", "content": prompt}],
    )

    return _parse_json_response(message.content[0].text)


def _generate_with_openai(topic: str, goal: str) -> dict:
    try:
        from openai import OpenAI
    except ImportError:
        raise ValueError("openai pakket niet geinstalleerd. Voer: pip install openai")

    api_key = os.environ.get("OPENAI_API_KEY")
    if not api_key:
        raise ValueError("OPENAI_API_KEY is niet ingesteld in .env")

    client = OpenAI(api_key=api_key)
    prompt = _build_prompt(topic, goal)

    response = client.chat.completions.create(
        model=os.environ.get("OPENAI_MODEL", "gpt-4o-mini"),
        messages=[{"role": "user", "content": prompt}],
        response_format={"type": "json_object"},
    )

    return _parse_json_response(response.choices[0].message.content)


def _parse_json_response(content: str) -> dict:
    # Extract JSON block in case model adds extra text
    json_match = re.search(r'\{[\s\S]*\}', content)
    if not json_match:
        raise ValueError("Geen valide JSON gevonden in AI-respons")
    return json.loads(json_match.group())


def _validate_tree(data: dict) -> None:
    if "topic" not in data or "nodes" not in data:
        raise ValueError("Ongeldige structuur: 'topic' of 'nodes' ontbreekt")
    if not isinstance(data["nodes"], list) or len(data["nodes"]) == 0:
        raise ValueError("De decision tree bevat geen nodes")

    node_ids = {node["id"] for node in data["nodes"]}

    for node in data["nodes"]:
        if "id" not in node or "option_a" not in node or "option_b" not in node:
            raise ValueError(f"Node heeft onjuiste structuur: {node}")
        for opt_key in ("option_a", "option_b"):
            opt = node[opt_key]
            if "label" not in opt or "image_hint" not in opt or "next_node_id" not in opt:
                raise ValueError(f"Optie heeft onjuiste structuur in node {node['id']}: {opt}")
            next_id = opt.get("next_node_id")
            if next_id is not None and next_id not in node_ids:
                raise ValueError(
                    f"Node {node['id']} verwijst naar onbekende next_node_id {next_id}"
                )


@app.get("/health")
async def health():
    provider = os.environ.get("AI_PROVIDER", "anthropic")
    return {"status": "ok", "provider": provider}
