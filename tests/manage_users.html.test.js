const { TextEncoder, TextDecoder } = require('util');
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const htmlPath = path.join(__dirname, '../src/admin/manage_users.html');

if (!fs.existsSync(htmlPath)) {
  describe('HTML File Check', () => {
    test(`The file 'portal.html' should exist`, () => {
      // This test will fail and provide a clear message.
      expect(fs.existsSync(htmlPath)).toBe(true);
    });
  });
} else {
  // Read the HTML file content
  const htmlContent = fs.readFileSync(htmlPath, 'utf-8');
  const dom = new JSDOM(htmlContent);
  const { document } = dom.window;

  describe('TASK1401 Meta Tags', () => {
    test('should have a meta tag with charset attribute', () => {
      const charsetMeta = document.querySelector('meta[charset]');
      expect(charsetMeta).toBeTruthy();
      expect(charsetMeta.hasAttribute('charset')).toBe(true);
    });
  
    test('should have a viewport meta tag', () => {
      const viewportMeta = document.querySelector('meta[name="viewport"]');
      expect(viewportMeta).toBeTruthy();
      expect(viewportMeta.hasAttribute('content')).toBe(true);
    });
  });
  
  describe('TASK1402 Page Title', () => {
    test('should have a title element', () => {
      const title = document.querySelector('title');
      expect(title).toBeTruthy();
      expect(title.textContent.length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1403 CSS Link', () => {
    test('should have at least one stylesheet link', () => {
      const cssLink = document.querySelector('link[rel="stylesheet"]');
      expect(cssLink).toBeTruthy();
      expect(cssLink.hasAttribute('href')).toBe(true);
    });
  });
  
  describe('TASK1404 Header Structure', () => {
    test('should have a header element', () => {
      const header = document.querySelector('header');
      expect(header).toBeTruthy();
    });

    test('should have an h heading', () => {
      const h1 = document.querySelector('h1, h2, h3, h4, h5, h6');
      expect(h1).toBeTruthy();
      expect(h1.textContent.trim().length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1405 Main Element', () => {
    test('should have a main element', () => {
      const main = document.querySelector('main');
      expect(main).toBeTruthy();
    });

    test('should have section elements', () => {
      const sections = document.querySelectorAll('section');
      expect(sections.length).toBeGreaterThanOrEqual(2);
    });
  });
  
  describe('TASK1406 Password Form', () => {
    test('should have a form element', () => {
      const form = document.querySelector('form');
      expect(form).toBeTruthy();
    });
  });
  
  describe('TASK1407 Password Fields', () => {
    test('should have at least 3 password inputs', () => {
      const passwordInputs = document.querySelectorAll('input[type="password"]');
      expect(passwordInputs.length).toBeGreaterThanOrEqual(3);
    });
  
    test('should have password inputs with id attributes', () => {
      const passwordInputs = document.querySelectorAll('input[type="password"][id]');
      expect(passwordInputs.length).toBeGreaterThanOrEqual(3);
    });
  
    test('should have password inputs with required attribute', () => {
      const passwordInputs = document.querySelectorAll('input[type="password"][required]');
      expect(passwordInputs.length).toBeGreaterThanOrEqual(3);
    });

    test('should have at least one password input with minlength attribute', () => {
      const passwordInput = document.querySelector('input[type="password"][minlength]');
      expect(passwordInput).toBeTruthy();
    });
  });

  describe('TASK1408 Student Form Fields', () => {
    test('should have at least 2 forms', () => {
      const forms = document.querySelectorAll('form');
      expect(forms.length).toBeGreaterThanOrEqual(2);
    });

    test('should have text type inputs', () => {
      const textInputs = document.querySelectorAll('input[type="text"]');
      expect(textInputs.length).toBeGreaterThanOrEqual(2);
    });

    test('should have an email type input', () => {
      const emailInput = document.querySelector('input[type="email"]');
      expect(emailInput).toBeTruthy();
    });
  
    test('should have email input with id attribute', () => {
      const emailInput = document.querySelector('input[type="email"]');
      expect(emailInput.hasAttribute('id')).toBe(true);
    });
  
    test('should have email input with required attribute', () => {
      const emailInput = document.querySelector('input[type="email"]');
      expect(emailInput.hasAttribute('required')).toBe(true);
    });
  });
}
