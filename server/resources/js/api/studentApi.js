// api/student.js

const StudentApi = {
    getAll(page = 1) {
        return $.ajax({
            url: '/api/student',
            method: 'GET',
            data: { page }
        });
    },

    getById(id) {
        return $.ajax({
            url: `/api/student/${id}`,
            method: 'GET'
        });
    },

    add(data) {
        return $.ajax({
            url: '/api/student',
            method: 'POST',
            data,
            processData: false,
            contentType: false
        });
    },

    update(id, data) {
        return $.ajax({
            url: `/api/student/${id}`,
            method: 'POST',
            data,
            processData: false,
            contentType: false
        });
    },

    remove(id) {
        return $.ajax({
            url: `/api/student/${id}`,
            method: 'DELETE'
        });
    },
    unsubscribe(courseId, studentId) {
        return $.ajax({
            url: `/api/student/${studentId}/course/${courseId}`,
            method: 'DELETE'
        });
    }
};

export default StudentApi;
