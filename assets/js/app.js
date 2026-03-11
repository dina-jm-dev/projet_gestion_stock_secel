/**
 * GestSecel - Script frontend (modales, AJAX, messages)
 */

(function () {
  'use strict';

  function getCsrf() {
    return window.GestSecelProduits?.csrf || window.GestSecelStocks?.csrf || window.GestSecelCommandes?.csrf || window.GestSecelUsers?.csrf || '';
  }

  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s == null ? '' : String(s);
    return div.innerHTML;
  }

  function serializeParams(data) {
    var parts = [];
    Object.keys(data).forEach(function (k) {
      var v = data[k];
      if (Array.isArray(v)) {
        v.forEach(function (item) {
          parts.push(encodeURIComponent(k) + '[]=' + encodeURIComponent(item));
        });
      } else {
        parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
      }
    });
    return parts.join('&');
  }

  function postJson(url, data, callback) {
    data.csrf_token = getCsrf();
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      try {
        var json = JSON.parse(xhr.responseText || '{}');
        callback(json, xhr.status);
      } catch (e) {
        callback({ success: false, message: 'Erreur de réponse.' }, xhr.status);
      }
    };
    xhr.send(serializeParams(data));
  }

  function getJson(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      try {
        var json = JSON.parse(xhr.responseText || '{}');
        callback(json, xhr.status);
      } catch (e) {
        callback({ success: false }, xhr.status);
      }
    };
    xhr.send();
  }

  // --- Produits (Suppression)
  document.querySelectorAll('.btn-delete-product').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ref = this.getAttribute('data-ref') || '';
      if (!confirm('Voulez-vous vraiment supprimer le produit ' + ref + ' ?')) return;
      var id = this.getAttribute('data-id');
      postJson('api/produits.php', { action: 'delete', id: id }, function (res) {
        if (res.success) window.location.href = 'produits.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  });

  // --- Commandes
  document.getElementById('btn-refuser-commande')?.addEventListener('click', function () {
    var motif = prompt('Veuillez saisir le motif du refus :');
    if (motif === null) return; // Annulé
    var id = this.getAttribute('data-id');
    postJson('api/commandes.php', { action: 'refuser', id_commande: id, motif_refus: motif.trim() }, function (res) {
      if (res.success) window.location.href = 'commandes.php?msg=refusee';
      else alert(res.message || 'Erreur');
    });
  });

  // Validation commande (admin)
  var btnValider = document.getElementById('btn-valider-commande');
  if (btnValider) {
    btnValider.addEventListener('click', function () {
      var idCommande = this.getAttribute('data-id');
      var inputs = document.querySelectorAll('.qte-validee-input');
      var lignes = [];
      inputs.forEach(function (inp) {
        lignes.push({ detail_id: inp.getAttribute('data-detail-id'), qte_validee: inp.value });
      });
      var data = { action: 'valider', id_commande: idCommande, lignes: JSON.stringify(lignes) };
      postJson('api/commandes.php', data, function (res) {
        if (res.success) window.location.href = 'commandes.php?msg=validee&id=' + idCommande;
        else {
          var el = document.getElementById('validation-msg');
          if (el) el.innerHTML = '<div class="alert alert-error">' + escapeHtml(res.message || 'Erreur') + '</div>';
          else alert(res.message || 'Erreur');
        }
      });
    });
  }

  var formValider = document.querySelector('form[data-action="valider"]');
  if (btnValider && formValider) {
    formValider.addEventListener('submit', function (e) {
      e.preventDefault();
      btnValider.click();
    });
  }

  // --- Utilisateurs (Désactivation)
  document.querySelectorAll('.btn-delete-user').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Désactiver l\'utilisateur « ' + this.getAttribute('data-login') + ' » ?')) return;
      var id = this.getAttribute('data-id');
      postJson('api/utilisateurs.php', { action: 'delete', id: id }, function (res) {
        if (res.success) window.location.href = 'utilisateurs.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  });


  // Disparition message flash
  var flash = document.getElementById('flash');
  if (flash && flash.classList.contains('alert')) {
    setTimeout(function () { flash.style.opacity = '0'; }, 4000);
  }
})();
