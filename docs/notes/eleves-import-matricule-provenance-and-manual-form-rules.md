# Élèves — matricule multi-écoles, statut et tarification

## Décision fonctionnelle

Dans AviaSchoolPay, le matricule DESPS peut être identique dans plusieurs établissements. Le matricule seul ne doit donc jamais être considéré comme identifiant global fiable.

La clé de résolution fiable est :

- établissement ;
- matricule DESPS ou matricule interne ;
- compte utilisateur lié.

## Import IA / OCR

Lors de l’import IA/OCR :

1. Si le matricule DESPS est valide, il est conservé.
2. Si le matricule DESPS est absent, trop court, mal orthographié ou mal lu, la ligne reste importable.
3. Un matricule interne est attribué et visible dans l’aperçu avant validation.
4. Si un matricule existe déjà dans une ou plusieurs écoles, l’aperçu doit afficher les provenances : matricule, nom de l’élève trouvé, école, code établissement, indication même école / autre école.
5. Si le sexe est vide ou illisible, la ligne reste importable et la cellule reste vide dans l’aperçu pour correction manuelle.

## Création et édition manuelle

Les vues `/eleves/create` et `/eleves/{id}/edit` doivent :

- afficher le statut élève : AFF / NAFF ;
- garder le champ classe ;
- garder le champ matricule DESPS ;
- ne plus afficher le bloc financier « Inscription & scolarité » ;
- ne plus permettre de saisir montant brut, réduction ou plan de paiement depuis la fiche élève.

La tarification doit rester centralisée dans :

- `/finances/tarifs`

## Paiements

Les paiements doivent utiliser les montants calculés par les tarifs configurés dans `/finances/tarifs` et synchronisés via `TarificationService`.

Ils ne doivent pas dépendre d’un montant saisi manuellement dans la fiche élève.
