// eslint-disable-next-line
((Drupal, once) => {
  'use strict';

  const drawerRegistry = new WeakMap();

  Drupal.behaviors.material_custom_Functions = {
    // eslint-disable-next-line
    attach: (context, settings) => {
      // Highlight active sidebar filters by matching href to the current path.
      once('sidebarActiveLinks', '.sidebar-content-layout__sidebar', context).forEach(sidebar => {
        const currentPath = (window.location.pathname || '/').replace(/\/+$/, '') || '/';

        sidebar.querySelectorAll('a[href]').forEach(link => {
          const linkPath = new URL(link.getAttribute('href'), window.location.origin)
            .pathname.replace(/\/+$/, '') || '/';

          if (linkPath === currentPath) {
            link.classList.add('is-active');
          }
        });
      });

      // Fade view content when filters trigger an AJAX refresh.
      once('viewAjaxFade', 'body', context).forEach(() => {
        const $doc = window.jQuery ? window.jQuery(document) : null;
        if (!$doc) {
          return;
        }

        $doc
          .on('ajaxSend', (event, xhr, settingsAjax) => {
            const wrapper = settingsAjax && settingsAjax.wrapper ? document.getElementById(settingsAjax.wrapper) : null;
            if (wrapper && wrapper.classList.contains('views-element-container')) {
              wrapper.classList.add('is-updating');
            }
          })
          .on('ajaxComplete', (event, xhr, settingsAjax) => {
            const wrapper = settingsAjax && settingsAjax.wrapper ? document.getElementById(settingsAjax.wrapper) : null;
            if (wrapper && wrapper.classList.contains('views-element-container')) {
              wrapper.classList.remove('is-updating');
            }
          });
      });

      // Add drawer close button and ensure right-side drawer closes on click.
      once('drawerCloseButton', '.mdc-drawer--modal', context).forEach(drawerEl => {
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'mdc-drawer__close';
        closeBtn.setAttribute('aria-label', 'Close menu');
        closeBtn.innerHTML = '<span class="close-line close-line--one"></span><span class="close-line close-line--two"></span>';
        drawerEl.appendChild(closeBtn);

        // Manual control instead of relying on MDC instance (prevents conflicts).
        const scrim = drawerEl.parentElement && drawerEl.parentElement.querySelector('.mdc-drawer-scrim');

        const closeDrawer = () => {
          drawerEl.style.setProperty('transform', 'translateX(100%)', 'important');
          drawerEl.classList.add('mdc-drawer--closing');
          drawerEl.classList.remove('mdc-drawer--open', 'mdc-drawer--opening');
          document.body.classList.remove('drawer-open');
          if (scrim) {
            scrim.style.opacity = '0';
            scrim.style.pointerEvents = 'none';
          }
          setTimeout(() => {
            drawerEl.classList.remove('mdc-drawer--closing');
          }, 350);
        };

        const openDrawer = () => {
          drawerEl.style.setProperty('left', 'auto');
          drawerEl.style.setProperty('right', '0');
          drawerEl.style.setProperty('transition', 'transform 0.5s ease-in-out');
          drawerEl.style.setProperty('transform', 'translateX(0)', 'important');
          drawerEl.classList.add('mdc-drawer--open');
          drawerEl.classList.remove('mdc-drawer--closing');
          document.body.classList.add('drawer-open');
          if (scrim) {
            scrim.style.opacity = '0.08';
            scrim.style.pointerEvents = 'auto';
          }
        };

        closeBtn.addEventListener('click', () => closeDrawer());

        if (scrim) {
          scrim.addEventListener('click', () => closeDrawer());
        }

        // Close on outside click (fallback if scrim click doesn't fire).
        once('drawerOutsideClose', 'body', context).forEach(() => {
          document.addEventListener('click', event => {
            const isOpen = drawerEl.classList.contains('mdc-drawer--open');
            const clickedInsideDrawer = drawerEl.contains(event.target);
            const clickedToggle = event.target.closest && event.target.closest('.drawer-toggle__button');
            if (isOpen && !clickedInsideDrawer && !clickedToggle) {
              closeDrawer();
            }
          });
        });

        // Force drawer positioning/animation to the right even if MDC CSS overrides.
        const applyPositioning = () => {
          drawerEl.style.setProperty('left', 'auto');
          drawerEl.style.setProperty('right', '0');
          drawerEl.style.setProperty('transition', 'transform 0.5s ease-in-out');
          const isOpening = drawerEl.classList.contains('mdc-drawer--open') || drawerEl.classList.contains('mdc-drawer--opening');
          const isClosing = drawerEl.classList.contains('mdc-drawer--closing');
          drawerEl.style.setProperty('transform', isOpening && !isClosing ? 'translateX(0)' : 'translateX(100%)', 'important');
        };

        applyPositioning();
        drawerEl.addEventListener('MDCDrawer:opened', applyPositioning);
        drawerEl.addEventListener('MDCDrawer:closed', applyPositioning);

        const observer = new MutationObserver(applyPositioning);
        observer.observe(drawerEl, { attributes: true, attributeFilter: ['class'] });

        // Ensure toggle button opens the drawer; reapply positioning before open.
        once('drawerToggleBind', '.drawer-toggle__button', context).forEach(btn => {
          btn.addEventListener('click', event => {
            event.preventDefault();
            applyPositioning();
            openDrawer();
          });
        });
      });

      // Put your common functions and handlers here. For example:
      //
      // const myCommonFunction = (element, event) => {
      //   // Do something.
      // }
      // Put global page behaviors here.
      // It is Drupal's equivalent JQuery's $(document).ready(myInit());
      // Example:
      //
      // once('myGlobalBehaviors', 'html').forEach(() => {
      //   const singleElement = document.getElementById('element-id');
      //   singleElement.addEventListener('click', event => myCommonFunction(singleElement, event));
      //
      //   const multipleElements = document.querySelectorAll('.classname-selector');
      //   multipleElements.forEach(element => {
      //     element.addEventListener('click', event => myCommonFunction(element, event));
      //   });
      // });
      // Put your specific behaviors with Ajax loading support here. For example:
      //
      // once('mySpecificBehavior', '.classname-selector', context).forEach(element => {
      //   element.addEventListener('click', event => myCommonFunction(element, event));
      // });
    },
  };
})(Drupal, once);
