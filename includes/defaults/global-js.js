/**
 * Site Utilities (Beaver Builder)
 * - Sticky header state on scroll
 * - Clickable columns (outside BB editor)
 * - Equal height groups via class markers
 * - Normalise button class (treat other buttons like .fl-button)
 *
 * Notes:
 * - Uses jQuery as requested (no $ alias).
 * - Designed to be pasted into Beaver's Custom JS, or ideally enqueued as a file.
 */
(function (window, document, jQuery) {
  "use strict";

  var SiteUI = {
    init: function () {
      this.initStickyHeader();
      this.initClickableColumns();
      this.initEqualHeights();
      this.initButtonNormaliser();
    },

    /**
     * 1) Sticky header scrolled state
     * Adds .scrolled to .sticky-row when page is not at top.
     */
    initStickyHeader: function () {
      var header = document.querySelector(".sticky-row");
      if (!header) return;

      var toggle = function () {
        if (window.scrollY > 0) header.classList.add("scrolled");
        else header.classList.remove("scrolled");
      };

      toggle(); // set initial state
      window.addEventListener("scroll", toggle, { passive: true });
    },

    /**
     * 2) Clickable columns
     * Makes .column-link behave like its nearest anchor.
     * Disabled in Beaver Builder edit mode.
     */
    initClickableColumns: function () {
      if (jQuery("html").hasClass("fl-builder-edit")) return;

      jQuery(".column-link").each(function () {
        var $el = jQuery(this);

        // Prefer closest wrapping anchor, otherwise find first child anchor
        var href =
          $el.closest("a").attr("href") ||
          $el.find("a").first().attr("href");

        if (!href) return;

        $el.css("cursor", "pointer").on("click", function (e) {
          // Avoid double navigation if user clicked an actual link inside
          if (jQuery(e.target).closest("a").length) return;
          window.location.href = href;
        });
      });
    },

    /**
     * 3) Equal heights
     *
     * Markers supported:
     * - Local:   same_height_{target}
     *           Example: <div class="same_height_card"><div class="card">...</div></div>
     *           -> equalises ".card" inside that wrapper only
     *
     * - Global:  same_height_{target}_{group}
     *           Example: same_height_card_a on multiple wrappers
     *           -> equalises ".card" across all wrappers with same group "a"
     *
     * - Group-only: same_height-group-{group}
     *           Example: same_height-group-a placed directly on items
     *           -> equalises all elements having that class
     */
    initEqualHeights: function () {
      var self = this;

      var debounce = function (fn, wait) {
        var t;
        return function () {
          var ctx = this, args = arguments;
          clearTimeout(t);
          t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
      };

      var getClassTokens = function (el) {
        if (el.classList && el.classList.length) return Array.prototype.slice.call(el.classList);
        return (el.className || "").split(" ").filter(Boolean);
      };

      var equaliseSet = function ($items) {
        if ($items.length < 2) return;

        $items.css("height", "auto");

        var maxOuter = 0;
        $items.each(function () {
          var oh = jQuery(this).outerHeight();
          if (oh > maxOuter) maxOuter = oh;
        });
        if (maxOuter <= 0) return;

        $items.each(function () {
          var $el = jQuery(this);
          if ($el.css("box-sizing") === "border-box") {
            $el.css("height", maxOuter + "px");
          } else {
            var extra = $el.outerHeight() - $el.height();
            $el.css("height", Math.max(0, maxOuter - extra) + "px");
          }
        });
      };

      var collectConfigs = function () {
        var globals = {};   // key: target|group => { target, group, wrappers[] }
        var locals = [];    // { target, wrapper }
        var groupOnly = {}; // key: group => { className, elements[] }

        jQuery('[class*="same_height_"], [class*="same_height-group-"]').each(function () {
          var el = this;
          var classList = getClassTokens(el);

          classList.forEach(function (c) {
            var mo = c.match(/^same_height-group-([A-Za-z0-9_-]+)$/);
            if (mo) {
              var gName = mo[1];
              if (!groupOnly[gName]) {
                groupOnly[gName] = { className: "same_height-group-" + gName, elements: [] };
              }
              groupOnly[gName].elements.push(el);
              return;
            }

            var mg = c.match(/^same_height_([A-Za-z0-9_-]+)_([A-Za-z0-9_-]+)$/);
            if (mg) {
              var targetG = mg[1];
              var group = mg[2];
              var key = targetG + "|" + group;

              if (!globals[key]) globals[key] = { target: targetG, group: group, wrappers: [] };
              globals[key].wrappers.push(el);
              return;
            }

            var ml = c.match(/^same_height_([A-Za-z0-9_-]+)$/);
            if (ml) {
              locals.push({ target: ml[1], wrapper: el });
            }
          });
        });

        return { globals: globals, locals: locals, groupOnly: groupOnly };
      };

      var refresh = function () {
        var run = function () {
          var cfgs = collectConfigs();

          // Local wrappers
          cfgs.locals.forEach(function (cfg) {
            var $targets = jQuery(cfg.wrapper).find("." + cfg.target + ":visible");
            equaliseSet($targets);
          });

          // Global groups
          Object.keys(cfgs.globals).forEach(function (key) {
            var cfg = cfgs.globals[key];
            var $targets = jQuery();
            cfg.wrappers.forEach(function (wrapper) {
              $targets = $targets.add(jQuery(wrapper).find("." + cfg.target + ":visible"));
            });
            equaliseSet($targets);
          });

          // Group-only
          Object.keys(cfgs.groupOnly).forEach(function (g) {
            var cfg = cfgs.groupOnly[g];
            equaliseSet(jQuery(cfg.elements).filter(":visible"));
          });
        };

        if (window.requestAnimationFrame) window.requestAnimationFrame(run);
        else run();
      };

      // Run now and bind lifecycle events
      refresh();
      jQuery(window).on("load", refresh);
      jQuery(window).on("resize orientationchange", debounce(refresh, 150));

      // Beaver Builder editor re-render hooks
      jQuery(document).on(
        "fl-builder.layout-rendered fl-builder.preview-rendered",
        debounce(refresh, 80)
      );

      // Optional manual trigger
      window.equalizeHeightsRefresh = refresh;
    },

    /**
     * 4) Normalise other button classes to behave like .fl-button
     * Add selectors here as needed.
     */
    initButtonNormaliser: function () {
      var selectors = [
        ".uabb-button"
      ];

      if (!selectors.length) return;

      jQuery(selectors.join(",")).addClass("fl-button");
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    SiteUI.init();
  });

})(window, document, jQuery);
