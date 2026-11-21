(function () {
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  var deck = document.querySelector('[data-deck]');
  if (!deck) return;

  var STORAGE_KEY = 'dse-chinese-known-cards';
  var known;
  try { known = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')); } catch (e) { known = new Set(); }
  var save = function () {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(known))); } catch (e) { /* storage unavailable */ }
  };

  var toolbar = document.querySelector('[data-deck-toolbar]');
  var hideKnown = document.querySelector('[data-deck-hide-known]');
  var count = document.querySelector('[data-deck-count]');
  var cards = Array.prototype.slice.call(deck.querySelectorAll('.flashcard'));
  toolbar.hidden = false;

  var refresh = function () {
    var visible = 0;
    cards.forEach(function (card) {
      var isKnown = known.has(card.dataset.cardId);
      card.classList.toggle('is-known', isKnown);
      card.querySelector('[data-known]').checked = isKnown;
      card.hidden = hideKnown.checked && isKnown;
      if (!card.hidden) visible++;
    });
    count.textContent = visible;
  };

  cards.forEach(function (card) {
    card.querySelector('.flashcard-inner').addEventListener('click', function () {
      card.classList.toggle('is-flipped');
    });
    card.querySelector('[data-known]').addEventListener('change', function (e) {
      if (e.target.checked) known.add(card.dataset.cardId); else known.delete(card.dataset.cardId);
      save();
      refresh();
    });
  });

  hideKnown.addEventListener('change', refresh);

  document.querySelector('[data-deck-shuffle]').addEventListener('click', function () {
    for (var i = cards.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = cards[i]; cards[i] = cards[j]; cards[j] = tmp;
    }
    cards.forEach(function (card) { card.classList.remove('is-flipped'); deck.appendChild(card); });
  });

  refresh();
})();
