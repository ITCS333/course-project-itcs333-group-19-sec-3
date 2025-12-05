/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="assignment-list-section"` to the
     <section> element that will contain the assignment articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list ('#assignment-list-section').
const listSection = document.querySelector('#assignment-list-section');


// Define the API base URL
const API_URL = 'index.php?resource=assignments';

// --- Functions ---

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  const article = document.createElement('article');
  article.className = 'assignment-card';

  article.innerHTML = `
        <h3>${assignment.title}</h3>
        <p>
            <strong>Due Date:</strong> 
            <time datetime="${assignment.dueDate}">${assignment.dueDate}</time>
        </p>
        <p class="description-preview">${assignment.description.substring(0, 100)}...</p>
        <a href="details.html?id=${assignment.id}" class="details-link">View Details</a>
    `;

    return article;

}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
  try {
        // 1. Use fetch() to get data from 'assignments.json'.
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // 2. Parse the JSON response into an array.
        const assignments = await response.json();

        if (!listSection) {
            console.error("Error: Could not find the element with id 'assignment-list-section'.");
            return;
        }

        // 3. Clear any existing content from listSection.
        listSection.innerHTML = '';

        // 4. Loop through the assignments array and append articles.
        assignments.forEach(assignment => {
            const articleElement = createAssignmentArticle(assignment);
            listSection.appendChild(articleElement);
        });

    } catch (error) {
        console.error('Failed to load assignments:', error);
        if (listSection) {
            listSection.innerHTML = '<p class="error-message">Error loading assignments. Please check the console for details.</p>';
        }
    }

  
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();
