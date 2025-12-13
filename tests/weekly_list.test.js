const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/weekly/list.js'), 'utf8');

let sandbox;
let mockListSection;

describe('Weekly Course Breakdown List Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <section id="week-list-section"></section>
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

    // 3. Run the script inside the sandbox (comment out the last line that calls loadWeeks)
    const codeWithoutInit = code.replace('loadWeeks();', '');
    vm.runInContext(codeWithoutInit, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockListSection = document.getElementById('week-list-section');
  });

  describe('TASK3201 createWeekArticle Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.createWeekArticle).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.createWeekArticle.length).toBe(1);
    });
  });

  describe('TASK3202 loadWeeks Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.loadWeeks).toBe('function');
    });

    test('should be an async function', () => {
      expect(sandbox.loadWeeks.constructor.name).toBe('AsyncFunction');
    });
  });
});
