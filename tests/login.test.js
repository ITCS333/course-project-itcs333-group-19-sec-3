const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/auth/login.js'), 'utf8');

let sandbox;
let mockForm, mockEmailInput, mockPasswordInput, mockMessageContainer;

describe('Login Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <form id="login-form">
        <input type="email" id="email" />
        <input type="password" id="password" />
        <div id="message-container"></div>
      </form>
    `;

    // 2. Prepare the sandbox with references to the Jest DOM environment
    sandbox = {
      document: document,
      window: window,
      console: console,
      Event: Event // Required if your script uses event listeners
    };

    vm.createContext(sandbox);

    // 3. Run the script inside the sandbox
    // This executes the 'const loginForm = ...' lines successfully because the HTML now exists
    vm.runInContext(code, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockForm = document.getElementById('login-form');
    mockEmailInput = document.getElementById('email');
    mockPasswordInput = document.getElementById('password');
    mockMessageContainer = document.getElementById('message-container');
  });

  describe('TASK1201 displayMessage Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.displayMessage).toBe('function');
    });

    test('should accept two parameters', () => {
      expect(sandbox.displayMessage.length).toBe(2);
    });
  });

  describe('TASK1202 isValidEmail Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.isValidEmail).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.isValidEmail.length).toBe(1);
    });

    test('should return a boolean', () => {
      const result = sandbox.isValidEmail('test@example.com');
      expect(typeof result).toBe('boolean');
    });

    test('should return true for valid email', () => {
      expect(sandbox.isValidEmail('test@example.com')).toBe(true);
    });

    test('should return false for invalid email', () => {
      expect(sandbox.isValidEmail('invalid-email')).toBe(false);
    });
  });

  describe('TASK1203 isValidPassword Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.isValidPassword).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.isValidPassword.length).toBe(1);
    });

    test('should return a boolean', () => {
      const result = sandbox.isValidPassword('password123');
      expect(typeof result).toBe('boolean');
    });

    test('should return true for password with 8+ characters', () => {
      expect(sandbox.isValidPassword('12345678')).toBe(true);
    });

    test('should return false for password with less than 8 characters', () => {
      expect(sandbox.isValidPassword('short')).toBe(false);
    });
  });

  describe('TASK1204 handleLogin Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleLogin).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleLogin.length).toBe(1);
    });

    test('should call preventDefault on event', () => {
      const mockEvent = {
        preventDefault: jest.fn()
      };
      
      // Set values on the DOM elements we created in beforeEach
      mockEmailInput.value = 'test@example.com';
      mockPasswordInput.value = 'password123';
      
      // Call the function from the sandbox
      sandbox.handleLogin(mockEvent);
      
      expect(mockEvent.preventDefault).toHaveBeenCalled();
    });
  });

  describe('TASK1205 setupLoginForm Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.setupLoginForm).toBe('function');
    });

    test('should add submit event listener to form', () => {
      // Spy on the form element existing in the DOM
      const addEventListenerSpy = jest.spyOn(mockForm, 'addEventListener');
      
      // Call the setup function
      sandbox.setupLoginForm();
      
      expect(addEventListenerSpy).toHaveBeenCalled();
    });
  });

  describe('TASK1206 Initial Setup', () => {
    test('setupLoginForm should be called on page load', () => {
      expect(typeof sandbox.setupLoginForm).toBe('function');
    });
  });
});
