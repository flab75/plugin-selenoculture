<?php
/* Position de la Lune et du Soleil, calcul local (pas de réseau).
   Lune : J. Meeus, « Astronomical Algorithms », chap. 47, termes principaux (≈ 0,05° en longitude, ≈ 50 km en distance) ;
   Soleil : chap. 25, précision faible (≈ 0,01°). Vérifié contre JPL Horizons et l'USNO (tests/selftest.php).
   ponytail: termes tronqués ; la table complète (60 + 60 termes) si un jour on vise la seconde d'arc. */
class selenoculture_lune {
  // D, M, M', F, longitude (1e-6 °), distance (1e-3 km)
  const LR = [
    [0, 0, 1, 0, 6288774, -20905355], [2, 0, -1, 0, 1274027, -3699111], [2, 0, 0, 0, 658314, -2955968], [0, 0, 2, 0, 213618, -569925],
    [0, 1, 0, 0, -185116, 48888], [0, 0, 0, 2, -114332, -3149], [2, 0, -2, 0, 58793, 246158], [2, -1, -1, 0, 57066, -152138],
    [2, 0, 1, 0, 53322, -170733], [2, -1, 0, 0, 45758, -204586], [0, 1, -1, 0, -40923, -129620], [1, 0, 0, 0, -34720, 108743],
    [0, 1, 1, 0, -30383, 104755], [2, 0, 0, -2, 15327, 10321], [0, 0, 1, 2, -12528, 0], [0, 0, 1, -2, 10980, 79661],
    [4, 0, -1, 0, 10675, -34782], [0, 0, 3, 0, 10034, -23210], [4, 0, -2, 0, 8548, -21636], [2, 1, -1, 0, -7888, 24208],
    [2, 1, 0, 0, -6766, 30824], [1, 0, -1, 0, -5163, -8379], [1, 1, 0, 0, 4987, -16675], [2, -1, 1, 0, 4036, -12831],
    [2, 0, 2, 0, 3994, -10445], [4, 0, 0, 0, 3861, -11650], [2, 0, -3, 0, 3665, 14403], [0, 1, -2, 0, -2689, -7003],
    [2, 0, -1, 2, -2602, 0], [2, -1, -2, 0, 2390, 10056], [1, 0, 1, 0, -2348, 6322], [2, -2, 0, 0, 2236, -9884],
  ];
  // D, M, M', F, latitude (1e-6 °)
  const B = [
    [0, 0, 0, 1, 5128122], [0, 0, 1, 1, 280602], [0, 0, 1, -1, 277693], [2, 0, 0, -1, 173237], [2, 0, -1, 1, 55413],
    [2, 0, -1, -1, 46271], [2, 0, 0, 1, 32573], [0, 0, 2, 1, 17198], [2, 0, 1, -1, 9266], [0, 0, 2, -1, 8822],
    [2, -1, 0, -1, 8216], [2, 0, -2, -1, 4324], [2, 0, 1, 1, 4200], [2, 1, 0, -1, -3359], [2, -1, -1, 1, 2463],
    [2, -1, 0, 1, 2211], [2, -1, -1, -1, 2065], [0, 1, -1, -1, -1870], [4, 0, -1, -1, 1828], [0, 1, 0, 1, -1794],
  ];
  /* Constellations du zodiaque (calendrier biodynamique, M. Thun) : longitude J2000 d'entrée => [nom, élément].
     usuel : limites des calendriers lunaires courants, retrouvées sur jardiner-malin.fr (160 changements d'oct. 2026 à sept. 2027,
     écart < 0,01° par limite) ; uai : limites astronomiques de l'UAI sur l'écliptique, Serpentaire compté avec le Scorpion.
     Les deux diffèrent de 0 à 6,6° selon le signe, soit jusqu'à 12 h sur l'heure du changement. */
  const CONST = [
    'usuel' => [
      [29.23, 'Bélier', 'fruit'], [57.73, 'Taureau', 'racine'], [96.72, 'Gémeaux', 'fleur'], [114.23, 'Cancer', 'feuille'],
      [135.23, 'Lion', 'fruit'], [174.23, 'Vierge', 'racine'], [219.43, 'Balance', 'fleur'], [235.53, 'Scorpion', 'feuille'],
      [266.33, 'Sagittaire', 'fruit'], [295.83, 'Capricorne', 'racine'], [327.13, 'Verseau', 'fleur'], [353.83, 'Poissons', 'feuille'],
    ],
    'uai' => [
      [28.69, 'Bélier', 'fruit'], [53.42, 'Taureau', 'racine'], [90.14, 'Gémeaux', 'fleur'], [118.25, 'Cancer', 'feuille'],
      [138.17, 'Lion', 'fruit'], [174.15, 'Vierge', 'racine'], [217.80, 'Balance', 'fleur'], [241.13, 'Scorpion', 'feuille'],
      [266.29, 'Sagittaire', 'fruit'], [299.71, 'Capricorne', 'racine'], [327.89, 'Verseau', 'fleur'], [351.57, 'Poissons', 'feuille'],
    ],
  ];
  const SYNODIQUE = 29.530588853;

