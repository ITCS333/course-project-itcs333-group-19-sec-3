const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/admin/manage_users.js'), 'utf8');

let sandbox;
let mockTableBody, mockAddForm, mockPasswordForm, mockSearchInput;

describe('Manage Users Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <table id="student-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Student ID</th>
            <th>Email</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <form id="add-student-form">
        <input type="text" id="student-name" />
        <input type="text" id="student-id" />
        <input type="email" id="student-email" />
        <input type="text" id="default-password" />
      </form>
      <form id="password-form">
        <input type="password" id="current-password" />
        <input type="password" id="new-password" />
        <input type="password" id="confirm-password" />
      </form>
      <input type="text" id="search-input" />
    `;

    // 2. Prepare the sandbox with references to the Jest DOM environment
    sandbox = {
      document: document,
      window: window,
      console: console,
      Event: Event,
      fetch: jest.fn() // Mock fetch for async function
    };

    vm.createContext(sandbox);

    // 3. Run the script inside the sandbox (comment out the last line that calls loadStudentsAndInitialize)
    const codeWithoutInit = code.replace('loadStudentsAndInitialize();', '');
    vm.runInContext(codeWithoutInit, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockTableBody = document.querySelector('tbody');
    mockAddForm = document.getElementById('add-student-form');
    mockPasswordForm = document.getElementById('password-form');
    mockSearchInput = document.getElementById('search-input');
  });

  describe('TASK1501 createStudentRow Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.createStudentRow).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.createStudentRow.length).toBe(1);
    });
  });

  describe('TASK1502 renderTable Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.renderTable).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.renderTable.length).toBe(1);
    });
  });

  describe('TASK1503 handleChangePassword Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleChangePassword).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleChangePassword.length).toBe(1);
    });

  });

  describe('TASK1504 handleAddStudent Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleAddStudent).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleAddStudent.length).toBe(1);
    });

  });

  describe('TASK1505 handleTableClick Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleTableClick).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleTableClick.length).toBe(1);
    });
  });

  describe('TASK1506 handleSearch Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleSearch).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleSearch.length).toBe(1);
    });
  });

  describe('TASK1507 handleSort Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleSort).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleSort.length).toBe(1);
    });
  });

  describe('TASK1508 loadStudentsAndInitialize Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.loadStudentsAndInitialize).toBe('function');
    });

    test('should be an async function', () => {
      expect(sandbox.loadStudentsAndInitialize.constructor.name).toBe('AsyncFunction');
    });
  });
});
