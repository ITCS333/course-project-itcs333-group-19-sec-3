const { TextEncoder, TextDecoder } = require('util');
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const htmlPath = path.join(__dirname, '../src/auth/login.html');

if (!fs.existsSync(htmlPath)) {
  describe('HTML File Check', () => {
    test(`The file '${studentHtmlFileName}' should exist`, () => {
      // This test will fail and provide a clear message.
      expect(fs.existsSync(htmlPath)).toBe(true);
    });
  });
} else {
  // Read the HTML file content
  const htmlContent = fs.readFileSync(htmlPath, 'utf-8');
  const dom = new JSDOM(htmlContent);
  const { document } = dom.window;

  describe('TASK1101 Meta Tags', () => {
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
  
  describe('TASK1102 Page Title', () => {
    test('should have a title element', () => {
      const title = document.querySelector('title');
      expect(title).toBeTruthy();
      expect(title.textContent.length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1103 CSS Link', () => {
    test('should have at least one stylesheet link', () => {
      const cssLink = document.querySelector('link[rel="stylesheet"]');
      expect(cssLink).toBeTruthy();
      expect(cssLink.hasAttribute('href')).toBe(true);
    });
  });
  
  describe('TASK1104 Page Structure', () => {
    test('should have a main element', () => {
      const main = document.querySelector('main');
      expect(main).toBeTruthy();
    });
  
    test('should have a section element', () => {
      const section = document.querySelector('section');
      expect(section).toBeTruthy();
    });
  
    test('should have a heading element (h1, h2, h3, h4, h5, or h6)', () => {
      const heading = document.querySelector('h1, h2, h3, h4, h5, h6');
      expect(heading).toBeTruthy();
      expect(heading.textContent.trim().length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1105 Form Element', () => {
    test('should have a form element', () => {
      const form = document.querySelector('form');
      expect(form).toBeTruthy();
    });
  
    test('should have form with action attribute', () => {
      const form = document.querySelector('form');
      expect(form.hasAttribute('action')).toBe(true);
    });
  
    test('should have a fieldset inside form', () => {
      const fieldset = document.querySelector('form fieldset');
      expect(fieldset).toBeTruthy();
    });
  
    test('should have a legend inside fieldset', () => {
      const legend = document.querySelector('fieldset legend');
      expect(legend).toBeTruthy();
      expect(legend.textContent.trim().length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1106 Email Field', () => {
    test('should have a label with "for" attribute', () => {
      const labels = document.querySelectorAll('label[for]');
      expect(labels.length).toBeGreaterThanOrEqual(1);
    });
  
    test('should have an email input with type="email"', () => {
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
  
  describe('TASK1107 Password Field', () => {
    test('should have another label with "for" attribute (for password)', () => {
      const labels = document.querySelectorAll('label[for]');
      expect(labels.length).toBeGreaterThanOrEqual(2);
    });
  
    test('should have a password input with type="password"', () => {
      const passwordInput = document.querySelector('input[type="password"]');
      expect(passwordInput).toBeTruthy();
    });
  
    test('should have password input with id attribute', () => {
      const passwordInput = document.querySelector('input[type="password"]');
      expect(passwordInput.hasAttribute('id')).toBe(true);
    });
  
    test('should have password input with required attribute', () => {
      const passwordInput = document.querySelector('input[type="password"]');
      expect(passwordInput.hasAttribute('required')).toBe(true);
    });
  
    test('should have password input with minlength attribute', () => {
      const passwordInput = document.querySelector('input[type="password"]');
      expect(passwordInput.hasAttribute('minlength')).toBe(true);
    });
  });
  
  describe('TASK1108 Submit Button', () => {
    test('should have a button element', () => {
      const button = document.querySelector('button');
      expect(button).toBeTruthy();
    });
  
    test('should have button with type attribute', () => {
      const button = document.querySelector('button');
      expect(button.hasAttribute('type')).toBe(true);
    });
  
    test('should have button with id attribute', () => {
      const button = document.querySelector('button');
      expect(button.hasAttribute('id')).toBe(true);
    });
  
    test('button should have text content', () => {
      const button = document.querySelector('button');
      expect(button.textContent.trim().length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK1109 Message Container (for JS)', () => {
    test('should have a div with id attribute (for message container)', () => {
      const divs = document.querySelectorAll('div[id]');
      expect(divs.length).toBeGreaterThanOrEqual(1);
    });
  });
  
  describe('TASK1110 Script Tag', () => {
    test('should have a script tag', () => {
      const script = document.querySelector('script');
      expect(script).toBeTruthy();
    });
  
    test('should have script tag with defer attribute', () => {
      const script = document.querySelector('script[defer]');
      expect(script).toBeTruthy();
    });
  
    test('should have script tag with src attribute', () => {
      const script = document.querySelector('script[src]');
      expect(script).toBeTruthy();
    });
  });
}
