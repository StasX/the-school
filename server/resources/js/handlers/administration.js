import { warningAddAdministrator, warningNavigation } from "../messages/warnings";

import { administratorHandlers } from "./administrator";

export function resetAdministrationHandlers() {
    $('#administrators-container').data('unsaved', false);
    $("#add-administrator")
        .off("click", warningAddAdministrator)
        .off("click", administratorHandlers.add)
        .on("click", administratorHandlers.add);

    $("#administrators-container .item-row")
        .each(function () {
            const clickHandler = $(this).data("clickHandler");

            $(this)
                .off("click", warningNavigation)
                .off("click", clickHandler)
                .on("click", clickHandler);
        });
}

export function setAdministrationWarningsHandler() {
    $('#administrators-container').data('unsaved', true);
    $("#add-administrator")
        .off("click", administratorHandlers.add)
        .off("click", warningAddAdministrator)
        .on("click", warningAddAdministrator);

    $("#administrators-container .item-row")
        .each(function () {
            const clickHandler = $(this).data("clickHandler");

            $(this)
                .off("click", clickHandler)
                .off("click", warningNavigation)
                .on("click", warningNavigation);
        });
}

export function removeAdministrationWarningsHandler() {
    $('#administrators-container').data('unsaved', false);
    $("#add-administrator")
        .off("click", warningAddAdministrator);

    $("#administrators-container .item-row")
        .off("click", warningNavigation);
}