  private static function norm($a) { $a = fmod($a, 360); return $a < 0 ? $a + 360 : $a; }

  // siècles juliens depuis J2000 (temps terrestre ≈ UTC + 69 s)
  public static function siecles($ts) { return (($ts + 69) / 86400 + 2440587.5 - 2451545.0) / 36525; }

  /* Position à l'instant $ts (timestamp Unix) :
     lon / lat écliptiques de la date (°), dist (km), dec (déclinaison °), elong (Lune − Soleil, 0-360°), illum (0-1),
     lonJ2000 (pour les constellations). */
  public static function position($ts) {
    $T = self::siecles($ts); $r = M_PI / 180;
    $Lp = self::norm(218.3164477 + 481267.88123421 * $T);
    $D = self::norm(297.8501921 + 445267.1114034 * $T);
    $M = self::norm(357.5291092 + 35999.0502909 * $T);
    $Mp = self::norm(134.9633964 + 477198.8675055 * $T);
    $F = self::norm(93.2720950 + 483202.0175233 * $T);
    $A1 = 119.75 + 131.849 * $T; $A2 = 53.09 + 479264.290 * $T; $A3 = 313.45 + 481266.484 * $T;
    $E = 1 - 0.002516 * $T;
    $sl = 0; $sr = 0; $sb = 0;
    foreach (self::LR as [$d, $m, $mp, $f, $l, $dist]) {
      $a = ($d * $D + $m * $M + $mp * $Mp + $f * $F) * $r; $e = abs($m) == 1 ? $E : (abs($m) == 2 ? $E * $E : 1);
      $sl += $l * $e * sin($a); $sr += $dist * $e * cos($a);
    }
    foreach (self::B as [$d, $m, $mp, $f, $b]) {
      $e = abs($m) == 1 ? $E : (abs($m) == 2 ? $E * $E : 1);
      $sb += $b * $e * sin(($d * $D + $m * $M + $mp * $Mp + $f * $F) * $r);
    }
    $sl += 3958 * sin($A1 * $r) + 1962 * sin(($Lp - $F) * $r) + 318 * sin($A2 * $r);
    $sb += -2235 * sin($Lp * $r) + 382 * sin($A3 * $r) + 175 * sin(($A1 - $F) * $r) + 175 * sin(($A1 + $F) * $r)
      + 127 * sin(($Lp - $Mp) * $r) - 115 * sin(($Lp + $Mp) * $r);
    $lon = self::norm($Lp + $sl / 1e6); $lat = $sb / 1e6; $dist = 385000.56 + $sr / 1000;
    // Soleil (longitude vraie)
    $L0 = 280.46646 + 36000.76983 * $T;
    $C = (1.914602 - 0.004817 * $T) * sin($M * $r) + 0.019993 * sin(2 * $M * $r) + 0.000289 * sin(3 * $M * $r);
    $soleil = self::norm($L0 + $C);
    $eps = (23.4392911 - 0.0130042 * $T) * $r;
    $dec = asin(sin($lat * $r) * cos($eps) + cos($lat * $r) * sin($eps) * sin($lon * $r)) / $r;
    $elong = self::norm($lon - $soleil);
    $cosPsi = cos($lat * $r) * cos($elong * $r); // angle Soleil-Terre-Lune
    return ['lon' => $lon, 'lat' => $lat, 'dist' => $dist, 'dec' => $dec, 'elong' => $elong, 'illum' => (1 - $cosPsi) / 2,
      'lonJ2000' => self::norm($lon - 1.396971 * $T)]; // précession générale depuis J2000
  }

