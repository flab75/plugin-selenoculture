<?php
/* Préconisations : fonctions pures (pas de base, pas de réseau), testées par tests/selftest.php.
   Textes écrits pour ce plugin (règles usuelles de la sélénoculture résumées, pas de copie des sites cités). */
class selenoculture_conseil {
  const ELEMENTS = ['racine' => 'Racine', 'feuille' => 'Feuille', 'fleur' => 'Fleur', 'fruit' => 'Fruit'];
  const PLANTES = [
    'racine' => 'carottes, radis, betteraves, navets, pommes de terre, ail, oignons, échalotes',
    'feuille' => 'salades, épinards, mâche, choux, poireaux, blettes, aromatiques à feuilles',
    'fleur' => 'brocolis, choux-fleurs, artichauts, fleurs et bulbes à fleurs, plantes mellifères',
    'fruit' => 'tomates, courges, haricots, pois, fraisiers, agrumes et arbres fruitiers',
  ];
  const ACTIONS = ['Semis', 'Repiquage', 'Plantation', 'Taille', 'Greffe', 'Récolte', 'Arrosage', 'Traitement', 'Travail du sol', 'Autre'];
  // ponytail: travaux du mois pour un climat tempéré du nord de la France ; table par région si un jour le plugin est partagé
  const MOIS = [
    1 => ['Jardin : tailler les fruitiers à pépins hors gel, préparer les commandes de graines.', 'Serres : aérer les jours doux, semer poireaux et oignons au chaud.'],
    2 => ['Jardin : finir la taille d\'hiver, épandre le compost mûr.', 'Serres : semer tomates, piments et aubergines au chaud, pommes de terre à germer.'],
    3 => ['Jardin : semer pois, fèves, radis, carottes hâtives ; planter ail et échalotes.', 'Serres : repiquer les semis, semer salades et choux.'],
    4 => ['Jardin : planter les pommes de terre, semer betteraves et salades.', 'Serres : endurcir les plants, ombrer et aérer quand le soleil chauffe.'],
    5 => ['Jardin : après les saints de glace, planter tomates, courges et haricots.', 'Serres : sortir les agrumes, surveiller les températures de l\'après-midi.'],
    6 => ['Jardin : pailler, arroser le soir, semer carottes et haricots d\'été.', 'Serres : ombrer, ouvrir en grand, arroser régulièrement.'],
    7 => ['Jardin : récolter, semer navets et choux d\'hiver, tailler en vert.', 'Serres : aérer jour et nuit, surveiller l\'oïdium.'],
    8 => ['Jardin : semer mâche, épinards, radis d\'hiver ; récolter les graines.', 'Serres : semer les salades d\'hiver, nettoyer ce qui a fini.'],
    9 => ['Jardin : planter fraisiers, choux ; récolter courges et pommes de terre.', 'Serres : semer mâche et laitues d\'hiver, réduire l\'arrosage.'],
    10 => ['Jardin : planter bulbes, arbres et arbustes, semer les engrais verts.', 'Serres : rentrer les agrumes avant les premières gelées, aérer les jours de soleil.'],
    11 => ['Jardin : planter les arbres à racines nues, pailler, protéger du gel.', 'Serres : hors gel pour les agrumes, arrosage réduit, aérer si l\'humidité monte.'],
    12 => ['Jardin : bêcher les sols lourds hors gel, entretenir les outils.', 'Serres : surveiller le gel la nuit, nettoyer les vitres pour la lumière.'],
  ];

