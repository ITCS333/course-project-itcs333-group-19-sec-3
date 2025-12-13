const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/resources/list.js'), 'utf8');

let sandbox;
let mockListSection;

describe('Course Resources List Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <section id="resource-list-section"></section>
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

    // 3. Run the script inside the sandbox (comment out the last line that calls loadResources)
    const codeWithoutInit = code.replace('loadResources();', '');
    vm.runInContext(codeWithoutInit, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockListSection = document.getElementById('resource-list-section');
  });

  describe('TASK2201 createResourceArticle Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.createResourceArticle).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.createResourceArticle.length).toBe(1);
    });
  });

  describe('TASK2202 loadResources Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.loadResources).toBe('function');
    });

    test('should be an async function', () => {
      expect(sandbox.loadResources.constructor.name).toBe('AsyncFunction');
    });
  });
});
