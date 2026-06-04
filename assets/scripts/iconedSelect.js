import '../styles/iconedSelect.scss';

const initIconedSelects = () => {
    document.querySelectorAll('select.cmx--iconedSelect').forEach(iconedSelect=>{
        try {
            if (typeof Choices !== 'undefined') {
                // Native Choices (Contao 5.7+). Guard on the real DOM state, not
                // an intent flag set up front: if an earlier run happened before
                // Choices was ready or threw, a later event still initialises it.
                if (!iconedSelect.closest('.choices')) {
                    initNativeChoices(iconedSelect);
                }
            } else if (window.MooTools) {
                // Legacy MooTools chosen (Contao 5.3); idempotent via applyIcon.
                initLegacyChosen(iconedSelect);
            }
            // Neither engine ready yet (e.g. 5.7 before Choices has loaded): do
            // nothing and let a later DOMContentLoaded/turbo:load/ajax_change retry,
            // rather than latch the wrong path or mutate the option HTML.
        } catch (e) {
            console.error('cmx iconedSelect: initialisation failed', e);
        }
    });
}

// Contao 5.7+: the native Choices.js component is available as a global.
function initNativeChoices(iconedSelect) {
    const arrOptions=[]

    // Add icon
    iconedSelect.querySelectorAll('option').forEach(iconedOption=>{
        let label = iconedOption.innerHTML;
        // Add span around bracketed text parts (unless a span is already present)
        if (!iconedOption.innerHTML.includes(']</span>')) {
            label = iconedOption.innerHTML.replace(/\[(.*)\]/, "<span>[$1]</span>")
        }

        arrOptions.push({
            value: iconedOption.value,
            label: iconedOption.dataset.icon ? `<img src="${iconedOption.dataset.icon}"/> ${label}` : label,
            id: iconedOption.value,
            selected: iconedOption.selected,
            customProperties: { label },
        })
    })

    // Copied and adjusted from Contao's choices implementation
    const choices = new Choices(iconedSelect, {
        choices: arrOptions,
        shouldSort: false,
        duplicateItemsAllowed: false,
        removeItemButton: iconedSelect.multiple,
        allowHTML: true, // needed to show the icons
        searchEnabled: iconedSelect.options.length > 7,
        searchResultLimit: -1,
        appendGroupInSearch: true,
        classNames: {
            containerOuter: ['choices', ...Array.from(iconedSelect.classList)],
        },
        fuseOptions: {
            includeScore: true,
            threshold: 0.4,
        },
        callbackOnInit: () => {
            const choices = iconedSelect.closest('.choices')?.querySelector('.choices__list--dropdown > .choices__list');

            if (choices && iconedSelect.dataset.placeholder) {
                choices.dataset.placeholder = iconedSelect.dataset.placeholder;
            }
        },
        loadingText: Contao.lang.loading,
        noResultsText: Contao.lang.noResults,
        noChoicesText: Contao.lang.noOptions,
        removeItemLabelText: (value) => Contao.lang.removeItem.concat(' ').concat(value),
    })

    // Make the choices instance available on the element for turbo:before-cache cleanup.
    iconedSelect._choices = choices;
}

// Contao 5.3: the bundled MooTools "Chosen" plugin renders each option's
// innerHTML (its SelectParser reads option.get('html')) and already wraps the
// trailing [..] part in a <span class="label-info">, so we only inject the icon
// — no bracket span here, to avoid duplicating it.
function initLegacyChosen(iconedSelect) {
    iconedSelect.querySelectorAll('option').forEach(applyIcon);

    // If Chosen has already initialised, ask it to re-render from the updated
    // options. If it runs after us, it reads the injected <img> directly.
    if (window.MooTools && window.document.id) {
        document.id(iconedSelect).fireEvent('liszt:updated');
    }
}

// The HTML parser strips <img> inside <select>, but the option.innerHTML setter
// keeps it. So the server only attaches a data-icon attribute and the icon is
// injected here, into the option's own HTML.
function applyIcon(option) {
    if (!option.dataset.icon || option.dataset.cmxIconApplied) {
        return;
    }
    option.innerHTML = `<img src="${option.dataset.icon}"/> ${option.innerHTML}`;
    option.dataset.cmxIconApplied = '1';
}

document.addEventListener('DOMContentLoaded', initIconedSelects);

// Contao 5.7+: the back end navigates with Turbo, which only fires
// DOMContentLoaded on the initial hard load. Re-init on each navigation, and
// tear the Choices instances down before Turbo snapshots the page (otherwise
// the cached DOM keeps the transformed widget and re-init can't run).
document.addEventListener('turbo:load', initIconedSelects);
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('select.cmx--iconedSelect').forEach(select => {
        // destroy() removes the .choices wrapper, so the state-based guard in
        // initIconedSelects lets turbo:load re-initialise the restored select.
        select._choices?.destroy();
        select._choices = null;
    });
});

// Contao 5.3 re-initialises chosen selects after ajax (subpalettes, wizards).
// Re-run so freshly inserted icon selects get their icons too. MooTools fires
// 'ajax_change' on the window via its window.addEvent helper. Register on
// DOMContentLoaded so MooTools has certainly loaded, independent of where the
// backend template renders TL_JAVASCRIPT relative to the MooTools script.
document.addEventListener('DOMContentLoaded', () => {
    if (window.addEvent) {
        window.addEvent('ajax_change', initIconedSelects);
    }
});
