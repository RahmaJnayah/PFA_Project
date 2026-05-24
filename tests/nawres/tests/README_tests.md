# Tests BehaviorAnalyzer — Guide de mise en place

## Structure attendue du projet

```
project/
├── src/
│   ├── BehaviorAnalyzer.php      ← ton fichier source
│   └── analysis_config.php       ← ta config
├── tests/
│   └── BehaviorAnalyzerTest.php  ← fichier fourni ici
├── composer.json
└── phpunit.xml
```

## Installation

```bash
composer install
```

## Lancer les tests

```bash
# Tous les tests avec sortie lisible
./vendor/bin/phpunit --testdox

# Avec couverture HTML (nécessite xdebug ou pcov)
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage-html

# Un seul groupe
./vendor/bin/phpunit --filter="test_resolveState"
```

---

## Groupes de tests couverts (17 groupes, ~50 cas)

| # | Groupe | Type | Ce qui est testé |
|---|--------|------|-----------------|
| 1 | `resolveState` | Fonctionnel | Tous les états possibles : Neutral, Agitated, High Engagement, Disengaged, Happy + cas limites (scores égaux, diff faible) |
| 2 | `resolveAgeGroup` | Fonctionnel | Mapping âge → groupe `4-6` / `7-9` / `10-12`, valeurs limites exactes, null, négatif |
| 3 | `getBaselineLatency` | Fonctionnel | Jeu connu/âge connu, jeu inconnu → fallback default, config vide → 1500 |
| 4 | `getGameWeight` | Fonctionnel | Slug connu retourne le bon poids, slug inconnu → default_weight |
| 5 | `decodeJson` | Fonctionnel + Robustesse | null, chaîne vide, JSON valide, JSON invalide, tableau indexé, tableau déjà décodé |
| 6 | `normalizeRawEvents` | Fonctionnel + Robustesse | Filtre entrées invalides (signal vide, pas de ts, scalaire), préserve pair_id, entrée vide |
| 7 | `detectPairTracking` | Fonctionnel | Avec pair_id → enabled + extractor correct, sans pair_id → disabled |
| 8 | `slugify` | Fonctionnel + Robustesse | Espaces, tirets, majuscules, underscores de bord, chaîne vide, caractères spéciaux |
| 9 | `applySummaryRules` | Fonctionnel | Haute latence → boredom, haut error_rate → frustration, faible erreur+latence → focus, succès élevé → joy, signaux vides → 0 |
| 10 | `applyEventRules` | Fonctionnel | Long pause → boredom, spam reactions → frustration, error spiral → frustration, joy streak, quick recovery → focus, sluggish → boredom, decay déclenché |
| 11 | Memory Game (pair repeat) | Fonctionnel | Même paire d'erreur deux fois → memory_pair_repeat + frustration |
| 12 | `analyzeSessionRecord` | Fonctionnel | raw_signals → qualité `raw_signals`, session vide → `no_data`, summary → `summary_only` |
| 13 | `storeAnalysis` | Fonctionnel + Robustesse | INSERT réussi → true, échec PDO → false |
| 14 | `runPeriodAnalysis` | Fonctionnel | Aucun enfant éligible → compteurs à 0 |
| 15 | `analyzeChildAllSessions` | Robustesse | Aucune session → null sans exception |
| 16 | `mergeWithHistory` | Fonctionnel | Pas d'historique → scores inchangés, avec historique → scores augmentés + sourceWeights rempli |
| 17 | Robustesse globale | Robustesse | age null, slug inconnu, JSON invalide, events vide, scores négatifs, mergeConfig deep merge, min_sessions depuis config, normalizeRawEvents tout invalide |

---

## Ce que les tests ne couvrent PAS (scope hors-test unitaire)

- `loadConfig()` + override → test d'intégration avec le FS (fichiers réels)
- `analyzeChildPeriod()` complet avec fetchSessions → test d'intégration avec DB
- `runPeriodAnalysis()` avec des enfants réels → test d'intégration / E2E
- Performance (temps d'exécution) → à ajouter avec k6 ou un bench PHP
