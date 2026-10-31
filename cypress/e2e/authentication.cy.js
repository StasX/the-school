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
            .and('contain.text', 'invalid Username or Password');

        cy.location('hash').should('eq', '');
    });

    [
        { label: 'empty fields', email: '', password: '' },
        { label: 'missing username', email: '', password: 'password123' },
        { label: 'missing password', email: 'owner@example.com', password: '' },
        { label: 'malformed email', email: 'not-an-email', password: 'password123' },
        { label: 'long email', email: `${'a'.repeat(60)}@example.com`, password: 'password123' },
        { label: 'short password', email: 'owner@example.com', password: 'short' },
        { label: 'long password', email: 'owner@example.com', password: 'a'.repeat(33) },
    ].forEach(({ label, email, password }) => {
        it(`shows the generic login error for ${label}`, () => {
            if (email) cy.get('#user').type(email);
            if (password) cy.get('#password').type(password);

            cy.get('#login').submit();

            cy.get('#alerts>.alert')
                .should('be.visible')
                .and('have.text', 'invalid Username or Password');
            cy.get('#alerts>.alert').should('have.length', 1);
            cy.location('hash').should('eq', '');
        });
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
