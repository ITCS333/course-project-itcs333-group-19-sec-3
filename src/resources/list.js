/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
const listSection = document.querySelector("#resource-list-section");
// --- Functions ---

/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
  const { id, title, description } = resource;

  const article = document.createElement("article");
  article.classList.add("resource-card");

  article.innerHTML = `
    <h3>${title}</h3>
    <p>${description}</p>
    <a href="details.html?id=${id}">View Resource & Discussion</a>
  `;

  return article;
}

/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadResources() {
  try {
    // PHASE 3 UPDATE:
    // Pull resources from the live backend API (MySQL)
    const response = await fetch("api/index.php");

    const data = await response.json();

    const resources = data.data;

    listSection.innerHTML = "";

    resources.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error("Error loading resources:", error);

    listSection.innerHTML = "<p>Could not load resources. Please try again later.</p>";
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
