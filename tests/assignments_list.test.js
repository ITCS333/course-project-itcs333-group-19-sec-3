const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/assignments/list.js'), 'utf8');

let sandbox;
let mockListSection;

describe('Assignemnts Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <section id="assignments-list-section"></section>
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

    const codeWithoutInit = code.replace('loadAssignments();', '');
    vm.runInContext(codeWithoutInit, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockListSection = document.getElementById('assignment-list-section');
  });

  describe('TASK4201 createAssignmentArticle Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.createAssignmentArticle).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.createAssignmentArticle.length).toBe(1);
    });
  });

  describe('TASK4202 loadAssignments Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.loadAssignments).toBe('function');
    });

    test('should be an async function', () => {
      expect(sandbox.loadAssignments.constructor.name).toBe('AsyncFunction');
    });
  });
});
