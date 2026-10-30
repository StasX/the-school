
import template from "../../templates/partials/course.html?raw";
import deleteButtonTemplate from "../../templates/partials/deleteButton.html?raw";
import { courseRender, courseInfoRender } from "../renders/course";
import { display } from "../utils/image";
import CourseApi from "../api/courseApi";
import { resetSchoolHandlers, setSchoolWarningsHandler } from "./school";
import AuthApi from "../api/authApi";
import { courseValidationConfig } from "../validations/course";
import { invalidFields } from '../messages/invalidFields';
import AuthFailure from "../messages/authFailure";
import {CourseFailure} from "../messages/courseFailure";

function isCourseFormChanged(course) {
    return (
        (course?.name ?? "") !== $("#name").val() ||
        (course?.description ?? "") !== $("#description").val() ||
        !!$('#image-file')[0].files.length
    );
}

function updateCourseWarnings(course = null) {
    if (isCourseFormChanged(course)) {
        setSchoolWarningsHandler();
    } else {
        resetSchoolHandlers();
    }
}

export const courseHandlers = {
    info: id => {
        CourseApi.getById(id).done(data => {
            AuthApi.auth().done((admin) => {
                courseInfoRender(data, admin);
            }).fail(AuthFailure.auth);
        }).fail(CourseFailure.get);
    },

    add: () => {
        const html = $(template);
        const form = html.filter("#courses-form");

        const validator = form.validate(courseValidationConfig());
        const fileInput = html.find("#image-file");
        const imageElement = html.find("#image-upload");
        html.find("#total").text(0);
        fileInput.on("change", function () { display(imageElement, this); });
        form.on("input change", () => updateCourseWarnings());
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
            CourseApi.add(formData).done(data => {
                resetSchoolHandlers();
                courseHandlers.info(data.id);
                CourseApi.getAll().done(courses => courseRender(courses)).fail(CourseFailure.get);
            }).fail(CourseFailure.add);
        });
        html.find("#save-course").on("click", () => form.trigger("submit"));
        $("#main-container").html(html);
    },

    edit: course => {
        const html = $(template);
        if (!course.students.length) {
            html.filter('#btn-row').append(deleteButtonTemplate);
        }
        const form = html.filter("#courses-form");

        const validator = form.validate(courseValidationConfig({ edit: true }));
        html.find("#container-title").text("Edit Course");
        html.find("#name").val(course.name);
        html.find("#description").val(course.description);
        const imageElement = html.find("#image-upload");
        const totalsRow = $(
            `<div class="col">
            <em>Total <b id="total"></b> students taking this course</em>
        </div>`
        );
        totalsRow.find("#total").text(course.students.length);
        html.filter("#totals-row").html(totalsRow);
        imageElement.attr("src", course.image);
        html.find("#image-file").on("change", function () { display(imageElement, this); });
        form.on("input change", () => updateCourseWarnings(course));
        html.find("#delete-course").on("click", () => courseHandlers.remove(course));
        form.on("submit", function (e) {
            e.preventDefault();
            if (!form.valid()) {
                invalidFields(validator);
                return;
            }
            const formData = new FormData(this);
            formData.set("_method", "PUT");
            CourseApi.update(course.id, formData).done(data => {
                resetSchoolHandlers();
                courseHandlers.info(data.id);
                CourseApi.getAll().done(courses => courseRender(courses)).fail(CourseFailure.get);
            }).fail(CourseFailure.update);
        });
        html.find("#save-course").on("click", () => form.trigger("submit"));
        $("#main-container").html(html);
    },

    remove: course => {
        Swal.fire({
            title: `Do you really want to delete course: ${course.name}?`,
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
                }).then(result => {
                    if (result.isConfirmed) {
                        CourseApi.remove(course.id).done(() => {
                            Swal.fire({
                                title: "Course deleted successfully!",
                                icon: "success",
                            }).then(() => {
                                CourseApi.getAll().done(courses => courseRender(courses)).fail(CourseFailure.get);
                                $("#main-container").html("");
                            });
                        }).fail(CourseFailure.remove);
                    }
                });
            }
        });
    }
}
