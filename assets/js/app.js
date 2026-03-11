/**
 * GestSecel - Script frontend (modales, AJAX, messages)
 */

(function() {
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
    Object.keys(data).forEach(function(k) {
      var v = data[k];
      if (Array.isArray(v)) {
        v.forEach(function(item) {
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
    xhr.onreadystatechange = function() {
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
    xhr.onreadystatechange = function() {
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

  // --- Produits (Admin)
  var modalProduct = document.getElementById('modal-product');
  var modalDeleteProduct = document.getElementById('modal-delete-product');
  if (modalProduct) {
    document.getElementById('btn-add-product')?.addEventListener('click', function() {
      document.getElementById('modal-product-title').textContent = 'Ajouter un produit';
      document.getElementById('form-product').reset();
      document.getElementById('product-id').value = '';
      document.getElementById('product-stock').value = '0';
      document.getElementById('product-seuil').value = '5';
      modalProduct.classList.add('is-open');
    });
    document.querySelectorAll('.btn-edit-product').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        getJson('api/produits.php?action=get&id=' + id, function(res) {
          if (!res.success || !res.produit) return;
          var p = res.produit;
          document.getElementById('modal-product-title').textContent = 'Modifier le produit';
          document.getElementById('product-id').value = p.id;
          document.getElementById('product-reference').value = p.reference;
          document.getElementById('product-nom').value = p.nom;
          document.getElementById('product-categorie').value = p.categorie;
          document.getElementById('product-prix').value = p.prix;
          document.getElementById('product-stock').value = p.stock_actuel;
          document.getElementById('product-seuil').value = p.seuil_alerte;
          modalProduct.classList.add('is-open');
        });
      });
    });
    document.getElementById('btn-cancel-product')?.addEventListener('click', function() {
      modalProduct.classList.remove('is-open');
    });
    document.getElementById('form-product')?.addEventListener('submit', function(e) {
      e.preventDefault();
      var id = document.getElementById('product-id').value;
      var action = id ? 'update' : 'create';
      var data = {
        action: action,
        reference: document.getElementById('product-reference').value.trim(),
        nom: document.getElementById('product-nom').value.trim(),
        categorie: document.getElementById('product-categorie').value.trim(),
        prix: document.getElementById('product-prix').value,
        seuil_alerte: document.getElementById('product-seuil').value
      };
      if (action === 'update') data.id = id;
      else data.stock_actuel = document.getElementById('product-stock').value || '0';
      postJson('api/produits.php', data, function(res) {
        if (res.success) window.location.href = 'produits.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  }
  if (modalDeleteProduct) {
    var deleteProductId = null;
    document.querySelectorAll('.btn-delete-product').forEach(function(btn) {
      btn.addEventListener('click', function() {
        deleteProductId = this.getAttribute('data-id');
        document.getElementById('delete-product-ref').textContent = this.getAttribute('data-ref') || '';
        modalDeleteProduct.classList.add('is-open');
      });
    });
    document.getElementById('btn-cancel-delete-product')?.addEventListener('click', function() {
      modalDeleteProduct.classList.remove('is-open');
      deleteProductId = null;
    });
    document.getElementById('btn-confirm-delete-product')?.addEventListener('click', function() {
      if (!deleteProductId) return;
      postJson('api/produits.php', { action: 'delete', id: deleteProductId }, function(res) {
        if (res.success) window.location.href = 'produits.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  }

  // --- Stocks (Admin)
  var modalMouvement = document.getElementById('modal-mouvement');
  var modalHistoire = document.getElementById('modal-histoire');
  if (modalMouvement) {
    var groupSelect = document.getElementById('group-select-produit');
    var refLine = document.getElementById('mouvement-produit-ref-line');
    var refSpan = document.getElementById('mouvement-produit-ref');
    var produitSelect = document.getElementById('mouvement-produit-select');
    var produitIdInput = document.getElementById('mouvement-produit-id');

    document.getElementById('btn-mouvement')?.addEventListener('click', function() {
      produitIdInput.value = '';
      if (refLine) refLine.style.display = 'none';
      if (groupSelect) groupSelect.style.display = 'block';
      document.getElementById('form-mouvement').reset();
      modalMouvement.classList.add('is-open');
    });
    document.querySelectorAll('.btn-open-mouvement').forEach(function(btn) {
      btn.addEventListener('click', function() {
        produitIdInput.value = this.getAttribute('data-id');
        if (refSpan) refSpan.textContent = this.getAttribute('data-ref') || '';
        if (refLine) refLine.style.display = 'block';
        if (groupSelect) groupSelect.style.display = 'none';
        document.getElementById('form-mouvement').reset();
        modalMouvement.classList.add('is-open');
      });
    });
    document.querySelectorAll('.btn-histoire').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        var ref = this.getAttribute('data-ref') || '';
        document.getElementById('histoire-ref').textContent = ref;
        document.getElementById('histoire-content').innerHTML = 'Chargement...';
        modalHistoire.classList.add('is-open');
        getJson('api/stocks.php?action=historique&produit_id=' + id + '&csrf_token=' + encodeURIComponent(getCsrf()), function(res) {
          if (!res.success || !res.mouvements) {
            document.getElementById('histoire-content').innerHTML = '<p>Aucun mouvement.</p>';
            return;
          }
          var html = '<table class="data-table"><thead><tr><th>Date</th><th>Type</th><th>Qté</th><th>Stock avant</th><th>Stock après</th><th>Motif</th></tr></thead><tbody>';
          var typeLabels = { entree: 'Entrée', sortie: 'Sortie', ajustement_plus: 'Augmentation', ajustement_moins: 'Diminution', inventaire: 'Inventaire', commande: 'Commande' };
          res.mouvements.forEach(function(m) {
            var typeLabel = typeLabels[m.type] || m.type;
            html += '<tr><td>' + escapeHtml(m.date_mouvement) + '</td><td>' + escapeHtml(typeLabel) + '</td><td>' + m.qte + '</td><td>' + m.stock_avant + '</td><td>' + m.stock_apres + '</td><td>' + escapeHtml(m.motif || '') + '</td></tr>';
          });
          html += '</tbody></table>';
          document.getElementById('histoire-content').innerHTML = html;
        });
      });
    });
    document.getElementById('btn-cancel-mouvement')?.addEventListener('click', function() {
      modalMouvement.classList.remove('is-open');
    });
    document.getElementById('form-mouvement')?.addEventListener('submit', function(e) {
      e.preventDefault();
      var pid = produitIdInput.value || (produitSelect ? produitSelect.value : '');
      if (!pid) {
        alert('Veuillez choisir un produit.');
        return;
      }
      var data = {
        action: 'ajustement',
        produit_id: pid,
        type: document.getElementById('mouvement-type').value,
        qte: document.getElementById('mouvement-qte').value,
        motif: document.getElementById('mouvement-motif').value.trim()
      };
      postJson('api/stocks.php', data, function(res) {
        if (res.success) window.location.href = 'stocks.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  }
  if (modalHistoire) {
    document.getElementById('btn-close-histoire')?.addEventListener('click', function() {
      modalHistoire.classList.remove('is-open');
    });
  }

  // --- Commandes
  var modalNouvelleCommande = document.getElementById('modal-nouvelle-commande');
  var modalRefus = document.getElementById('modal-refus-commande');
  if (modalNouvelleCommande) {
    document.getElementById('btn-nouvelle-commande')?.addEventListener('click', function() {
      document.getElementById('form-nouvelle-commande').reset();
      var container = document.getElementById('lignes-commande');
      container.innerHTML = container.querySelector('.ligne-commande') ? container.innerHTML : document.querySelector('#lignes-commande .ligne-commande')?.outerHTML || '';
      modalNouvelleCommande.classList.add('is-open');
    });
    document.getElementById('btn-ajouter-ligne')?.addEventListener('click', function() {
      var container = document.getElementById('lignes-commande');
      var first = container.querySelector('.ligne-commande');
      if (!first) return;
      var clone = first.cloneNode(true);
      clone.querySelector('select').value = '';
      clone.querySelector('input[type="number"]').value = '1';
      container.appendChild(clone);
    });
    document.getElementById('btn-cancel-commande')?.addEventListener('click', function() {
      modalNouvelleCommande.classList.remove('is-open');
    });
    document.getElementById('form-nouvelle-commande')?.addEventListener('submit', function(e) {
      e.preventDefault();
      var motif = document.getElementById('commande-motif').value.trim();
      var selects = document.querySelectorAll('#lignes-commande select.produit-select');
      var inputs = document.querySelectorAll('#lignes-commande input[name="qte[]"]');
      var produit_id = [];
      var qte = [];
      for (var i = 0; i < selects.length; i++) {
        var v = selects[i].value;
        var q = parseInt(inputs[i]?.value || '0', 10);
        if (v && q > 0) {
          produit_id.push(v);
          qte.push(q);
        }
      }
      if (produit_id.length === 0) {
        alert('Ajoutez au moins une ligne avec un produit et une quantité.');
        return;
      }
      var data = { action: 'create', motif: motif, produit_id: produit_id, qte: qte };
      postJson('api/commandes.php', data, function(res) {
        if (res.success) {
          window.location.href = 'commandes.php?msg=ok&id=' + (res.id || '');
        } else {
          alert(res.message || 'Erreur');
        }
      });
    });
  }
  if (modalRefus) {
    document.getElementById('btn-refuser-commande')?.addEventListener('click', function() {
      document.getElementById('refus-commande-id').value = this.getAttribute('data-id');
      document.getElementById('refus-motif').value = '';
      modalRefus.classList.add('is-open');
    });
    document.getElementById('btn-cancel-refus')?.addEventListener('click', function() {
      modalRefus.classList.remove('is-open');
    });
    document.getElementById('form-refus-commande')?.addEventListener('submit', function(e) {
      e.preventDefault();
      var data = {
        action: 'refuser',
        id_commande: document.getElementById('refus-commande-id').value,
        motif_refus: document.getElementById('refus-motif').value.trim()
      };
      postJson('api/commandes.php', data, function(res) {
        if (res.success) window.location.href = 'commandes.php?msg=refusee';
        else alert(res.message || 'Erreur');
      });
    });
  }
  // Validation commande (admin)
  var btnValider = document.getElementById('btn-valider-commande');
  if (btnValider) {
    btnValider.addEventListener('click', function() {
      var idCommande = this.getAttribute('data-id');
      var inputs = document.querySelectorAll('.qte-validee-input');
      var lignes = [];
      inputs.forEach(function(inp) {
        lignes.push({ detail_id: inp.getAttribute('data-detail-id'), qte_validee: inp.value });
      });
      var data = { action: 'valider', id_commande: idCommande, lignes: JSON.stringify(lignes) };
      postJson('api/commandes.php', data, function(res) {
        if (res.success) window.location.href = 'commandes.php?msg=validee&id=' + idCommande;
        else {
          var el = document.getElementById('validation-msg');
          if (el) el.innerHTML = '<div class="alert alert-error">' + escapeHtml(res.message || 'Erreur') + '</div>';
          else alert(res.message || 'Erreur');
        }
      });
    });
  }
  // API commandes attend un tableau lignes: [ { detail_id, qte_validee } ]
  var formValider = document.querySelector('form[data-action="valider"]');
  if (btnValider && formValider) {
    formValider?.addEventListener('submit', function(e) {
      e.preventDefault();
      btnValider.click();
    });
  }

  // --- Utilisateurs (Admin)
  var modalUser = document.getElementById('modal-user');
  if (modalUser) {
    document.getElementById('btn-add-user')?.addEventListener('click', function() {
      document.getElementById('modal-user-title').textContent = 'Ajouter un utilisateur';
      document.getElementById('form-user').reset();
      document.getElementById('user-id').value = '';
      document.getElementById('user-password').required = true;
      document.getElementById('pwd-optional').style.display = 'none';
      modalUser.classList.add('is-open');
    });
    document.querySelectorAll('.btn-edit-user').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = this.getAttribute('data-id');
        getJson('api/utilisateurs.php?action=get&id=' + id + '&csrf_token=' + encodeURIComponent(getCsrf()), function(res) {
          if (!res.success || !res.utilisateur) return;
          var u = res.utilisateur;
          document.getElementById('modal-user-title').textContent = 'Modifier l\'utilisateur';
          document.getElementById('user-id').value = u.id;
          document.getElementById('user-login').value = u.login;
          document.getElementById('user-password').value = '';
          document.getElementById('user-password').required = false;
          document.getElementById('pwd-optional').style.display = 'inline';
          document.getElementById('user-nom').value = u.nom;
          document.getElementById('user-prenom').value = u.prenom;
          document.getElementById('user-role').value = u.role;
          document.getElementById('user-actif').checked = !!parseInt(u.actif, 10);
          modalUser.classList.add('is-open');
        });
      });
    });
    document.getElementById('btn-cancel-user')?.addEventListener('click', function() {
      modalUser.classList.remove('is-open');
    });
    document.getElementById('form-user')?.addEventListener('submit', function(e) {
      e.preventDefault();
      var id = document.getElementById('user-id').value;
      var action = id ? 'update' : 'create';
      var data = {
        action: action,
        login: document.getElementById('user-login').value.trim(),
        nom: document.getElementById('user-nom').value.trim(),
        prenom: document.getElementById('user-prenom').value.trim(),
        role: document.getElementById('user-role').value,
        actif: document.getElementById('user-actif').checked ? '1' : '0'
      };
      if (action === 'update') {
        data.id = id;
        var pwd = document.getElementById('user-password').value;
        if (pwd) data.password = pwd;
      } else {
        data.password = document.getElementById('user-password').value;
      }
      postJson('api/utilisateurs.php', data, function(res) {
        if (res.success) window.location.href = 'utilisateurs.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  }
  document.querySelectorAll('.btn-delete-user').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Désactiver l\'utilisateur « ' + this.getAttribute('data-login') + ' » ?')) return;
      var id = this.getAttribute('data-id');
      postJson('api/utilisateurs.php', { action: 'delete', id: id }, function(res) {
        if (res.success) window.location.href = 'utilisateurs.php?msg=ok';
        else alert(res.message || 'Erreur');
      });
    });
  });

  // Fermer modales au clic sur overlay
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) overlay.classList.remove('is-open');
    });
  });

  // Disparition message flash
  var flash = document.getElementById('flash');
  if (flash && flash.classList.contains('alert')) {
    setTimeout(function() { flash.style.opacity = '0'; }, 4000);
  }
})();
