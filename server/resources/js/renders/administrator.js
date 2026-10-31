import { renderPagination } from '../utils/pagination';
import AdministratorApi from '../api/administratorApi';
import { setAdministrationWarningsHandler } from '../handlers/administration';
import administratorListItemTemplate from "../../templates/partials/administratorListItem.html?raw";
import { administratorHandlers } from "../handlers/administrator";

export function administratorRender(response) {
    const data = response.data;
    renderPagination('administrators', response.meta, page =>
        AdministratorApi.getAll(page).done(administratorRender)
    );
    $("#administrators-container").html("");
    $.each(data, (i, administrator) => {
        const html = $(administratorListItemTemplate);
        html.find(".administrator-name").text(administrator.name);
        html.find(".administrator-role").text(administrator.role);
        html.find(".administrator-img").attr({ "src": administrator.image, "alt": administrator.name });
        const clickHandler = () => administratorHandlers.edit(administrator);
        html.data("clickHandler", clickHandler);
        html.on("click", clickHandler);
        $("#administrators-container").append(html);
    });
    if ($('#administrators-container').data('unsaved')) {
        setAdministrationWarningsHandler();
    }
}
