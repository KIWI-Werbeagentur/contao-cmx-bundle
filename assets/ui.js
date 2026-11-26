(function () {
    'use strict';

    // hide UI until loaded
    document.querySelectorAll('.cmx--ui[data-cmx-ui]').forEach((el) => {
        el.dataset.cmxDisplay = el.style.display;
        el.style.display = 'none'
    });

    document.addEventListener('DOMContentLoaded', () => {
        // the UI is only shown in the front end preview (the toolbar sets body[data-cmx-ui])
        const toolbarWrapper = document.getElementsByTagName('cto-toolbar')[0];
        const toolbar = toolbarWrapper?.shadowRoot?.querySelector('.cto-toolbar');

        if (!toolbar) {
            return;
        }

        toolbar.addEventListener('cto-toolbar-loaded', (e) => {
            // prepare ui context as shadow DOM
            const cmxUi = document.createElement('cmx-ui');
            document.querySelector('body').appendChild(cmxUi);
            cmxUi.attachShadow({mode: 'open'});

            // pair each ui context with its article wrapper before moving it out of the article,
            // articles without markup have no wrapper to attach to
            const uiEls = [];
            document.querySelectorAll('.cmx--ui[data-cmx-ui]').forEach((el) => {
                const article = el.closest('.mod_article');

                if (!article) {
                    el.remove();
                    return;
                }

                uiEls.push({el, article});
                cmxUi.shadowRoot.appendChild(el);
            });

            repositionCmxUi(uiEls);
            window.addEventListener('resize', () => repositionCmxUi(uiEls));

            // add styles to shadow DOM
            let cmxUiCss = '';
            for(const rule of document.head.querySelector('link[data-cmx-ui]').sheet.cssRules) {
                if (rule.selectorText.startsWith('.cmx--ui')) cmxUiCss += rule.cssText;
            }
            const sheet = new CSSStyleSheet();
            sheet.replaceSync(cmxUiCss);
            cmxUi.shadowRoot.adoptedStyleSheets = [sheet];

            // show UI after loading is finished
            uiEls.forEach(({el}) => {
                el.style.display = el.dataset.cmxDisplay;
                delete el.dataset.cmxDisplay;
            });
        });
    });

    function repositionCmxUi(uiEls) {
        uiEls.forEach(({el, article}) => {
            const r = article.getBoundingClientRect();
            // position element relative to viewport boundaries, respecting scroll distance
            el.style.top = window.scrollY + r.top + 'px';
            el.style.left = window.scrollX + r.left + 'px';
            el.style.width = r.width + 'px';
            el.style.height = r.height + 'px';

            // do not shift badge out of view
            const b = el.querySelector('.cmx--ui__badge');
            const bTop = window.scrollY + b.getBoundingClientRect().top;
            if (bTop < 0) {
                b.classList.add('cmx--ui__badge--inside');
            }
        });
    }
})();
