<?php

namespace App\Services\Edt;

/**
 * OCR spécialisé pour les emplois du temps d'enseignants dans d'autres établissements.
 *
 * Hérite du service vacataire pour la logique image/PDF, mais utilise un prompt
 * différent : on extrait des créneaux OCCUPÉS (cours déjà attribués), non des
 * disponibilités. Ces créneaux deviennent des contraintes dures HARD_NO_TEACHER_EXTERNAL_COLLISION
 * dans le moteur de génération IA.
 *
 * Données supplémentaires extraites vs le service vacataire :
 *  - etablissement (nom de l'autre école)
 *  - professeur (nom du prof sur le document)
 *  - classe (classe enseignée à ce créneau)
 *  - matiere (discipline enseignée)
 */
class ExternalScheduleOcrService extends OpenAiVacataireOcrService
{
    protected function ocrPrompt(): string
    {
        return <<<'PROMPT'
Tu analyses un emploi du temps officiel d'un enseignant dans un établissement scolaire.

Objectif : extraire TOUS les créneaux où ce professeur est OCCUPÉ (en cours d'enseignement)
afin qu'un autre système scolaire puisse éviter de lui attribuer un cours au même moment.

Retourne STRICTEMENT un JSON valide sans aucun texte autour, au format suivant :

{
  "teacher_name": "NOM Prénom ou null si non détecté",
  "etablissement": "Nom de l'établissement scolaire sur le document ou null",
  "source_notes": "Observations importantes sur la qualité de l'extraction",
  "confidence_score": 85,
  "slots": [
    {
      "jour": "lundi",
      "heure_debut": "07:10",
      "heure_fin": "08:05",
      "classe": "TleD",
      "matiere": "SVT",
      "salle": "SALLE 1",
      "commentaire": null
    }
  ]
}

Règles strictes :
1. "jour" doit être en minuscules : lundi, mardi, mercredi, jeudi, vendredi ou samedi.
2. "heure_debut" et "heure_fin" au format 24h HH:MM (ex: 07:10, 14:25).
3. N'extrait QUE les créneaux avec un cours réel — ignore récréation, pause déjeuner, heures libres.
4. "classe" : indique la classe enseignée (ex: TleD, 3ème1, 2ndeC). Null si non lisible.
5. "matiere" : indique la discipline (ex: SVT, Maths, Français). Null si non lisible.
6. "salle" : numéro ou nom de la salle si présent sur le document. Null sinon.
7. Si deux cours sont au même horaire sur des colonnes différentes (matin/après-midi), extrait les deux.
8. "confidence_score" : entier entre 0 et 100 représentant ta confiance globale dans l'extraction.
9. Si le document est flou ou peu lisible, signale-le dans "source_notes" et mets un score faible.
10. Ne génère PAS de créneaux inventés. En cas de doute, omets le créneau et note le problème.
PROMPT;
    }

    protected function normalizePayload(array $data): array
    {
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

        $slots = collect($data['slots'] ?? [])
            ->map(function ($slot) {
                return [
                    'jour'        => strtolower(trim((string) ($slot['jour'] ?? ''))),
                    'heure_debut' => substr((string) ($slot['heure_debut'] ?? ''), 0, 5),
                    'heure_fin'   => substr((string) ($slot['heure_fin'] ?? ''), 0, 5),
                    'classe'      => $slot['classe'] ?? null,
                    'matiere'     => $slot['matiere'] ?? null,
                    'salle'       => $slot['salle'] ?? null,
                    'commentaire' => $slot['commentaire'] ?? null,
                    // Tous les créneaux occupés = le prof ne peut pas être ailleurs
                    'etat'        => 'indisponible',
                ];
            })
            ->filter(function ($slot) use ($jours) {
                return in_array($slot['jour'], $jours, true)
                    && preg_match('/^\d{2}:\d{2}$/', $slot['heure_debut'])
                    && preg_match('/^\d{2}:\d{2}$/', $slot['heure_fin'])
                    && $slot['heure_debut'] < $slot['heure_fin'];
            })
            ->values()
            ->all();

        return [
            'teacher_name'    => $data['teacher_name'] ?? null,
            'etablissement'   => $data['etablissement'] ?? null,
            'source_notes'    => $data['source_notes'] ?? null,
            'confidence_score' => (int) ($data['confidence_score'] ?? 0),
            'slots'           => $slots,
        ];
    }
}