  public static function constellation($lonJ2000, $conv = 'usuel') { // [nom, élément]
    $l = self::CONST[$conv] ?? self::CONST['usuel'];
    $c = $l[count($l) - 1]; // avant la 1re limite : Poissons
    foreach ($l as $x) if ($lonJ2000 >= $x[0]) $c = $x;
    return [$c[1], $c[2]];
  }

  public static function phaseNom($elong) {
    $n = ['Nouvelle lune', 'Premier croissant', 'Premier quartier', 'Gibbeuse croissante', 'Pleine lune', 'Gibbeuse décroissante', 'Dernier quartier', 'Dernier croissant'];
    return $n[(int)floor(self::norm($elong + 22.5) / 45) % 8];
  }

  /* Instants des évènements entre $deb et $fin (pas d'une heure, puis dichotomie à la minute) :
     phase (0/90/180/270), noeud (latitude nulle : asc/desc), perigee / apogee, montante / descendante (début, = extrême de déclinaison),
     constellation (changement). Renvoie [[ts, type, détail], …] triés. */
  public static function evenements($deb, $fin, $conv = 'usuel') {
    $ev = []; $pas = 3600;
    $p0 = self::position($deb); $t0 = $deb;
    $dec0 = $p0['dec'] - self::position($deb - $pas)['dec']; // tendance de la déclinaison
    $dist0 = $p0['dist'] - self::position($deb - $pas)['dist'];
    for ($t = $deb + $pas; $t <= $fin; $t += $pas) {
      $p = self::position($t);
      foreach ([0, 90, 180, 270] as $q) { // passage de l'élongation par q
        $a = self::norm($p0['elong'] - $q + 180) - 180; $b = self::norm($p['elong'] - $q + 180) - 180;
        if ($a < 0 && $b >= 0) $ev[] = [self::affiner($t0, $t, function ($x) use ($q) { return self::norm(self::position($x)['elong'] - $q + 180) - 180; }), 'phase', $q];
      }
      if (($p0['lat'] < 0) != ($p['lat'] < 0)) $ev[] = [self::affiner($t0, $t, function ($x) { return self::position($x)['lat']; }), 'noeud', $p['lat'] >= 0 ? 'ascendant' : 'descendant'];
      $dd = $p['dec'] - $p0['dec']; $dr = $p['dist'] - $p0['dist'];
      if (($dd > 0) != ($dec0 > 0)) $ev[] = [$t0, $dd > 0 ? 'montante' : 'descendante', round($p0['dec'], 1)]; // ± 1 h suffit
      if (($dr > 0) != ($dist0 > 0)) $ev[] = [$t0, $dr > 0 ? 'perigee' : 'apogee', round($p0['dist'])];
      $c0 = self::constellation($p0['lonJ2000'], $conv); $c = self::constellation($p['lonJ2000'], $conv);
      if ($c0[0] != $c[0]) $ev[] = [self::affiner($t0, $t, function ($x) use ($c, $conv) {
        return self::constellation(self::position($x)['lonJ2000'], $conv)[0] == $c[0] ? 1 : -1; }), 'constellation', $c[0] . '|' . $c[1]];
      $p0 = $p; $t0 = $t; $dec0 = $dd; $dist0 = $dr;
    }
    usort($ev, function ($a, $b) { return $a[0] <=> $b[0]; });
    return $ev;
  }

  private static function affiner($a, $b, $f) { // f(a) < 0 ≤ f(b) : dichotomie jusqu'à la minute
    while ($b - $a > 60) { $m = (int)(($a + $b) / 2); if ($f($m) < 0) $a = $m; else $b = $m; }
    return $b;
  }

  // État complet à l'instant $ts (sans évènements)
  public static function etat($ts, $conv = 'usuel') {
    $p = self::position($ts); $av = self::position($ts - 3600);
    [$cons, $elem] = self::constellation($p['lonJ2000'], $conv);
    return $p + ['age' => $p['elong'] / 360 * self::SYNODIQUE, 'croissante' => $p['elong'] < 180, 'montante' => $p['dec'] > $av['dec'],
      'constellation' => $cons, 'element' => $elem, 'phase' => self::phaseNom($p['elong'])];
  }
}
