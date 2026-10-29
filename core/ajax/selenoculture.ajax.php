<?php
try {
  require_once __DIR__ . '/../../../../core/php/core.inc.php';
  include_file('core', 'authentification', 'php');
  if (!isConnect()) throw new Exception(__('401 - Accès non autorisé', __FILE__));
  require_once __DIR__ . '/../class/selenoculture.class.php';
  ajax::init();

  $eq = selenoculture::byId(init('id'));
  if (!is_object($eq) || $eq->getEqType_name() != 'selenoculture') throw new Exception(__('Équipement introuvable', __FILE__));
  if (!$eq->hasRight('r')) throw new Exception(__('401 - Accès non autorisé', __FILE__));

  if (init('action') == 'calendrier') {
    ajax::success(['cal' => selenoculture::calendrier(time(), max(1, min(60, (int)init('jours', 30))), $eq->conv()),
      'journal' => array_reverse($eq->journal())]); // ponytail: tout le journal à chaque ouverture ; ne l'envoyer qu'à l'onglet Journal s'il devient lourd
  }
  // écriture dans le journal : administrateurs seulement
  if (!isConnect('admin')) throw new Exception(__('401 - Accès non autorisé', __FILE__));
  if (init('action') == 'ajouter') { $t = json_decode((string)init('travail'), true); ajax::success($eq->ajouter(is_array($t) ? $t : [])); }
  if (init('action') == 'supprimer') { $eq->supprimer((string)init('travail')); ajax::success(); }

  throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
} catch (Throwable $e) {
  ajax::error($e->getMessage(), $e->getCode()); // texte brut : la tuile l'affiche avec textContent
}
