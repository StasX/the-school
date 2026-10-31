import { renderPagination } from '../utils/pagination';
import StudentApi from '../api/studentApi';
import { setSchoolWarningsHandler } from '../handlers/school';
import { studentHandlers } from '../handlers/student';
import studentListItemTemplate from "../../templates/partials/studentListItem.html?raw";
import studentInfoTemplate from "../../templates/partials/studentInfo.html?raw";
import memberItemTemplate from "../../templates/partials/memberOf.html?raw";

export function studentRender(response) {
    const data = response.data;
    renderPagination('students', response.meta, page =>
        StudentApi.getAll(page).done(studentRender)
    );
    $("#students-container").html('');
    $.each(data, (i, student) => {
        const html = $(studentListItemTemplate);
        html.find(".student-name").text(student.name);
        html.find(".student-phone").text(student.phone);
        html.find(".student-img").attr({ "src": student.image, "alt": student.name });
        const clickHandler = () => studentHandlers.info(student.id);
        html.data("clickHandler", clickHandler);
        html.on("click", clickHandler);
        $("#students-container").append(html);
    });
    if ($('#students-container').data('unsaved')) {
        setSchoolWarningsHandler();
    }
}

export function studentInfoRender(data) {
    const html = $(studentInfoTemplate);
    html.find("#student-img").attr({ "src": data.image, "alt": data.name });
    html.find("#student-name").text(data.name);
    html.find("#student-phone").text(data.phone);
    html.find("#student-email").text(data.email);
    const coursesElement = html.find("#member-of");
    $.each(data.courses, (i, course) => {
        const memberItem = $(memberItemTemplate);
        memberItem.find(".course-name").text(course.name);
        memberItem.find(".course-img").attr({ "alt": course.name, "src": course.image });
        coursesElement.append(memberItem);
    });
    html.find("#edit").on("click", () => studentHandlers.edit(data));
    $("#main-container").html(html);
}

