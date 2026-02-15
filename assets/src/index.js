import "./iconedSelect.scss";
import "./ui.scss";
import Choices from "choices.js";

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.iconedSelect select').forEach(iconedSelect=>{
        const arrOptions=[]

        iconedSelect.querySelectorAll('option').forEach(iconedOption=>{
            const label = iconedOption.innerHTML.replace(/\[((.)*)\]/, "<span>[$1]</span>", iconedOption.innerHTML)

            arrOptions.push({
                value: iconedOption.value,
                label: iconedOption.dataset.icon ? `<img src="${iconedOption.dataset.icon}"/> ${label}` : label,
                id: iconedOption.value,
                selected: iconedSelect.value == iconedOption.value,
                customProperties: {
                    label: label,
                },
            })
        })

        const cmx_choices = new Choices(iconedSelect, {
            choices: arrOptions,
            allowHTML: true,
            itemSelectText: '',
            shouldSort: false,
            searchFields: ['customProperties.label'],
            classNames: {
                containerOuter: ['cmx_choices'],
                containerInner: ['cmx_choices__inner'],
                input: ['cmx_choices__input'],
                inputCloned: ['cmx_choices__input--cloned'],
                list: ['cmx_choices__list'],
                listItems: ['cmx_choices__list--multiple'],
                listSingle: ['cmx_choices__list--single'],
                listDropdown: ['cmx_choices__list--dropdown'],
                item: ['cmx_choices__item'],
                itemSelectable: ['cmx_choices__item--selectable'],
                itemDisabled: ['cmx_choices__item--disabled'],
                itemChoice: ['cmx_choices__item--choice'],
                description: ['cmx_choices__description'],
                placeholder: ['cmx_choices__placeholder'],
                group: ['cmx_choices__group'],
                groupHeading: ['cmx_choices__heading'],
                button: ['cmx_choices__button'],
                notice: ['cmx_choices__notice'],
                addChoice: ['cmx_choices__item--selectable', 'add-choice'],
            },
        })
    })
})