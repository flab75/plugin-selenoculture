<?php
require_once __DIR__ . '/../../../core/php/core.inc.php';

function selenoculture_install() {
}

function selenoculture_update() {
  foreach (eqLogic::byType('selenoculture') as $eq) $eq->save(); // crée les commandes ajoutées par la nouvelle version
}

function selenoculture_remove() {
}
