# Sélénoculture

Jardiner avec la Lune, sans service extérieur : la position de la Lune est calculée sur la box chaque heure.

## Ce que fait le plugin
- **Tuile** : phase dessinée, illumination, lune montante ou descendante, type de jour (racine, feuille, fleur, fruit),
  alerte les jours à éviter (nœud lunaire, périgée, apogée, ± 6 h), les 7 prochains jours, les préconisations du jour.
- **Lune du jour** (clic sur la Lune de la tuile) : image de la Lune pour l'heure en cours, rendue par la NASA
  (Dial-A-Moon, Scientific Visualization Studio, d'après la sonde LRO : vue calculée, pas une photo), et ses paramètres,
  ceux du plugin à côté de ceux de la NASA. Seul accès Internet du plugin : la box interroge svs.gsfc.nasa.gov au plus
  une fois par heure, à l'ouverture de l'onglet ; l'image elle-même est chargée par le navigateur depuis ce site.
  En début d'année, tant que la NASA n'a pas publié l'année nouvelle, l'image est indisponible.
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
et les nœuds, 0,02° sur la longitude. Les phases principales (nouvelle lune, quartiers, pleine lune) ne sont affichées que le jour même ;
l'âge est compté depuis la dernière nouvelle lune. `sudo -u www-data php tests/selftest.php reseau` refait la comparaison à l'USNO.

La sélénoculture est une tradition : aucun effet solide de la Lune sur les cultures n'a été démontré.

> La tuile est dessinée par le plugin : laissez « Widget du plugin » coché dans la configuration avancée de l’équipement, sinon elle reste vide.
