(function () {
  'use strict';

  var config = window.RHIANNON_DPTO_CONFIG || {};
  var targetClass = typeof config.targetClass === 'string' ? config.targetClass.trim() : '';
  var preferredOrder = Array.isArray(config.order) ? config.order.map(normalizeSlug).filter(Boolean) : [];

  if (!targetClass || preferredOrder.length === 0) {
    return;
  }

  function normalizeSlug(value) {
    return String(value || '')
      .trim()
      .toLowerCase()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function getTabSlug(item) {
    var link = item.querySelector('a[data-category-slug], button[data-category-slug], [data-category-slug]');
    if (link && link.getAttribute('data-category-slug')) {
      return normalizeSlug(link.getAttribute('data-category-slug'));
    }

    return normalizeSlug(item.textContent);
  }

  function findModules() {
    var escapedClass = window.CSS && typeof window.CSS.escape === 'function'
      ? window.CSS.escape(targetClass)
      : targetClass.replace(/[^a-zA-Z0-9_-]/g, '');

    var selectors = [
      '.et_pb_filterable_portfolio.' + escapedClass,
      '.' + escapedClass + ' .et_pb_filterable_portfolio'
    ];

    return Array.prototype.slice.call(document.querySelectorAll(selectors.join(',')));
  }

  function reorderModule(module) {
    var lists = module.querySelectorAll('.et_pb_portfolio_filters ul');

    Array.prototype.forEach.call(lists, function (list) {
      var items = Array.prototype.slice.call(list.children).filter(function (child) {
        return child.nodeType === 1;
      });

      if (items.length < 2) {
        return;
      }

      var allItems = [];
      var categoryItems = [];

      items.forEach(function (item) {
        var slug = getTabSlug(item);
        if (slug === 'all' || item.classList.contains('et_pb_portfolio_filter_all')) {
          allItems.push(item);
        } else {
          categoryItems.push({ element: item, slug: slug });
        }
      });

      var desired = allItems.slice();

      preferredOrder.forEach(function (slug) {
        categoryItems.forEach(function (entry) {
          if (entry.slug === slug && desired.indexOf(entry.element) === -1) {
            desired.push(entry.element);
          }
        });
      });

      categoryItems.forEach(function (entry) {
        if (desired.indexOf(entry.element) === -1) {
          desired.push(entry.element);
        }
      });

      var alreadyCorrect = desired.length === items.length && desired.every(function (item, index) {
        return item === items[index];
      });

      if (alreadyCorrect) {
        return;
      }

      var fragment = document.createDocumentFragment();
      desired.forEach(function (item) {
        fragment.appendChild(item);
      });
      list.appendChild(fragment);
    });
  }

  var scheduled = false;

  function run() {
    scheduled = false;
    findModules().forEach(reorderModule);
  }

  function scheduleRun() {
    if (scheduled) {
      return;
    }
    scheduled = true;
    window.requestAnimationFrame(run);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleRun, { once: true });
  } else {
    scheduleRun();
  }

  window.addEventListener('load', scheduleRun, { once: true });

  var observer = new MutationObserver(function (mutations) {
    var relevant = mutations.some(function (mutation) {
      return mutation.type === 'childList' && mutation.addedNodes.length > 0;
    });

    if (relevant) {
      scheduleRun();
    }
  });

  observer.observe(document.documentElement, {
    childList: true,
    subtree: true
  });
}());

