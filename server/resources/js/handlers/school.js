import { warningAddCourse, warningAddStudent, warningNavigation } from "../messages/warnings";

import { courseHandlers } from "./course";
import { studentHandlers } from "./student";

export function resetSchoolHandlers() {
    $('#students-container, #courses-container').data('unsaved', false);
    $("#add-student")
        .off("click", warningAddStudent)
        .off("click", studentHandlers.add)
        .on("click", studentHandlers.add);

    $("#add-course")
        .off("click", warningAddCourse)
        .off("click", courseHandlers.add)
        .on("click", courseHandlers.add);

    $("#students-container .item-row, #courses-container .item-row")
        .each(function () {
            const clickHandler = $(this).data("clickHandler");
            $(this)
                .off("click", warningNavigation)
                .off("click", clickHandler)
                .on("click", clickHandler);
        });
}

export function setSchoolWarningsHandler() {
    $('#students-container, #courses-container').data('unsaved', true);
    $("#add-student")
        .off("click", studentHandlers.add)
        .off("click", warningAddStudent)
        .on("click", warningAddStudent);

    $("#add-course")
        .off("click", courseHandlers.add)
        .off("click", warningAddCourse)
        .on("click", warningAddCourse);

    $("#students-container .item-row, #courses-container .item-row")
        .each(function () {
            const clickHandler = $(this).data("clickHandler");
            $(this)
                .off("click", clickHandler)
                .off("click", warningNavigation)
                .on("click", warningNavigation);
        });
}

export function removeSchoolWarningsHandler() {
    $('#students-container, #courses-container').data('unsaved', false);
    $("#add-student")
        .off("click", warningAddStudent);

    $("#add-course")
        .off("click", warningAddCourse);

    $("#students-container .item-row, #courses-container .item-row")
        .off("click", warningNavigation);
}
