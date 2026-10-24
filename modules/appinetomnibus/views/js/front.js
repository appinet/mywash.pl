/**
 * Moves the Omnibus message out of the price block to the element set in the
 * module configuration. Price blocks are re-rendered by AJAX (combinations,
 * listing filters) and by theme scripts, so every new message is placed when
 * it appears.
 */
(function () {
  var config = window.appinetOmnibusPlacement || {};
  var MINIATURE = '.js-product-miniature, .product-miniature, [data-id-product], article';
  var PLACED = 'data-appinet-placed';
  var scheduled = false;

  function scopeOf(message, placement) {
    return placement === 'list' ? message.closest(MINIATURE) || document : document;
  }

  function findAnchor(message, placement) {
    var selector = config[placement].selector;

    try {
      return message.closest(selector) || scopeOf(message, placement).querySelector(selector);
    } catch (e) {
      return null; // invalid selector in the configuration
    }
  }

  function insert(message, anchor, position) {
    if (position === 'append') {
      anchor.appendChild(message);
    } else if (position === 'prepend') {
      anchor.insertBefore(message, anchor.firstChild);
    } else {
      anchor.parentNode.insertBefore(message, position === 'before' ? anchor : anchor.nextSibling);
      // Flex layouts (Creative Elements) may reorder siblings with "order".
      message.style.order = window.getComputedStyle(anchor).order;
    }
  }

  function removePlaced(scope, placement) {
    var old = scope.querySelectorAll('.appinet-omnibus[' + PLACED + '="1"][data-placement="' + placement + '"]');

    for (var i = 0; i < old.length; i++) {
      old[i].parentNode.removeChild(old[i]);
    }
  }

  function place() {
    scheduled = false;

    var messages = document.querySelectorAll('.appinet-omnibus:not([' + PLACED + '])');

    for (var i = 0; i < messages.length; i++) {
      var message = messages[i];
      var placement = message.getAttribute('data-placement');
      var anchor = config[placement] ? findAnchor(message, placement) : null;

      if (!anchor) {
        message.setAttribute(PLACED, '0');
        continue;
      }

      // A refreshed price block brings a new message: drop the one moved before.
      removePlaced(scopeOf(message, placement), placement);
      message.setAttribute(PLACED, '1');
      insert(message, anchor, config[placement].position);
    }
  }

  function schedule() {
    if (!scheduled) {
      scheduled = true;
      window.requestAnimationFrame(place);
    }
  }

  function init() {
    place();
    new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });

    // A combination without a reduction renders no message: remove the old one.
    if (window.prestashop && typeof window.prestashop.on === 'function') {
      window.prestashop.on('updatedProduct', function () {
        var refreshed = false;
        var stale = document.querySelectorAll('.appinet-omnibus[' + PLACED + '="1"][data-placement="product"]');

        window.setTimeout(function () {
          refreshed = !!document.querySelector('.appinet-omnibus[data-placement="product"]:not([' + PLACED + '="1"])');
          place();

          if (!refreshed) {
            for (var i = 0; i < stale.length; i++) {
              if (stale[i].parentNode) {
                stale[i].parentNode.removeChild(stale[i]);
              }
            }
          }
        }, 300);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
