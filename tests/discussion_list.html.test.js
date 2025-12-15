const { TextEncoder, TextDecoder } = require('util');
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const htmlPath = path.join(__dirname, '../src/discussion/baord.html');

if (!fs.existsSync(htmlPath)) {
  describe('HTML File Check', () => {
    test(`The file 'baord.html' should exist`, () => {
      // This test will fail and provide a clear message.
      expect(fs.existsSync(htmlPath)).toBe(true);
    });
  });
} else {
  // Read the HTML file content
  const htmlContent = fs.readFileSync(htmlPath, 'utf-8');
  const dom = new JSDOM(htmlContent);
  const { document } = dom.window;

  describe('TASK5101 Meta Tags', () => {
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
  
  describe('TASK5102 Page Title', () => {
    test('should have a title element', () => {
      const title = document.querySelector('title');
      expect(title).toBeTruthy();
      expect(title.textContent.length).toBeGreaterThan(0);
    });
  });
  
  describe('TASK5103 CSS Link', () => {
    test('should have at least one stylesheet link', () => {
      const cssLink = document.querySelector('link[rel="stylesheet"]');
      expect(cssLink).toBeTruthy();
      expect(cssLink.hasAttribute('href')).toBe(true);
    });
  });
  
  describe('TASK5104 Header Structure', () => {
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
  
  describe('TASK5105 Main Element', () => {
    test('should have a main element', () => {
      const main = document.querySelector('main');
      expect(main).toBeTruthy();
    });

    test('should have a section element', () => {
      const section = document.querySelector('section');
      expect(section).toBeTruthy();
    });
  });
}
