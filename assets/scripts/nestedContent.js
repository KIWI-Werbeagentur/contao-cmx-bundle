// Elementliste im Backend: Kindelemente einer Gruppe (.nested-records, Kiwi\Contao\CmxBundle\NestedContent) per
// Drag & Drop am Griff (.nested-records__handle) oder mit den Pfeiltasten auf dem Griff umsortieren.
// Natives HTML-Drag-and-drop, nur innerhalb derselben Gruppe. Gespeichert wird wie in Contaos
// Sortierung (act=cut, mode=1 nach dem Vorgänger, mode=2 an erster Stelle), aber über die
// Kindelement-Ansicht der Gruppe (data-sort-url), damit die Elemente in der Gruppe bleiben.
(() => {
  if (window.cmxNestedSortable) {
    return;
  }

  window.cmxNestedSortable = true;

  let dragged = null;
  let startIndex = -1;

  const records = (list) => [...list.children].filter((el) => el.classList.contains('nested-records__record'));

  const requestToken = () =>
    document.querySelector('[data-contao--sortable-request-token-value]')?.dataset.contaoSortableRequestTokenValue
    ?? window.Contao?.request_token
    ?? '';

  const save = (record) => {
    const list = record.parentElement;
    const previous = records(list)[records(list).indexOf(record) - 1];
    const url = new URL(list.dataset.sortUrl, window.location.href);

    url.searchParams.set('act', 'cut');
    url.searchParams.set('id', record.dataset.id);
    url.searchParams.set('rt', requestToken());

    if (previous) {
      url.searchParams.set('pid', previous.dataset.id);
      url.searchParams.set('mode', '1');
    } else {
      url.searchParams.set('pid', list.dataset.pid);
      url.searchParams.set('mode', '2');
    }

    fetch(url, { redirect: 'manual' });
  };

  // erst am Griff wird der Eintrag ziehbar (sonst ließe sich Text in der Vorschau nicht markieren)
  document.addEventListener('pointerdown', (event) => {
    const handle = event.target.closest('.nested-records__handle');

    if (handle) {
      handle.closest('.nested-records__record').draggable = true;
    }
  });

  document.addEventListener('dragstart', (event) => {
    const record = event.target.closest?.('.nested-records__record');

    if (!record || !record.draggable) {
      return;
    }

    // nicht an Contaos Sortierung der äußeren Liste weitergeben
    event.stopPropagation();
    dragged = record;
    startIndex = records(record.parentElement).indexOf(record);
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', record.dataset.id);
    requestAnimationFrame(() => record.classList.add('nested-records__record--dragging'));
  }, true);

  document.addEventListener('dragover', (event) => {
    if (!dragged) {
      return;
    }

    const list = dragged.parentElement;
    const over = event.target.closest('.nested-records__record');

    // nur innerhalb derselben Gruppe
    if (!over || over === dragged || over.parentElement !== list) {
      return;
    }

    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';

    const box = over.getBoundingClientRect();
    over[event.clientY < box.top + box.height / 2 ? 'before' : 'after'](dragged);
  });

  document.addEventListener('drop', (event) => {
    if (dragged) {
      event.preventDefault();
    }
  });

  document.addEventListener('dragend', () => {
    if (!dragged) {
      return;
    }

    const record = dragged;
    dragged = null;
    record.draggable = false;
    record.classList.remove('nested-records__record--dragging');

    if (records(record.parentElement).indexOf(record) !== startIndex) {
      save(record);
    }
  });

  // Tastatur: Pfeil hoch/runter auf dem Griff
  document.addEventListener('keydown', (event) => {
    const handle = event.target.closest?.('.nested-records__handle');

    if (!handle || !['ArrowUp', 'ArrowDown'].includes(event.key)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const record = handle.closest('.nested-records__record');
    const sibling = event.key === 'ArrowUp' ? record.previousElementSibling : record.nextElementSibling;

    if (!sibling?.classList.contains('nested-records__record')) {
      return;
    }

    sibling[event.key === 'ArrowUp' ? 'before' : 'after'](record);
    save(record);
    handle.focus();
  }, true);
})();
