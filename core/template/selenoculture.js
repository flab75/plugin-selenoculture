/* Sélénoculture — rendu de la tuile et de sa fenêtre (calendrier, journal, explications). Recopié dans chaque tuile par
   selenoculture::toHtml(), chargé une seule fois par page. Textes entre doubles accolades traduits côté PHP (core/i18n). */
if (!window.selenoculture) (function () {
  var css = document.createElement('style')
  css.textContent =
    '.sel{box-sizing:border-box;width:100%;display:flex;flex-direction:column;gap:5px;padding:6px 8px 10px;text-align:left;font-size:12px}' +
    '.sel-h{display:flex;gap:10px;align-items:center}.sel-h svg{flex:none}.sel-ph{font-size:16px;font-weight:300}.sel-s{opacity:.7}' +
    '.sel-pill{display:inline-block;border-radius:10px;padding:1px 8px;color:#fff;font-weight:600;margin-right:4px}' +
    '.sel-al{background:#b3261e;color:#fff;border-radius:8px;padding:2px 8px;font-weight:600}.sel-al:empty{display:none}' +
    '.sel-7{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;text-align:center;font-size:10px}' +
    '.sel-7 div{border-radius:6px;padding:2px 0;background:rgba(128,128,128,.12)}.sel-7 .x{outline:1px solid #b3261e}' +
    '.sel-7 b{display:block;font-size:14px;font-weight:400}' +
    '.sel-c{margin:0;padding-left:14px}.sel-c li{margin:1px 0}.sel-c .alerte{color:#d33;font-weight:600}' +
    '.sel-bt{display:flex;gap:5px;justify-content:center}.sel-bt a{cursor:pointer;border-radius:12px;padding:2px 10px;background:rgba(128,128,128,.2)}' +
    '.sel-m{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center}' +
    '.sel-mb{background:var(--bg-modal-color,#fff);color:var(--txt-color,#222);width:min(760px,96vw);max-height:90vh;overflow:auto;border-radius:10px;padding:10px 14px;font-size:13px;text-align:left}' +
    '.sel-mb nav{display:flex;gap:6px;margin-bottom:8px}.sel-mb nav a{cursor:pointer;padding:3px 10px;border-radius:10px;background:rgba(128,128,128,.15)}' +
    '.sel-mb nav a.on{background:#4a7;color:#fff}.sel-mb nav .z{margin-left:auto}' +
    '.sel-mb table{width:100%;border-collapse:collapse}.sel-mb td,.sel-mb th{padding:3px 5px;border-bottom:1px solid rgba(128,128,128,.25);vertical-align:top}' +
    '.sel-mb form{display:grid;grid-template-columns:auto 1fr;gap:4px 8px;align-items:center;margin-bottom:10px}' +
    '.sel-mb input,.sel-mb select,.sel-mb textarea{width:100%;box-sizing:border-box}.sel-mb h4{margin:10px 0 3px}.sel-er{color:#d33}'
  document.head.appendChild(css)

  var EL = {racine: ['\u{1F955}', "{{Racine}}", '#a8692e'], feuille: ['\u{1F96C}', "{{Feuille}}", '#3e9a48'],
    fleur: ['\u{1F338}', "{{Fleur}}", '#c4569b'], fruit: ['\u{1F345}', "{{Fruit}}", '#d9482f']}
  function esc(s) { return String(s === undefined || s === null ? '' : s).replace(/[&<>"']/g, function (x) { return '&#' + x.charCodeAt(0) + ';' }) }

  // Lune vue de l'hémisphère nord : partie éclairée entre le limbe et le terminateur (ellipse de demi-axe r·cos(élongation))
  function lune(elong, taille) {
    var r = 20, e = elong * Math.PI / 180, rx = Math.abs(r * Math.cos(e)).toFixed(2), sw = Math.cos(e) > 0 ? 0 : 1
    var p = 'M0,-' + r + 'A' + r + ',' + r + ' 0 0 1 0,' + r + 'A' + rx + ',' + r + ' 0 0 ' + sw + ' 0,-' + r + 'Z'
    return '<svg width="' + taille + '" height="' + taille + '" viewBox="-22 -22 44 44" role="img" aria-label="' + Math.round((1 - Math.cos(e)) * 50) + ' %">' +
      '<circle r="' + r + '" fill="#3a3f4a"/><path d="' + p + '" fill="#f1e9c8"' + (elong > 180 ? ' transform="scale(-1,1)"' : '') + '/></svg>'
  }
  function sens(m) { return m ? '↗ ' + "{{montante}}" : '↘ ' + "{{descendante}}" }

  function ajax(d, action, champs, ok, ko) {
    var f = new FormData()
    f.append('action', action); f.append('id', d.id); f.append('jeedom_token', window.JEEDOM_AJAX_TOKEN || '')
    Object.keys(champs).forEach(function (k) { f.append(k, champs[k]) })
    fetch('plugins/selenoculture/core/ajax/selenoculture.ajax.php', {method: 'POST', body: f, credentials: 'same-origin'})
      .then(function (r) { return r.json() })
      .then(function (j) { if (j.state == 'ok') ok(j.result); else ko(j.result) })
      .catch(function (e) { ko(String(e)) })
  }

  var EXPLI = '<h4>' + "{{Le principe}}" + '</h4><p>' + "{{La sélénoculture règle les travaux du jardin sur la Lune. Trois repères comptent, du plus au moins important : la Lune montante ou descendante, la constellation devant laquelle elle passe (type de jour), puis la phase.}}" + '</p>' +
    '<h4>' + "{{Montante ou descendante}}" + '</h4><p>' + "{{La Lune monte dans le ciel pendant environ 13,6 jours, puis redescend autant (c'est sa déclinaison, à ne pas confondre avec la phase). Montante : on dit la sève dans les parties hautes, moment choisi pour semer, greffer, récolter fruits et feuilles. Descendante : la sève redescend, moment choisi pour planter, repiquer, tailler, travailler et amender le sol, récolter les racines.}}" + '</p>' +
    '<h4>' + "{{Les types de jour}}" + '</h4><p>' + "{{Selon la constellation, le jour favorise une partie de la plante : racine (Taureau, Vierge, Capricorne), feuille (Cancer, Scorpion, Poissons), fleur (Gémeaux, Balance, Verseau), fruit (Bélier, Lion, Sagittaire). On sème ou plante chaque légume le jour de la partie qu'on récolte.}}" + '</p>' +
    '<h4>' + "{{Jours à éviter}}" + '</h4><p>' + "{{Aux nœuds lunaires (la Lune traverse le plan de l'orbite terrestre), au périgée (au plus près de la Terre) et à l'apogée (au plus loin), la tradition déconseille de semer, planter ou tailler, quelques heures avant et après.}}" + '</p>' +
    '<h4>' + "{{Croissante ou décroissante}}" + '</h4><p>' + "{{Critère secondaire : la Lune croissante passe pour favoriser la pousse aérienne, la décroissante l'enracinement et la conservation des récoltes.}}" + '</p>' +
    '<h4>' + "{{Ce qu'on peut en attendre}}" + '</h4><p>' + "{{Les études publiées n'ont pas montré d'effet solide de la Lune sur les rendements. Ce que la méthode apporte surtout : un calendrier qui étale semis et plantations, l'habitude d'observer et de noter, et la comparaison d'une année sur l'autre grâce au journal. À vous de juger sur vos propres notes.}}" + '</p>' +
    '<h4>' + "{{Le calcul}}" + '</h4><p>' + "{{Position de la Lune calculée sur la box (algorithmes de J. Meeus), vérifiée contre le JPL et l'USNO à quelques minutes près. Les limites des constellations varient selon les calendriers, d'où des écarts de quelques heures entre eux sur l'heure d'un changement de type de jour ; la configuration de l'équipement permet de choisir.}}" + '</p>' +
    '<h4>' + "{{Pour aller plus loin}}" + '</h4><ul>' +
    [['https://www.rustica.fr/jardiner-avec-la-lune/', 'Rustica'], ['https://www.jardiner-malin.fr/calendrier-lunaire', 'Jardiner malin'],
      ['https://www.autonomiejardin.com/la-lune-au-jardin/jardiner-avec-la-lune/', 'Autonomie jardin'], ['https://www.gerbeaud.com/jardin/calendrier/calendrier-lunaire.php', 'Gerbeaud'],
      ['https://www.calendrier-lunaire.fr/', 'Calendrier lunaire (M. Gros)'], ['https://iriso.fr/blog/utiliser-comprendre-lune-jardin-potager-guide-complet', 'Iriso']]
      .map(function (l) { return '<li><a href="' + l[0] + '" target="_blank" rel="noopener noreferrer">' + l[1] + '</a></li>' }).join('') + '</ul>'

  function fenetre(d, onglet) {
    var m = document.createElement('div'); m.className = 'sel-m'
    m.innerHTML = '<div class="sel-mb"><nav><a data-o="cal">' + "{{Calendrier}}" + '</a><a data-o="jou">' + "{{Journal}}" + '</a><a data-o="exp">' + "{{Explications}}" + '</a><a class="z">✕</a></nav><div class="sel-ct"></div></div>'
    document.body.appendChild(m)
    var ct = m.querySelector('.sel-ct'), cache = null
    function fermer() { m.remove(); document.removeEventListener('keydown', touche) }
    function touche(e) { if (e.key == 'Escape') fermer() }
    document.addEventListener('keydown', touche)
    m.onclick = function (e) { if (e.target == m || e.target.classList.contains('z')) fermer() }
    function charger(fn) {
      ajax(d, 'calendrier', {jours: 30}, function (r) { cache = r; fn() }, function (er) { ct.innerHTML = '<p class="sel-er">' + esc(er) + '</p>' })
    }
    function cal() {
      ct.innerHTML = '<table><tr><th>' + "{{Jour}}" + '</th><th></th><th>' + "{{Type}}" + '</th><th>' + "{{Lune}}" + '</th><th>' + "{{Évènements}}" + '</th></tr>' + cache.cal.map(function (j) {
        var e = EL[j.el]
        return '<tr><td>' + esc(j.nom) + '</td><td>' + lune(j.elong, 22) + '</td><td style="color:' + e[2] + '">' + e[0] + ' ' + e[1] + '<br><small>' + esc(j.cons) + '</small></td>' +
          '<td>' + sens(j.mont) + '<br><small>' + j.illum + ' %</small></td><td>' + (j.abst ? '<b class="sel-er">' + "{{À éviter}}" + ' : ' + esc(j.abst) + '</b><br>' : '') +
          j.ev.map(function (x) { return esc(x[0] + ' ' + x[1]) }).join('<br>') + '</td></tr>'
      }).join('') + '</table>'
    }
    function jou() {
      var auj = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10)
      ct.innerHTML = (d.admin == 1 ? '<form><label>' + "{{Date}}" + '</label><input type="date" name="date" value="' + auj + '" required>' +
        '<label>' + "{{Lieu}}" + '</label><input name="lieu" list="sel-lieux" maxlength="60"><datalist id="sel-lieux">' + d.lieux.map(function (l) { return '<option value="' + esc(l) + '">' }).join('') + '</datalist>' +
        '<label>' + "{{Travail}}" + '</label><select name="action">' + d.actionsCle.map(function (a, i) { return '<option value="' + esc(a) + '">' + esc(d.actions[i]) + '</option>' }).join('') + '</select>' +
        '<label>' + "{{Plante}}" + '</label><input name="plante" maxlength="80">' +
        '<label>' + "{{Catégorie}}" + '</label><select name="cat"><option value="">–</option>' + Object.keys(EL).map(function (k) { return '<option value="' + k + '">' + EL[k][0] + ' ' + EL[k][1] + '</option>' }).join('') + '</select>' +
        '<label>' + "{{Note}}" + '</label><textarea name="note" rows="2" maxlength="500"></textarea>' +
        '<span></span><span><button type="submit">' + "{{Enregistrer}}" + '</button> <span class="sel-er"></span></span></form>' : '') +
        '<table><tr><th>' + "{{Date}}" + '</th><th>' + "{{Travail}}" + '</th><th>' + "{{Lune ce jour-là}}" + '</th><th></th></tr>' + cache.journal.map(function (x) {
          var e = EL[x.lune && x.lune.element] || ['', '']
          return '<tr><td>' + esc(x.date) + '</td><td><b>' + esc(d.actions[d.actionsCle.indexOf(x.action)] || x.action) + '</b> ' + esc(x.plante) + (x.cat ? ' ' + EL[x.cat][0] : '') + (x.lieu ? ' <small>(' + esc(x.lieu) + ')</small>' : '') +
            (x.note ? '<br><small>' + esc(x.note) + '</small>' : '') + '</td><td>' + e[0] + ' ' + e[1] + ', ' + sens(x.lune && x.lune.montante == 1) + '</td><td>' +
            (d.admin == 1 ? '<a class="sel-del" data-id="' + esc(x.id) + '" title="' + "{{Supprimer}}" + '" style="cursor:pointer">✕</a>' : '') + '</td></tr>'
        }).join('') + '</table>' + (cache.journal.length ? '' : '<p>' + "{{Rien de noté pour l'instant.}}" + '</p>')
      var f = ct.querySelector('form')
      if (f) f.onsubmit = function (ev) {
        ev.preventDefault()
        var t = {}, bt = f.querySelector('button'); new FormData(f).forEach(function (v, k) { t[k] = v })
        bt.disabled = true // pas de double envoi
        ajax(d, 'ajouter', {travail: JSON.stringify(t)}, function () { charger(jou) }, function (er) { bt.disabled = false; f.querySelector('.sel-er').textContent = er })
      }
      ct.querySelectorAll('.sel-del').forEach(function (a) {
        a.onclick = function () { ajax(d, 'supprimer', {travail: a.dataset.id}, function () { charger(jou) }, function (er) { a.textContent = er }) }
      })
    }
    function montrer(o) {
      m.querySelectorAll('nav a[data-o]').forEach(function (a) { a.classList.toggle('on', a.dataset.o == o) })
      if (o == 'exp') { ct.innerHTML = EXPLI; return }
      ct.textContent = "{{Chargement…}}"
      if (cache) (o == 'cal' ? cal : jou)(); else charger(o == 'cal' ? cal : jou)
    }
    m.querySelectorAll('nav a[data-o]').forEach(function (a) { a.onclick = function () { montrer(a.dataset.o) } })
    montrer(onglet)
  }

  window.selenoculture = {init: function (b, d) {
    if (!b) return
    var e = d.etat, el = EL[e.element]
    b.innerHTML = '<div class="sel-h">' + lune(e.elong, 56) + '<div><div class="sel-ph">' + esc(e.phase) + '</div><div class="sel-s">' +
      Math.round(e.illum * 100) + ' % · ' + "{{âge}}" + ' ' + e.age.toFixed(1).replace('.', ',') + ' j · ' + Math.round(e.dist).toLocaleString() + ' km</div>' +
      '<div style="margin-top:3px"><span class="sel-pill" style="background:' + el[2] + '">' + el[0] + ' ' + "{{Jour}}" + ' ' + el[1].toLowerCase() + '</span>' + sens(e.montante) + '</div>' +
      '<div class="sel-s">' + "{{en}}" + ' ' + esc(e.constellation) + (d.prochain ? ' · ' + esc(d.prochain) : '') + '</div></div></div>' +
      '<div class="sel-al">' + (d.raison ? esc("{{À éviter}}" + ' : ' + d.raison) : '') + '</div>' +
      '<div class="sel-7">' + d.cal.map(function (j) { var x = EL[j.el]; return '<div' + (j.abst ? ' class="x"' : '') + ' title="' + esc(j.nom + ' : ' + x[1] + ', ' + sens(j.mont) + (j.abst ? ' · ' + j.abst : '')) + '">' + esc(j.nom.split(' ')[0]) + '<b>' + x[0] + '</b>' + (j.mont ? '↗' : '↘') + '</div>' }).join('') + '</div>' +
      '<ul class="sel-c">' + d.conseils.filter(function (c) { return c[0] != 'saison' }).concat(d.conseils.filter(function (c) { return c[0] == 'saison' }).slice(0, 1))
        .map(function (c) { return '<li class="' + c[0] + '">' + esc(c[1]) + '</li>' }).join('') + '</ul>' +
      '<div class="sel-bt">' + (d.admin == 1 ? '<a data-o="jou">＋ ' + "{{Noter}}" + '</a>' : '') + '<a data-o="cal">' + "{{Calendrier}}" + '</a><a data-o="exp">?</a></div>'
    b.querySelectorAll('.sel-bt a').forEach(function (a) { a.onclick = function () { fenetre(d, a.dataset.o) } })
  }}
})()
