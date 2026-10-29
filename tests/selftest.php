<?php
/* Contrôles sans effet sur la box : calcul lunaire (repères connus), conventions, préconisations, journal.
   Usage : sudo -u www-data php tests/selftest.php [reseau] (reseau = compare aussi à l'USNO). */
require_once __DIR__ . '/../core/class/selenoculture.class.php';
$ok = function ($v, $m) { if (!$v) { echo "ECHEC : $m\n"; exit(1); } }; // assert() est désactivé en production
$L = 'selenoculture_lune';

// repères publiés (USNO, jardiner-malin.fr), en UTC
$ev = $L::evenements(gmmktime(0, 0, 0, 10, 20, 2026), gmmktime(0, 0, 0, 12, 1, 2026));
$cherche = function ($type, $det, $ts) use ($ev) { foreach ($ev as $e) if ($e[1] == $type && $e[2] == $det && abs($e[0] - $ts) < 1800) return true; return false; };
$ok($cherche('phase', 0, gmmktime(7, 2, 0, 11, 9, 2026)), 'nouvelle lune du 9/11/2026 07:02 UTC');
$ok($cherche('phase', 180, gmmktime(4, 13, 0, 10, 26, 2026)), "pleine lune du 26/10/2026 04:13 UTC");
$ok($cherche('noeud', 'ascendant', gmmktime(11, 45, 0, 11, 17, 2026)), 'nœud ascendant du 17/11/2026 11:45 UTC');
$ok($cherche('constellation', 'Vierge|racine', gmmktime(4, 44, 0, 11, 5, 2026)), 'jour racine du 5/11/2026 04:44 UTC (usuel)');
$ok($cherche('constellation', 'Balance|fleur', gmmktime(17, 21, 0, 11, 8, 2026)), 'jour fleur du 8/11/2026 17:21 UTC (usuel)');
$ok($L::constellation(220, 'uai')[0] == 'Balance' && $L::constellation(218, 'usuel')[0] == 'Vierge' && $L::constellation(10)[0] == 'Poissons', 'limites usuel / uai');
$p = $L::position(gmmktime(4, 13, 0, 10, 26, 2026));
$ok($p['illum'] > 0.99 && abs($p['elong'] - 180) < 0.1, 'pleine lune éclairée');
echo "calcul OK\n";

if (($argv[1] ?? '') == 'reseau') { // phases de l'année : écart à l'USNO
  $r = json_decode((new com_http('https://aa.usno.navy.mil/api/moon/phases/date?date=' . date('Y-m-d') . '&nump=24'))->exec(15, 1), true);
  $q = ['New Moon' => 0, 'First Quarter' => 90, 'Full Moon' => 180, 'Last Quarter' => 270]; $max = 0;
  $ev = $L::evenements(time() - 86400, time() + 380 * 86400);
  foreach ($r['phasedata'] ?? [] as $x) {
    $t = gmmktime(...array_map('intval', array_merge(explode(':', $x['time']), [0, $x['month'], $x['day'], $x['year']])));
    $d = 1e9; foreach ($ev as $e) if ($e[1] == 'phase' && $e[2] == $q[$x['phase']]) $d = min($d, abs($e[0] - $t));
    $max = max($max, $d);
  }
  $ok(!empty($r['phasedata']) && $max <= 300, 'USNO : écart max ' . $max . ' s');
  echo 'USNO OK (' . count($r['phasedata']) . " phases, écart max $max s)\n";
}

$C = 'selenoculture_conseil';
$etat = $L::etat(time());
$c = $C::conseils(['etat' => ['montante' => true, 'element' => 'fruit'] + $etat, 'abstention' => '', 'mois' => 11, 'tmin' => -2, 'pluie' => 80,
  'abris' => [['Serre', 28, 90], ['Boîte', null, null]], 'semis' => [['tomates', 25, 'fruit']], 'prochains' => ['planter' => ['fruit' => 'lun. 3/11']]]);
$n = array_count_values(array_column($c, 0));
$ok($c[0][0] == 'alerte' && $n['alerte'] == 2 && $n['abri'] == 2 && $n['journal'] == 1 && $n['saison'] == 2, 'conseils : ' . json_encode($n));
$ok(strpos($c[1][1], 'montante') !== false || strpos($c[1][1], 'Lune') !== false, 'conseil lunaire');
$c = $C::conseils(['etat' => $etat, 'abstention' => 'Nœud ascendant 11:45', 'mois' => 1]);
$ok(strpos($c[0][1], 'Nœud') === 0 && count($c) == 3, 'abstention');
$j = [['date' => '2026-10-01', 'action' => 'Semis', 'plante' => 'Laitue', 'cat' => 'feuille'], ['date' => '2026-10-01', 'action' => 'Semis', 'plante' => 'Radis'],
  ['date' => '2026-10-20', 'action' => 'Repiquage', 'plante' => 'radis']];
$ok($C::semis($j, strtotime('2026-10-29')) == [['Laitue', 28, 'feuille']], 'semis à repiquer');
$cal = selenoculture::calendrier(time(), 30);
$ok(count($cal) == 30 && isset($C::ELEMENTS[$cal[0]['el']]), 'calendrier');
$pr = $C::prochains($cal);
$ok(isset($pr['semer']['']) && isset($pr['planter']['']), 'prochains jours');
$ok(selenoculture::texte(" Semis \u{1F345} si <5 °C ", 80) === 'Semis  si ‹5 °C' && selenoculture::texte(['x'], 9) === '' && mb_strlen(selenoculture::texte(str_repeat('é', 99), 60)) == 60, 'texte saisi');
$ok($C::semis([['date' => '2026-02-25', 'action' => 'Semis', 'plante' => 'X']], strtotime('2026-03-30 00:30')) == [['X', 33, '']], 'semis : jours de calendrier au passage à l\'heure d\'été');
echo "conseils OK\n";

foreach (eqLogic::byType('selenoculture') as $eq) {
  $d = $eq->donnees();
  $ok(json_encode($d, JSON_HEX_TAG) !== false && count($d['cal']) == 7, 'données de ' . $eq->getHumanName());
  echo 'données OK : ' . $eq->getHumanName() . ' (' . count($d['conseils']) . " conseils)\n";
}
echo "TOUT OK\n";
