import '../styles/iconedSelect.scss';

const initIconedSelects = () => {
    document.querySelectorAll('.cmx--iconedSelect select').forEach(iconedSelect=>{
        if (iconedSelect.closest('.choices')) return; // already initialized

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

        // Make the choices instance available on the element (e.g. for cleanup)
        iconedSelect._choices = choices;
    })
}

document.addEventListener('DOMContentLoaded', initIconedSelects);
document.addEventListener('turbo:load', initIconedSelects);
document.addEventListener('turbo:before-cache', () => {
    document.querySelectorAll('.cmx--iconedSelect select').forEach(select => {
        select._choices?.destroy();
        select._choices = null;
    });
});
