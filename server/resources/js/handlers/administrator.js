import template from "../../templates/partials/administrator.html?raw";
import { display } from "../utils/image";
import { administratorRender } from "../renders/administrator";
import Swal from "sweetalert2";
import AdministratorApi from "../api/administratorApi";
import { resetAdministrationHandlers, setAdministrationWarningsHandler } from "./administration";
import { administratorValidationConfig } from "../validations/administrator";
import AuthApi from "../api/authApi";
import { userRender } from "../renders/navbar";
import { invalidFields } from "../messages/invalidFields";
import AuthFailure from "../messages/authFailure";
import AdministratorFailure from "../messages/administratorFailure";

function isAdministratorFormChanged(administrator) {
    return (
        (administrator?.name ?? "") !== $("#name").val() ||
        (administrator?.email ?? "") !== $("#email").val() ||
        (administrator?.phone ?? "") !== $("#phone").val() ||
        (administrator?.role ?? "") !== $("#role").val() ||
        $("#password").val() !== "" ||
        !!$('#image-file')[0].files.length
    );
}

function updateAdministratorWarnings(administrator = null) {
    if (isAdministratorFormChanged(administrator)) {
        setAdministrationWarningsHandler();
    } else {
        resetAdministrationHandlers();
    }
}

export const administratorHandlers = {
    add: () => {
        const html = $(template);
        const saveBtn = html.find("#save-administrator");
        const form = html.filter("#administrators-form");
        const validator = form.validate(administratorValidationConfig());
        const fileInput = html.find("#image-file");
        const imageElement = html.find("#image-upload");
        const roleInput = html.find("#role");
        const roles = ['manager', 'sales'];
        $.each(roles, (i, role) => {
            const option = $('<option></option>');
            option.val(role);
            option.text(role);
            roleInput.append(option);
        });
        fileInput.on("change", function () { display(imageElement, this); });
        form.on("input change", () => updateAdministratorWarnings());
        form.on("submit", function (e) {
            e.preventDefault();
            if (!form.valid()) {
                invalidFields(validator);
                return;
            }
            const formData = new FormData(this);
            if (fileInput[0].files.length) {
                formData.set("image", fileInput[0].files[0]);
            }
            AdministratorApi.add(formData).done((data) => {
                resetAdministrationHandlers();
                administratorHandlers.edit(data);
                AdministratorApi.getAll().done(administrator => administratorRender(administrator)).fail(AdministratorFailure.get);
            }).fail(AdministratorFailure.add);
        });
        saveBtn.on("click", () => form.trigger("submit"));
        $("#main-container").html(html);
    },
    edit: administrator => {
        const html = $(template);
        const titleContainer = html.find("#container-title");
        const nameInput = html.find("#name");
        const phoneInput = html.find("#phone");
        const emailInput = html.find("#email");
        const fileInput = html.find("#image-file");
        const imageElement = html.find("#image-upload");
        const buttons = html.filter("#btn-row");
        const saveBtn = buttons.find("#save-administrator");
        const form = html.filter("#administrators-form");
        form.validate(administratorValidationConfig({
            edit: true,
            owner: administrator.role === "owner"
        }));
        titleContainer.text("Edit Administrator");
        const btnContainer = $('<div class="col d-flex align-items-center"></div>');
        const removeBtn = $(`
            <button type="button" class="btn btn-sm btn-dark ms-auto">
                Delete <i class="fa-regular fa-trash-can"></i>
            </button>
        `);
        const roleInput = html.find("#role");
        const roles = ['owner', 'manager', 'sales', () => roleInput.val(administrator.role)];
        if (administrator.role == 'owner') {
            roles.splice(1, 2);
        } else {
            roles.splice(0, 1);
        }
        $.each(roles, (i, role) => {

            if (typeof role != 'string') {
                role();
                return;
            }
            const option = $('<option></option>');
            option.val(role);
            option.text(role);
            roleInput.append(option);
        });


        fileInput.on("change", function () { display(imageElement, this); });
        form.on("input change", () => updateAdministratorWarnings(administrator));
        form.on("submit", function (e) {
            e.preventDefault();
            if (!form.valid()){
                invalidFields(validator);
                return;
                }
            const formData = new FormData(this);
            formData.set("_method", "PUT");
            if (fileInput[0].files.length) {
                formData.set("image", fileInput[0].files[0]);
            }
            AdministratorApi.update(administrator.id, formData).done((data) => {
                resetAdministrationHandlers();
                administratorHandlers.edit(data);
                AuthApi.auth().done((user) => userRender(user)).fail(AuthFailure.auth);
                AdministratorApi.getAll().done(administrators => administratorRender(administrators)).fail(AdministratorFailure.get);
            }).fail(AdministratorFailure.update);
        });
        saveBtn.on("click", () => form.trigger("submit"));
        if (administrator.role != 'owner') {
            removeBtn.on("click", () => administratorHandlers.remove(administrator));
            btnContainer.append(removeBtn);
        }
        buttons.append(btnContainer);
        nameInput.val(administrator.name);
        phoneInput.val(administrator.phone);
        emailInput.val(administrator.email);
        imageElement.attr("src", administrator.image);
        $("#main-container").html(html);
    },
    remove: administrator => {
        Swal.fire({
            title: `Do you really want to delete administrator: ${administrator.name}?`,
            icon: "question",
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            buttonsStyling: false,

            customClass: {
                confirmButton: "btn btn-danger",
                cancelButton: "btn btn-dark ms-2"
            }
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Removed data cannot be restored!",
                    text: "Do you want to continue?",
                    icon: "warning",
                    showCloseButton: true,
                    showCancelButton: true,
                    confirmButtonText: "Continue",
                    cancelButtonText: "Abort",
                    buttonsStyling: false,

                    customClass: {
                        confirmButton: "btn btn-danger",
                        cancelButton: "btn btn-dark ms-2"
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        AdministratorApi.remove(administrator.id).done(() => {
                            Swal.fire({
                                title: "Administrator deleted successfully!",
                                icon: "success",
                            }).then(() => {
                                AdministratorApi.getAll().done(administrator => administratorRender(administrator)).fail(AdministratorFailure.get);
                                $("#main-container").html("");
                            });
                        }).fail(AdministratorFailure.remove);
                    }
                });
            }
        });
    }
}
