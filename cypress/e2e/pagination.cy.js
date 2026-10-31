describe('List pagination', () => {
    const records = name => Array.from({ length: 21 }, (_, index) => ({
        id: index + 1,
        name: `${name} ${index + 1}`,
        email: `${name}${index + 1}@example.com`,
        phone: '0501234567',
        image: '/upload/NotFound.png',
        description: 'Test course',
        role: 'manager',
    }));

    beforeEach(() => {
        cy.intercept('GET', '/api/auth', {
            id: 100,
            name: 'Owner',
            role: 'owner',
            image: '/upload/NotFound.png',
        });
        ['student', 'course', 'administrator'].forEach(name => {
            cy.intercept('GET', `/api/${name}?*`, request => {
                const page = Number(request.query.page || 1);
                request.reply({
                    data: records(name).slice((page - 1) * 20, page * 20),
                    meta: { current_page: page, last_page: 2, total: 21 },
                });
            }).as(name);
        });
        cy.intercept('GET', '/api/administrator/count', { count: 21 });
    });

    it('pages students and courses independently and keeps total counts', () => {
        cy.visit('/#!school');
        cy.get('#students-container .item-row').should('have.length', 20);
        cy.get('#total-students').should('have.text', '21');
        cy.get('#total-courses').should('have.text', '21');
        cy.contains('#students-pagination button', 'Previous').should('be.disabled');
        cy.contains('#students-pagination button', 'Next').click();
        cy.get('#students-container .item-row').should('have.length', 1);
        cy.contains('#students-container', 'student 21').should('be.visible');
        cy.get('#courses-container .item-row').should('have.length', 20);
        cy.contains('#students-pagination button', 'Next').should('be.disabled');
        cy.contains('#students-pagination button', 'Previous').click();
        cy.get('#students-container .item-row').should('have.length', 20);
        cy.contains('#courses-pagination button', 'Next').click();
        cy.get('#courses-container .item-row').should('have.length', 1);
        cy.get('#total-courses').should('have.text', '21');
    });

    it('loads enrollment choices from every course page and preserves form warnings', () => {
        cy.visit('/#!school');
        cy.get('#add-student').click();
        cy.get('.courses-container input[type="checkbox"]').should('have.length', 21);
        cy.get('#name').type('Unsaved student');
        cy.contains('#students-pagination button', 'Next').click();
        cy.get('#students-container .item-row').should('have.length', 1).first().click();
        cy.contains('.swal2-popup', 'all changes will be discarded').should('be.visible');
        cy.contains('.swal2-popup button', 'Cancel').click();
        cy.get('#name').should('have.value', 'Unsaved student');
    });

    it('pages administrators', () => {
        cy.visit('/#!administration');
        cy.get('#administrators-container .item-row').should('have.length', 20);
        cy.contains('#administrators-pagination button', 'Next').click();
        cy.get('#administrators-container .item-row').should('have.length', 1);
        cy.contains('#administrators-container', 'administrator 21').should('be.visible');
        cy.contains('#administrators-pagination button', 'Next').should('be.disabled');
    });
});
