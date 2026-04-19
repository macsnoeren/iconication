#!/usr/bin/env python3
"""
Intent State — Step 1
─────────────────────
Bijhoudt de communicatie-intentie van een gebruiker tijdens een sessie.
Elke keuze in de decision tree werkt de kansverdeling over intenties bij.
"""

from dataclasses import dataclass, field
from typing import Optional


@dataclass
class IntentState:
    topic: str
    # Kansverdeling over intenties (label → kans 0.0–1.0, som ≈ 1.0)
    intent_probabilities: dict[str, float] = field(default_factory=dict)
    # Gekozen opties in volgorde: [(node_id, option_key, label), ...]
    history: list[tuple[int, str, str]] = field(default_factory=list)
    # Huidig node-ID (None = sessie nog niet gestart of afgerond)
    current_node_id: Optional[int] = None
    # Werd de boom volledig doorlopen (eindpunt bereikt)?
    completed: bool = False


def make_state(topic: str, root_node_id: int) -> IntentState:
    """Maak een lege beginstatus voor een nieuw onderwerp."""
    return IntentState(topic=topic, current_node_id=root_node_id)


def update_state(state: IntentState, node_id: int, option_key: str,
                 label: str, next_node_id: Optional[int]) -> IntentState:
    """
    Verwerk een gemaakte keuze en geef een bijgewerkte state terug.

    - Voegt de keuze toe aan de geschiedenis.
    - Past de intentie-kansverdeling bij op basis van de keuze.
    - Markeert de sessie als afgerond als next_node_id None is.
    """
    state.history.append((node_id, option_key, label))
    state.current_node_id = next_node_id
    state.completed = (next_node_id is None)
    _update_probabilities(state, label)
    return state


def _update_probabilities(state: IntentState, chosen_label: str) -> None:
    """
    Eenvoudige Bayesiaanse update: verhoog kans voor intenties die overeenkomen
    met het gekozen label, verlaag de rest. Normaliseert daarna naar som = 1.0.
    """
    if not state.intent_probabilities:
        # Eerste keuze: initialiseer met de gekozen label als enige intentie
        state.intent_probabilities[chosen_label] = 1.0
        return

    boost = 0.3
    updated: dict[str, float] = {}
    for intent, prob in state.intent_probabilities.items():
        if intent.lower() in chosen_label.lower() or chosen_label.lower() in intent.lower():
            updated[intent] = prob + boost
        else:
            updated[intent] = max(0.0, prob - boost / len(state.intent_probabilities))

    # Voeg gekozen label toe als nieuwe intentie als het er nog niet in zit
    if chosen_label not in updated:
        updated[chosen_label] = boost

    # Normaliseer
    total = sum(updated.values())
    if total > 0:
        state.intent_probabilities = {k: v / total for k, v in updated.items()}
    else:
        state.intent_probabilities = {chosen_label: 1.0}


def top_intent(state: IntentState) -> Optional[str]:
    """Geeft de meest waarschijnlijke intentie terug, of None als er geen zijn."""
    if not state.intent_probabilities:
        return None
    return max(state.intent_probabilities, key=lambda k: state.intent_probabilities[k])


def state_summary(state: IntentState) -> dict:
    """Geeft een beknopte dict-weergave terug (handig voor logging / API-respons)."""
    return {
        "topic":      state.topic,
        "completed":  state.completed,
        "top_intent": top_intent(state),
        "history":    [{"node": n, "option": o, "label": l} for n, o, l in state.history],
        "probabilities": state.intent_probabilities,
    }
