import template from '../templates/pages/school.html?raw';
import { courseHandlers } from './handlers/course';
import { studentRender } from "./renders/student";
import { courseRender } from "./renders/course";
import { studentHandlers } from './handlers/student';
import { navbarRender } from './renders/navbar';
import StudentApi from './api/studentApi';
import CourseApi from './api/courseApi';
import StudentFailure from './messages/studentFailure';
import { CourseFailure } from './messages/courseFailure';

export default function school(user) {
    $('body').html(template);
    navbarRender(user);
    StudentApi.getAll().done((data) => {
        studentRender(data);
        $('#total-students').text(data.length);
    }).fail(StudentFailure.get);
    $("#add-student").on("click", studentHandlers.add);
    CourseApi.getAll().done((data) => {
        courseRender(data);
        $('#total-courses').text(data.length);
    }).fail(CourseFailure.get);
    $("#add-course").on("click", courseHandlers.add);

}
