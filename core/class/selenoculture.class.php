<?php
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/selenoculture_lune.class.php';
require_once __DIR__ . '/selenoculture_conseil.class.php';

/* Sélénoculture : position de la Lune calculée sur la box (pas de réseau), commandes historisées chaque heure,
   journal des travaux (configuration de l'équipement), préconisations lune + saison + météo + abris + journal. */
class selenoculture extends eqLogic {
  const ABST = 6 * 3600; // ± autour d'un nœud, du périgée ou de l'apogée
  const JOURS = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];

  public static function cronHourly() {
    foreach (self::byType('selenoculture', true) as $eq) {
      try { $eq->maj(); } catch (Throwable $e) { log::add('selenoculture', 'error', $eq->getHumanName() . ' : ' . $e->getMessage()); }
    }
  }

  // commandes info : logicalId => [nom, sous-type, unité] ; toutes historisées sauf conseil et journal (history.value = varchar(127), mode strict)
  private static function commandes() {
    return [
      'phase' => [__('Phase', __FILE__), 'string', ''], 'illum' => [__('Illumination', __FILE__), 'numeric', '%'],
      'age' => [__('Âge', __FILE__), 'numeric', 'j'], 'croissante' => [__('Croissante', __FILE__), 'binary', ''],
      'montante' => [__('Montante', __FILE__), 'binary', ''], 'constellation' => [__('Constellation', __FILE__), 'string', ''],
      'element' => [__('Type de jour', __FILE__), 'string', ''], 'distance' => [__('Distance', __FILE__), 'numeric', 'km'],
      'declinaison' => [__('Déclinaison', __FILE__), 'numeric', '°'], 'abstention' => [__('Jour à éviter', __FILE__), 'binary', ''],
      'raison' => [__('Raison', __FILE__), 'string', ''], 'prochain' => [__('Prochain évènement', __FILE__), 'string', ''],
      'conseil' => [__('Conseil du jour', __FILE__), 'string', ''], 'journal' => [__('Dernier travail noté', __FILE__), 'string', ''],
    ];
  }

  public function postSave() {
    foreach (self::commandes() as $k => [$nom, $sub, $unite]) {
      if (is_object($this->getCmd('info', $k))) continue;
      $c = new selenocultureCmd();
      $c->setEqLogic_id($this->getId()); $c->setLogicalId($k); $c->setName($nom); $c->setType('info'); $c->setSubType($sub);
      $c->setUnite($unite); $c->setIsHistorized(in_array($k, ['conseil', 'journal']) ? 0 : 1); $c->setIsVisible(0); $c->save();
    }
    if ($this->getDisplay('width') == '') { $this->setDisplay('width', '300px'); $this->save(true); } // le contenu ne tient pas dans 230 px
    if (!is_object($this->getCmd('action', 'refresh'))) {
      $c = new selenocultureCmd();
      $c->setEqLogic_id($this->getId()); $c->setLogicalId('refresh'); $c->setName(__('Rafraîchir', __FILE__));
      $c->setType('action'); $c->setSubType('other'); $c->save();
    }
    // commandes remplies tout de suite, sans attendre le cron horaire
    if ($this->getIsEnable()) try { $this->maj(); } catch (Throwable $e) { log::add('selenoculture', 'error', $this->getHumanName() . ' : ' . $e->getMessage()); }
  }

  public function conv() { return $this->getConfiguration('conv', 'usuel') ?: 'usuel'; }

  public static function nomEv($e) { // [ts, type, détail] → texte
    switch ($e[1]) {
      case 'phase': return [0 => __('Nouvelle lune', __FILE__), 90 => __('Premier quartier', __FILE__), 180 => __('Pleine lune', __FILE__), 270 => __('Dernier quartier', __FILE__)][$e[2]];
      case 'noeud': return $e[2] == 'ascendant' ? __('Nœud ascendant', __FILE__) : __('Nœud descendant', __FILE__);
      case 'perigee': return __('Périgée', __FILE__);
      case 'apogee': return __('Apogée', __FILE__);
      case 'montante': return __('Début de lune montante', __FILE__);
      case 'descendante': return __('Début de lune descendante', __FILE__);
      case 'constellation': [$n, $el] = explode('|', $e[2]); return sprintf(__('Lune en %s (jour %s)', __FILE__), $n, strtolower(__(selenoculture_conseil::ELEMENTS[$el], __FILE__)));
    }
    return $e[1];
  }

  /* Calendrier jour par jour (heure locale) à partir d'aujourd'hui :
     [date, nom, el (élément majoritaire), mont (montante à midi), illum, elong (à midi), cons, ev [[H:i, texte]], abst (texte ou '')] */
  public static function calendrier($ts, $jours, $conv = 'usuel', $ev = null) { // $ev : évènements déjà calculés, couvrant au moins la période ± ABST
    $d0 = strtotime('today', $ts);
    $ev = $ev ?? selenoculture_lune::evenements($d0 - self::ABST - 3600, strtotime('+' . $jours . ' days', $d0) + self::ABST, $conv);
    $r = [];
    for ($i = 0; $i < $jours; $i++) {
      $a = strtotime('+' . $i . ' days', $d0); $b = strtotime('+1 day', $a); // jours de 23 ou 25 h au changement d'heure
      $n = [];
      for ($t = $a + 1800; $t < $b; $t += 3600) { $el = selenoculture_lune::etat($t, $conv)['element']; $n[$el] = ($n[$el] ?? 0) + 1; }
      arsort($n);
      $m = selenoculture_lune::etat($a + ($b - $a) / 2, $conv);
      $j = ['date' => date('Y-m-d', $a), 'nom' => __(self::JOURS[date('w', $a)], __FILE__) . ' ' . date('j/m', $a), 'el' => array_key_first($n),
        'mont' => $m['montante'], 'illum' => round($m['illum'] * 100), 'elong' => round($m['elong'], 1), 'cons' => $m['constellation'], 'ev' => [], 'abst' => ''];
      foreach ($ev as $e) {
        if ($e[0] >= $a && $e[0] < $b) $j['ev'][] = [date('H:i', $e[0]), self::nomEv($e)];
        // jour à éviter seulement si la fenêtre de ± 6 h touche la journée de jardinage (6 h - 21 h), pas pour une heure de nuit
        if ($j['abst'] == '' && in_array($e[1], ['noeud', 'perigee', 'apogee']) && $e[0] + self::ABST > strtotime('06:00', $a) && $e[0] - self::ABST < strtotime('21:00', $a))
          $j['abst'] = self::nomEv($e) . ' ' . date($e[0] >= $a && $e[0] < $b ? 'H:i' : 'j/m H:i', $e[0]) . ' (± 6 h)';
      }
      $r[] = $j;
    }
    return $r;
  }

  private static function lire($s, $h = 12) { // '#id#' ou '#[Objet][Équipement][Commande]#' d'une commande info → valeur numérique, null si absente ou muette depuis $h heures
    if (!preg_match('/^#(\d+)#$/', trim(cmd::humanReadableToCmd((string)$s)), $m)) return null;
    $c = cmd::byId($m[1]);
    if (!is_object($c) || $c->getType() != 'info') return null;
    $v = $c->execCmd();
    if (!is_numeric($v) || strtotime($c->getCollectDate()) < time() - $h * 3600) return null;
    return (float)$v;
  }

  public function abris() { // configuration « Nom | #t# | #h# » par ligne → [[nom, t, h], …]
    $r = [];
    foreach (preg_split('/\R/', (string)$this->getConfiguration('abris', '')) as $l) {
      $p = array_map('trim', explode('|', $l));
      if ($p[0] !== '') $r[] = [$p[0], self::lire($p[1] ?? ''), self::lire($p[2] ?? '')];
    }
    return $r;
  }

  public function journal() { $j = $this->getConfiguration('journal', []); return is_array($j) ? $j : []; }

  // tout ce qu'affiche la tuile, calculé maintenant
  public function donnees($ts = null) {
    $ts = $ts ?? time(); $conv = $this->conv();
    $etat = selenoculture_lune::etat($ts, $conv);
    $d0 = strtotime('today', $ts);
    $ev = selenoculture_lune::evenements($d0 - self::ABST - 3600, $d0 + 31 * 86400, $conv);
    $cal = self::calendrier($ts, 14, $conv, $ev);
    $raison = $cal[0]['abst']; // même règle que la case du jour (fenêtre ± 6 h sur 06:00-21:00)
    $prochains = selenoculture_conseil::prochains($cal); $abris = $this->abris();
    $prochain = '';
    foreach ($ev as $e) if ($e[0] > $ts && !in_array($e[1], ['montante', 'descendante', 'constellation'])) {
      $prochain = self::nomEv($e) . ' : ' . __(self::JOURS[date('w', $e[0])], __FILE__) . ' ' . date('j/m H:i', $e[0]); break;
    }
    $conseils = selenoculture_conseil::conseils(['etat' => $etat, 'abstention' => $raison, 'mois' => (int)date('n', $ts),
      'tmin' => self::lire($this->getConfiguration('tmin'), 36), 'pluie' => self::lire($this->getConfiguration('pluie'), 36), // une prévision peut rester identique longtemps 'abris' => $abris,
      'semis' => selenoculture_conseil::semis($this->journal(), $ts), 'prochains' => $prochains]);
    $lieux = array_merge([__('Jardin', __FILE__), __('Potager', __FILE__), __('Verger', __FILE__)], array_column($abris, 0));
    return ['id' => $this->getId(), 'etat' => $etat, 'raison' => $raison, 'prochain' => $prochain, 'cal' => array_slice($cal, 0, 7),
      'conseils' => $conseils, 'lieux' => $lieux, 'admin' => isConnect('admin') ? 1 : 0,
      'actions' => array_map(function ($a) { return __($a, __FILE__); }, selenoculture_conseil::ACTIONS), 'actionsCle' => selenoculture_conseil::ACTIONS];
  }

  public function maj() {
    $d = $this->donnees();
    $e = $d['etat'];
    $v = ['phase' => $e['phase'], 'illum' => round($e['illum'] * 100, 1), 'age' => round($e['age'], 1), 'croissante' => $e['croissante'] ? 1 : 0,
      'montante' => $e['montante'] ? 1 : 0, 'constellation' => $e['constellation'], 'element' => __(selenoculture_conseil::ELEMENTS[$e['element']], __FILE__),
      'distance' => round($e['dist']), 'declinaison' => round($e['dec'], 2), 'abstention' => $d['raison'] != '' ? 1 : 0, 'raison' => $d['raison'],
      'prochain' => $d['prochain'], 'conseil' => $d['conseils'][0][1] ?? ''];
    foreach ($v as $k => $x) {
      if ($x === '' && is_object($c = $this->getCmd('info', $k)) && $c->execCmd() === '') continue; // pas d'évènement vide chaque heure (Raison)
      $this->checkAndUpdateCmd($k, $x);
    }
    $this->refreshWidget(); // la tuile n'a pas de commande affichée : sans ça, elle garde l'état du chargement
  }

  // texte libre saisi : pas d'émoji (base en utf8mb3, un caractère de 4 octets ferait échouer l'enregistrement), < > neutralisés, longueur bornée
  public static function texte($v, $n) {
    $s = is_scalar($v) ? (string)$v : '';
    return mb_substr(trim(strtr(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $s) ?? '', ['<' => '‹', '>' => '›'])), 0, $n);
  }

  // ajoute un travail au journal, avec la lune de ce jour-là ; renvoie l'entrée
  public function ajouter(array $in) {
    $date = (string)($in['date'] ?? '');
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) throw new Exception(__('Date invalide', __FILE__));
    if (!in_array($in['action'] ?? '', selenoculture_conseil::ACTIONS, true)) throw new Exception(__('Action inconnue', __FILE__));
    $cat = (string)($in['cat'] ?? '');
    if ($cat !== '' && !isset(selenoculture_conseil::ELEMENTS[$cat])) throw new Exception(__('Catégorie inconnue', __FILE__));
    $txt = function ($k, $n) use ($in) { return self::texte($in[$k] ?? '', $n); };
    $l = selenoculture_lune::etat(strtotime($date . ' 12:00'), $this->conv());
    $x = ['id' => uniqid(), 'date' => $date, 'lieu' => $txt('lieu', 60), 'action' => $in['action'], 'plante' => $txt('plante', 80), 'cat' => $cat,
      'note' => $txt('note', 500), 'lune' => ['phase' => $l['phase'], 'element' => $l['element'], 'montante' => $l['montante'] ? 1 : 0]];
    $j = $this->relire()->journal();
    // ponytail: 2000 entrées max dans la configuration ; table dédiée si ça ne suffit pas
    if (count($j) >= 2000) throw new Exception(__('Journal plein (2000 travaux) : supprimez les plus anciens', __FILE__));
    $j[] = $x;
    usort($j, function ($a, $b) { return strcmp($a['date'], $b['date']); });
    $this->enregistrer($j);
    return $x;
  }

  public function supprimer($id) {
    $this->enregistrer(array_values(array_filter($this->relire()->journal(), function ($x) use ($id) { return ($x['id'] ?? '') !== $id; })));
  }

  // dernier état en base : limite la perte d'une saisie faite en même temps ailleurs (ponytail: pas de verrou)
  private function relire() { $e = self::byId($this->getId()); return is_object($e) ? $e : $this; }

  // journal trié par date → configuration, commande « Dernier travail noté » (le plus récent), tuile
  private function enregistrer(array $j) {
    $this->setConfiguration('journal', $j);
    $this->save(true);
    $x = end($j);
    $this->checkAndUpdateCmd('journal', $x ? $x['date'] . ' ' . __($x['action'], __FILE__) . ($x['plante'] !== '' ? ' ' . $x['plante'] : '') . ($x['lieu'] !== '' ? ' (' . $x['lieu'] . ')' : '') : '');
    $this->refreshWidget();
  }

  private static function lib() { // ponytail: bibliothèque recopiée dans chaque tuile (gardée par window.selenoculture)
    static $js = [];
    $l = translate::getLanguage();
    return $js[$l] ??= translate::exec(file_get_contents(__DIR__ . '/../template/selenoculture.js'), 'plugins/selenoculture/core/template/selenoculture.js');
  }

  // ponytail: même gabarit en mobile ; core/template/mobile/tuile.html si la tuile y rend mal
  public function toHtml($_version = 'dashboard') {
    $replace = $this->preToHtml($_version, [], true); // sans cache : préconisations recalculées à chaque affichage
    if (!is_array($replace)) return $replace;
    $json = json_encode($this->donnees(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    $replace['#cmd#'] = '';
    $replace += ['#eqLogic_class#' => 'eqLogic_layout_default', '#background-color#' => ''];
    $replace = array_map(function ($v) { return is_string($v) ? str_replace('@@', '@ @', $v) : $v; }, $replace); // pas de @@LIB@@ venu d'un nom
    $html = template_replace($replace, getTemplate('core', 'dashboard', 'tuile', 'selenoculture'));
    return $this->postToHtml($_version, strtr($html, ['@@LIB@@' => self::lib(), '@@DATA@@' => $json]));
  }
}

class selenocultureCmd extends cmd {
  public function preSave() { // textes de plus de 127 caractères : refusés par history.value en mode strict
    if (in_array($this->getLogicalId(), ['conseil', 'journal'])) $this->setIsHistorized(0);
  }

  public function execute($_options = []) {
    if ($this->getLogicalId() == 'refresh') $this->getEqLogic()->maj();
  }
}
