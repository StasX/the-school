import { renderPagination } from '../utils/pagination';
import CourseApi from '../api/courseApi';
import { setSchoolWarningsHandler } from '../handlers/school';
import { courseHandlers } from '../handlers/course';
import courseListItemTemplate from "../../templates/partials/courseListItem.html?raw";
import courseInfoTemplate from "../../templates/partials/courseInfo.html?raw";
import courseMemberTemplate from "../../templates/partials/courseMember.html?raw";
import { studentHandlers } from '../handlers/student';

export function courseRender(response) {
    const data = response.data;
    renderPagination('courses', response.meta, page =>
        CourseApi.getAll(page).done(courseRender)
    );
    $("#courses-container").html("");
    $.each(data, (i, course) => {
        const html = $(courseListItemTemplate);
        html.find(".course-name").text(course.name);
        html.find(".course-description").text(course.description);
        html.find(".course-img").attr({ "src": course.image, "alt": course.name });
        const clickHandler = () => courseHandlers.info(course.id);
        html.data("clickHandler", clickHandler);
        html.on("click", clickHandler);
        $("#courses-container").append(html);
    });
    if ($('#courses-container').data('unsaved')) {
        setSchoolWarningsHandler();
    }
}

export function courseInfoRender(data,admin) {
    const html = $(courseInfoTemplate);
    if (["owner","manager"].includes(admin.role)){
        const editBtn = $('<button class="btn btn-sm btn-dark ms-auto" id="edit">Edit</button>');
        editBtn.on("click", () => courseHandlers.edit(data));
        html.find("#head-row").append(editBtn);
}
    html.find("#course-img").attr({ "src": data.image, "alt": data.name });
    html.find("#course-name").text(`${data.name}, ${data.students.length} Students`);
    html.find("#course-description").text(data.description);
    const studentsContainer = html.find("#members-list");
    $.each(data.students, (i, student) => {
        const member = $(courseMemberTemplate);
        member.find(".member-img").attr({ "alt": student.name, "src": student.image });
        member.find(".member-name").text(student.name);
        member.find(".fa-xmark").on("click", e => {
            e.stopPropagation();
            studentHandlers.unsubscribe(data.id, student.id);
        });
        studentsContainer.append(member);
    });
    $("#main-container").html(html);
}


