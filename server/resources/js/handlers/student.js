import template from "../../templates/partials/student.html?raw";
import courseCheckboxTemplate from "../../templates/partials/courseCheckbox.html?raw";
import { display } from "../utils/image";
import { studentInfoRender, studentRender } from "../renders/student";
import Swal from "sweetalert2";
import StudentApi from "../api/studentApi";
import CourseApi from "../api/courseApi";
import { haveSameElements } from "../utils/arrays";
import { resetSchoolHandlers, setSchoolWarningsHandler } from "./school";
import { courseHandlers } from "./course";
import { studentValidationConfig } from "../validations/student";
import { invalidFields } from "../messages/invalidFields";
import StudentFailure from "../messages/studentFailure";

function isStudentFormChanged(student) {
    const currentCourses = (student?.courses || []).map(obj => obj.id);
    const selectedCourses = [];
    $('input[name="course[]"]:checked').each(function () {
        selectedCourses.push($(this).val());
    });
    return (
        (student?.name ?? "") !== $("#name").val() ||
        (student?.email ?? "") !== $("#email").val() ||
        (student?.phone ?? "") !== $("#phone").val() ||
        !!$('#image-file')[0].files.length ||
        !haveSameElements(currentCourses, selectedCourses)
    );
}

function updateStudentWarnings(student = null) {
    if (isStudentFormChanged(student)) {
        setSchoolWarningsHandler();
    } else {
        resetSchoolHandlers();
    }
}

export const studentHandlers = {
    info: (id) => {
        StudentApi.getById(id).done((data) => {
            studentInfoRender(data);
        }
        ).fail(StudentFailure.get);
    },
    add: () => {
        const html = $(template);
        const saveBtn = html.find("#save-student");
        const form = html.filter("#students-form");
        const validator = form.validate(studentValidationConfig());
        const fileInput = html.find("#image-file");
        const imageElement = html.find("#image-upload");
        const coursesContainer = form.find(".courses-container");
        fileInput.on("change", function () { display(imageElement, this); });
        form.on("input change", () => updateStudentWarnings());
        form.on("submit", function (e) {
            e.preventDefault();
            if (!form.valid()) {
                invalidFields(validator);
                return;
            }
            if (!form.valid()) return;
            const formData = new FormData(this);
            if (fileInput[0].files.length) {
                formData.set("image", fileInput[0].files[0]);
            }
            StudentApi.add(formData).done((data) => {
                resetSchoolHandlers();
                studentHandlers.info(data.id);
                StudentApi.getAll().done((students) => studentRender(students)).fail(StudentFailure.get);
            }).fail(StudentFailure.add);
        });
        saveBtn.on("click", () => form.trigger("submit"));
        CourseApi.getAll().done((data) => $.each(data, (i, course) => {
            const $course = $(courseCheckboxTemplate);
            const input = $course.find("input");
            input.val(course.id);
            input.attr("id", `course-${course.id}`);
            const label = $course.find("label");
            label.attr("for", `course-${course.id}`);
            label.text(course.name);
            coursesContainer.append($course);
        }));

        $("#main-container").html(html);
    },
    edit: (student) => {
        const html = $(template);
        const titleContainer = html.find("#container-title");
        const nameInput = html.find("#name");
        const phoneInput = html.find("#phone");
        const emailInput = html.find("#email");
        const fileInput = html.find("#image-file");
        const imageElement = html.find("#image-upload");
        const buttons = html.filter("#btn-row");
        const saveBtn = buttons.find("#save-student");
        const form = html.filter("#students-form");
        const validator = form.validate(studentValidationConfig({ edit: true }));
        const coursesContainer = form.find(".courses-container");
        titleContainer.text("Edit Student");
        const btnContainer = $('<div class="col d-flex align-items-center"></div>');
        const removeBtn = $(`
            <button type="button" class="btn btn-sm btn-dark ms-auto">
                Delete <i class="fa-regular fa-trash-can"></i>
            </button>
        `);
        fileInput.on("change", function () {
            display(imageElement, this);
        });
        form.on("input change", () => updateStudentWarnings(student));
        form.on("submit", function (e) {
            e.preventDefault();
            if (!form.valid()) {
                invalidFields(validator);
                return;
            }
            const formData = new FormData(this);
            formData.set("_method", "PUT");
            if (fileInput[0].files.length) {
                formData.set("image", fileInput[0].files[0]);
            }
            StudentApi.update(student.id, formData).done((data) => {
                resetSchoolHandlers();
                studentHandlers.info(data.id);
                StudentApi.getAll().done(students => studentRender(students)).fail(StudentFailure.get);
            }).fail(StudentFailure.update);
        });
        removeBtn.on("click", () => studentHandlers.remove(student));
        saveBtn.on("click", () => form.trigger("submit"));
        btnContainer.append(removeBtn);
        buttons.append(btnContainer);
        nameInput.val(student.name);
        phoneInput.val(student.phone);
        emailInput.val(student.email);
        imageElement.attr("src", student.image);
        const subscriptions = student.courses.map(obj => obj.id);
        CourseApi.getAll().done(data => $.each(data, (id, course) => {
            const $course = $(courseCheckboxTemplate);
            const input = $course.find("input");
            input.val(course.id);
            input.attr("id", `course-${course.id}`);
            const label = $course.find("label");
            label.attr("for", `course-${course.id}`);
            label.text(course.name);
            if (subscriptions.includes(course.id)) {
                $course.find(`#course-${course.id}`).prop("checked", true);
            }
            coursesContainer.append($course);
        }));
        $("#main-container").html(html);
    },
    remove: student => {
        Swal.fire({
            title: `Do you really want to delete student: ${student.name}?`,
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
                        StudentApi.remove(student.id).done(() => {
                            Swal.fire({
                                title: "Student deleted successfully!",
                                icon: "success",
                            }).then(() => {
                                StudentApi.getAll().done(students => studentRender(students)).fail(StudentFailure.get);
                                $("#main-container").html("");
                            });
                        }).fail(StudentFailure.remove);
                    }
                });
            }
        });
    },
    unsubscribe: (courseId, studentId) => {
        StudentApi.unsubscribe(courseId, studentId).done(() => {
            courseHandlers.info(courseId);
        }).fail(StudentFailure.unsubscribe);
    }
}
