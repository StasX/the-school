// api/student.js

const CourseApi = {
    getAll(page = 1) {
        return $.ajax({
            url: '/api/course',
            method: 'GET',
            data: { page }
        });
    },

    getOptions() {
        const courses = [];
        const load = page => CourseApi.getAll(page).then(response => {
            courses.push(...response.data);
            return response.meta.current_page < response.meta.last_page
                ? load(response.meta.current_page + 1)
                : courses;
        });
        return load(1);
    },

    getById(id) {
        return $.ajax({
            url: `/api/course/${id}`,
            method: 'GET'
        });
    },

    add(data) {
        return $.ajax({
            url: '/api/course',
            method: 'POST',
            data,
            processData: false,
            contentType: false
        });
    },

    update(id, data) {
        return $.ajax({
            url: `/api/course/${id}`,
            method: 'POST',
            data,
            processData: false,
            contentType: false
        });
    },

    remove(id) {
        return $.ajax({
            url: `/api/course/${id}`,
            method: 'DELETE'
        });
    }
};

export default CourseApi;
