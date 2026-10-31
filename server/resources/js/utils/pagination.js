export function renderPagination(name, meta, loadPage) {
    const list = $(`#${name}-container`);
    let controls = $(`#${name}-pagination`);
    if (!controls.length) {
        controls = $('<nav class="my-2"></nav>')
            .attr({ id: `${name}-pagination`, 'aria-label': `${name} pages` });
        list.after(controls);
    }
    controls.empty();
    $(`#total-${name}`).text(meta.total);
    const pages = $('<ul class="pagination pagination-sm pagination-light mb-0"></ul>');
    const previousItem = $('<li class="page-item"></li>');
    const nextItem = $('<li class="page-item"></li>');
    const previous = $('<button type="button" class="page-link">Previous</button>');
    const next = $('<button type="button" class="page-link">Next</button>');
    const status = $('<span class="page-link" aria-live="polite"></span>')
        .text(meta.current_page);
    function setDisabled(item, button, disabled) {
        item.toggleClass('disabled', disabled);
        button.prop('disabled', disabled);
    }
    function restoreControls() {
        setDisabled(previousItem, previous, meta.current_page <= 1);
        setDisabled(nextItem, next, meta.current_page >= meta.last_page);
    }
    restoreControls();
    function navigate(page) {
        setDisabled(previousItem, previous, true);
        setDisabled(nextItem, next, true);
        loadPage(page).fail(() => {
            restoreControls();
            Swal.fire({ icon: 'error', text: 'Unable to load this page. Please try again.' });
        });
    }
    previous.on('click', () => navigate(meta.current_page - 1));
    next.on('click', () => navigate(meta.current_page + 1));
    pages.append(
        previousItem.append(previous),
        $('<li class="page-item active" aria-current="page"></li>').append(status),
        nextItem.append(next)
    );
    controls.append(pages);
}
