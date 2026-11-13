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
Des mesures en laboratoire font osciller la vitesse d'allongement des racines
([Barlow et al. 2013](https://doi.org/10.1093/aob/mct052), *Annals of Botany* 111:859-872) et les mouvements des feuilles
([Barlow 2015](https://doi.org/10.1093/aob/mcv096), *Annals of Botany* 116:149-187) avec la marée lunisolaire quotidienne
(cycle d'environ 24,8 h) : des corrélations, « provisoires » selon les auteurs, venues d'un même réseau de chercheurs, sans réplication
indépendante connue, et qui ne testent pas les règles des calendriers (phase, lune montante, constellations).

**Et les champignons ?** On dit qu'ils sortent davantage autour de la pleine lune. En Suisse, 1 715 relevés datés sur cinq placettes
forestières suivies de 1990 à 2007 n'ont montré aucun lien entre la phase et l'apparition des champignons sauvages : un mythe, pour les
auteurs ([Egli, Ayer & Merlini 2011](https://sydowia.at/syd63-1/T3-Swiss.html), *Sydowia* 63:23-33).

**Et la déclinaison très forte de ces années-ci ?** Selon un cycle de 18,6 ans, la Lune monte et descend chaque mois plus ou
moins loin dans le ciel : vers ±28,5° de déclinaison au « grand arrêt lunaire » de 2024-2025 (encore ±27° et plus en 2026), ±18°
seulement vers 2034. Lune montante et descendante gardent le même rythme (environ 13,6 jours chacune) : le plugin les calcule
d'après le sens de la déclinaison, ses dates et ses conseils ne changent donc pas, l'écart de hauteur est seulement plus marqué.
Les pleines lunes d'hiver passent très haut et éclairent la nuit plus longtemps, celles d'été restent basses ; aucune étude ne montre
d'effet de ce cycle sur les cultures.

> La tuile est dessinée par le plugin : laissez « Widget du plugin » coché dans la configuration avancée de l’équipement, sinon elle reste vide.
