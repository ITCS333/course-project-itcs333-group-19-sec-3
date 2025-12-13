const fs = require('fs');
const vm = require('vm');
const path = require('path');

// Read the file content once
const code = fs.readFileSync(path.resolve(__dirname, '../src/discussion/board.js'), 'utf8');

let sandbox;
let mockTopicListContainer;
let mockNewTopicForm;

describe('Discussion Board Script Tests', () => {

  beforeEach(() => {
    // 1. Set up the DOM *before* running the script
    document.body.innerHTML = `
      <form id="new-topic-form">
        <input type="text" id="topic-subject" value="" />
        <textarea id="topic-message"></textarea>
        <button type="submit">Create Topic</button>
      </form>
      <div id="topic-list-container"></div>
    `;

    // 2. Prepare the sandbox with references to the Jest DOM environment
    sandbox = {
      document: document,
      window: window,
      console: console,
      Event: Event,
      Date: Date,
      fetch: jest.fn() // Mock fetch for async function
    };

    vm.createContext(sandbox);

    // 3. Run the script inside the sandbox (comment out the last line that calls loadAndInitialize)
    const codeWithoutInit = code.replace('loadAndInitialize();', '');
    vm.runInContext(codeWithoutInit, sandbox);

    // 4. Update our test helper variables to point to the new DOM elements
    mockTopicListContainer = document.getElementById('topic-list-container');
    mockNewTopicForm = document.getElementById('new-topic-form');
  });

  describe('TASK5201 createTopicArticle Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.createTopicArticle).toBe('function');
    });

    test('should accept one parameter', () => {
      expect(sandbox.createTopicArticle.length).toBe(1);
    });

    test('should return an article element', () => {
      const mockTopic = {
        id: 'topic_1',
        subject: 'Test Topic',
        author: 'John Doe',
        date: '2024-01-15'
      };
      const article = sandbox.createTopicArticle(mockTopic);
      expect(article).toBeTruthy();
      expect(article.tagName).toBe('ARTICLE');
    });
  });

  describe('TASK5202 renderTopics Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.renderTopics).toBe('function');
    });
  });

  describe('TASK5203 handleCreateTopic Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleCreateTopic).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleCreateTopic.length).toBe(1);
    });

    test('should prevent default form submission', () => {
      const mockEvent = {
        preventDefault: jest.fn(),
        target: mockNewTopicForm
      };
      
      document.getElementById('topic-subject').value = 'Test Subject';
      document.getElementById('topic-message').value = 'Test Message';
      
      sandbox.handleCreateTopic(mockEvent);
      expect(mockEvent.preventDefault).toHaveBeenCalled();
    });

  });

  describe('TASK5204 handleTopicListClick Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.handleTopicListClick).toBe('function');
    });

    test('should accept one parameter (event)', () => {
      expect(sandbox.handleTopicListClick.length).toBe(1);
    });

  });

  describe('TASK5205 loadAndInitialize Function', () => {
    test('should exist and be a function', () => {
      expect(typeof sandbox.loadAndInitialize).toBe('function');
    });

    test('should be an async function', () => {
      expect(sandbox.loadAndInitialize.constructor.name).toBe('AsyncFunction');
    });
  });
});
