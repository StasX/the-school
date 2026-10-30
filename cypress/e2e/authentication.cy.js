describe('Authentication', () => {
    beforeEach(() => {
        cy.visit('/');
    });

    it('displays the login form', () => {
        cy.get('#login').should('be.visible');

        cy.get('#user')
            .should('be.visible')
            .and('have.attr', 'type', 'text');

        cy.get('#password')
            .should('be.visible')
            .and('have.attr', 'type', 'password');

        cy.get('#login')
            .find('button[type="submit"]')
            .should('be.visible')
            .and('contain.text', 'Login');
    });

    it('rejects invalid credentials', () => {
        cy.get('#user').type('invalid@example.com');
        cy.get('#password').type('wrong-password');

        cy.get('#login').submit();

        cy.get('#alerts>.alert')
            .should('be.visible')
            .and('contain.text', 'Invalid username or password');

        cy.location('hash').should('eq', '');
    });

    it('logs in with valid credentials', () => {
        cy.env(['OWNER_EMAIL', 'OWNER_PASSWORD'])
            .then(({ OWNER_EMAIL, OWNER_PASSWORD }) => {
                cy.get('#user').type(OWNER_EMAIL);
                cy.get('#password').type(OWNER_PASSWORD);
            });

        cy.get('#login').submit();

        cy.location('hash')
            .should('eq', '#!school');

        cy.get('#login')
            .should('not.exist');
    });
});
