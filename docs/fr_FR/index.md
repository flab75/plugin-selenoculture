# Sélénoculture

Jardiner avec la Lune, sans service extérieur : la position de la Lune est calculée sur la box chaque heure.

## Ce que fait le plugin
- **Tuile** : phase dessinée, illumination, lune montante ou descendante, type de jour (racine, feuille, fleur, fruit),
  alerte les jours à éviter (nœud lunaire, périgée, apogée, ± 6 h), les 7 prochains jours, les préconisations du jour.
- **Calendrier** sur 30 jours (bouton « Calendrier ») avec les heures des changements.
- **Journal** des travaux (bouton « ＋ Noter », administrateurs) : date, lieu, travail, plante, catégorie, note ;
  la lune du jour est notée avec. Les semis de 3 à 6 semaines non repiqués sont rappelés dans les préconisations.
- **Explications** (bouton « ? ») : principes, limites, liens.
- **Commandes historisées** : phase, illumination, âge, croissante, montante, constellation, type de jour, distance,
  déclinaison, jour à éviter et raison, prochain évènement, conseil du jour, dernier travail noté.

## Configuration de l'équipement
- **Limites des constellations** : « calendriers usuels » (par défaut, mêmes heures que jardiner-malin.fr) ou
  « astronomiques » (limites officielles de l'UAI). Les calendriers publiés diffèrent jusqu'à 12 h sur l'heure d'un changement.
- **Température minimale prévue** et **risque de pluie** : commandes d'un plugin météo (facultatif) ; gel annoncé ≤ 0 °C,
  pluie ≥ 70 %.
- **Abris** : une ligne par serre ou châssis, `Nom | commande température | commande humidité`.
  Aérer au-dessus de 25 °C ou 85 % d'humidité, protéger sous 3 °C. Une mesure muette depuis 12 h est ignorée.

## Précision
Algorithmes de J. Meeus (termes principaux) : comparé au JPL (Horizons) et à l'USNO, quelques minutes sur les phases
et les nœuds, 0,02° sur la longitude. `sudo -u www-data php tests/selftest.php reseau` refait la comparaison à l'USNO.

La sélénoculture est une tradition : aucun effet solide de la Lune sur les cultures n'a été démontré.

> La tuile est dessinée par le plugin : laissez « Widget du plugin » coché dans la configuration avancée de l’équipement, sinon elle reste vide.