  /* Conseils du jour, du plus important au moins important : [niveau (alerte|lune|abri|journal|saison), texte].
     $c = ['etat' => selenoculture_lune::etat, 'abstention' => '' ou raison, 'mois' => 1-12,
           'tmin' => °C prévue ou null, 'pluie' => % ou null, 'abris' => [[nom, t, h], …], 'semis' => [[plante, jours, élément], …],
           'prochains' => ['planter' => [élément => date], 'semer' => [élément => date]]] */
  public static function conseils(array $c) {
    $l = []; $e = $c['etat'];
    if (($c['tmin'] ?? null) !== null && $c['tmin'] <= 0) $l[] = ['alerte', sprintf(__('Gel annoncé (%s °C) : pas de semis ni de plantation dehors, protéger les plants, fermer les serres.', __FILE__), round($c['tmin'], 1))];
    if ($c['abstention'] != '') $l[] = ['alerte', sprintf(__('%s : la tradition conseille de ne pas semer, planter ni tailler aujourd\'hui, tenez-vous-en à l\'entretien.', __FILE__), $c['abstention'])];
    else {
      $el = __(self::ELEMENTS[$e['element']], __FILE__); $pl = __(self::PLANTES[$e['element']], __FILE__);
      $l[] = ['lune', $e['montante']
        ? sprintf(__('Lune montante, jour %s : semer, greffer, récolter (%s).', __FILE__), strtolower($el), $pl)
        : sprintf(__('Lune descendante, jour %s : planter, repiquer, tailler, travailler le sol (%s).', __FILE__), strtolower($el), $pl)];
    }
    if (($c['pluie'] ?? null) !== null && $c['pluie'] >= 70) $l[] = ['alerte', sprintf(__('Pluie probable (%s %%) : arrosage inutile, reporter les traitements.', __FILE__), round($c['pluie']))];
    foreach ($c['abris'] ?? [] as [$nom, $t, $h]) {
      if ($t !== null && $t > 25) $l[] = ['abri', sprintf(__('%s : %s °C, aérer.', __FILE__), $nom, round($t, 1))];
      elseif ($t !== null && $t < 3) $l[] = ['abri', sprintf(__('%s : %s °C, fermer et protéger du froid.', __FILE__), $nom, round($t, 1))];
      if ($h !== null && $h > 85) $l[] = ['abri', sprintf(__('%s : humidité %s %%, aérer pour éviter les maladies.', __FILE__), $nom, round($h))];
    }
    foreach ($c['semis'] ?? [] as [$plante, $jours, $el]) {
      $quand = $c['prochains']['planter'][$el ?: ''] ?? $c['prochains']['planter'][''] ?? '';
      $l[] = ['journal', sprintf(__('Semis de %s il y a %s jours : à repiquer', __FILE__), $plante, $jours) . ($quand ? ' (' . sprintf(__('prochain bon jour : %s', __FILE__), $quand) . ')' : '') . '.'];
    }
    foreach (self::MOIS[$c['mois']] as $t) $l[] = ['saison', __($t, __FILE__)];
    return $l;
  }

  /* Semis du journal encore à repiquer : semés il y a 21 à 42 jours, sans repiquage ni plantation de la même plante depuis.
     $journal = [['date' => 'Y-m-d', 'action' => …, 'plante' => …, 'cat' => élément], …] */
  public static function semis(array $journal, $ts) {
    $r = [];
    foreach ($journal as $s) {
      if (($s['action'] ?? '') != 'Semis' || ($s['plante'] ?? '') === '') continue;
      $j = (int)date_diff(date_create($s['date']), date_create(date('Y-m-d', $ts)))->format('%r%a'); // en jours de calendrier, pas en 86400 s
      if ($j < 21 || $j > 42) continue;
      foreach ($journal as $x) if (in_array($x['action'] ?? '', ['Repiquage', 'Plantation']) && mb_strtolower($x['plante'] ?? '') == mb_strtolower($s['plante']) && $x['date'] >= $s['date']) continue 2;
      $r[] = [$s['plante'], $j, $s['cat'] ?? ''];
    }
    return $r;
  }

  /* Prochain jour favorable par élément, à partir du calendrier (selenoculture::calendrier) :
     ['semer' => [élément => 'D j/m'], 'planter' => […]] ; clé '' = n'importe quel élément. Jours d'abstention écartés. */
  public static function prochains(array $cal) {
    $r = ['semer' => [], 'planter' => []];
    foreach ($cal as $d) {
      if ($d['abst']) continue;
      $k = $d['mont'] ? 'semer' : 'planter';
      $r[$k][$d['el']] ??= $d['nom'];
      $r[$k][''] ??= $d['nom'];
    }
    return $r;
  }
}
