/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
// --- Element Selections ---
const assignmentTitle = document.getElementById('assignment-title');
const assignmentDueDate = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList = document.getElementById('assignment-files-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');


// Define the API base URL
const ASSIGNMENT_API = 'index.php?resource=assignments';
const COMMENT_API = 'index.php?resource=comments';

// --- Functions ---

/**
 * TODO: Implement the getAssignmentIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getAssignmentIdFromURL() {
  // 1. Get the query string from `window.location.search`.
    const queryString = window.location.search;
    
    // 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
    const params = new URLSearchParams(queryString);
    
    // 3. Return the id.
    return params.get('id');
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 * It takes one assignment object.
 * It should:
 * 1. Set the `textContent` of `assignmentTitle` to the assignment's title.
 * 2. Set the `textContent` of `assignmentDueDate` to "Due: " + assignment's dueDate.
 * 3. Set the `textContent` of `assignmentDescription`.
 * 4. Clear `assignmentFilesList` and then create and append
 * `<li><a href="#">...</a></li>` for each file in the assignment's 'files' array.
 */
function renderAssignmentDetails(assignment) {
  if (!assignment) return;
    
    // 1. Set the textContent of assignmentTitle
    assignmentTitle.textContent = assignment.title || 'Assignment Details';
    
    // 2. Set the textContent of assignmentDueDate
    assignmentDueDate.textContent = "Due: " + (assignment.due_date || 'N/A');
    
    // 3. Set the textContent of assignmentDescription
    assignmentDescription.textContent = assignment.description || 'No description provided.';
    
    // 4. Clear assignmentFilesList and populate with links
    assignmentFilesList.innerHTML = '';
    
    const files = assignment.files || [];
    
    if (files.length === 0) {
        assignmentFilesList.innerHTML = '<li class="text-gray-500 italic">No attached files.</li>';
    } else {
        files.forEach(fileUrl => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            
            // Assuming the fileUrl is a path/name, use it as the link text and href
            a.href = fileUrl; 
            a.textContent = fileUrl.split('/').pop() || fileUrl; // Show only the filename
            a.target = "_blank"; // Open in new tab
            a.classList.add('text-blue-600', 'hover:text-blue-800', 'underline');

            li.appendChild(a);
            assignmentFilesList.appendChild(li);
        });
    }
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 */
function createCommentArticle(comment) {
  // Outer article container
    const article = document.createElement('article');
    article.className = 'p-4 mb-4 bg-gray-50 rounded-lg shadow-sm border border-gray-200';

    // Header div for author and date
    const headerDiv = document.createElement('div');
    headerDiv.className = 'flex justify-between items-center mb-2';

    // Author name
    const authorP = document.createElement('p');
    authorP.className = 'font-semibold text-gray-900';
    authorP.textContent = comment.author || 'Anonymous';

    // Date
    const dateSpan = document.createElement('span');
    dateSpan.className = 'text-sm text-gray-500';
    // Format date nicely
    const date = comment.created_at ? new Date(comment.created_at) : new Date();
    dateSpan.textContent = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    headerDiv.appendChild(authorP);
    headerDiv.appendChild(dateSpan);

    // Comment text content
    const textP = document.createElement('p');
    textP.className = 'text-gray-700 whitespace-pre-wrap'; // Use pre-wrap to preserve formatting
    textP.textContent = comment.text || '';

    // Append everything
    article.appendChild(headerDiv);
    article.appendChild(textP);

    return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  // 1. Clear the commentList.
    commentList.innerHTML = '';

    if (currentComments.length === 0) {
        commentList.innerHTML = '<p class="text-center text-gray-500 italic">Be the first to leave a comment.</p>';
        return;
    }

    // 2. Loop through the global currentComments array.
    currentComments.forEach(comment => {
        // 3. For each comment, call createCommentArticle(), and append the resulting <article> to commentList.
        const article = createCommentArticle(comment);
        commentList.appendChild(article);
    });
}

/**
 * Helper to fetch comments for the current assignment ID.
 */
async function fetchCommentsForAssignment() {
    if (!currentAssignmentId) return;

    try {
        const response = await fetch(`${COMMENT_API}&assignment_id=${currentAssignmentId}`);
        const data = await response.json();

        if (response.ok) {
            // Update the global store and render
            currentComments = Array.isArray(data) ? data : [];
            renderComments();
        } else {
            console.error('Failed to fetch comments:', data.error);
        }
    } catch (error) {
        console.error('Network error fetching comments:', error);
    }
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
async function handleAddComment(event) {
  // 1. Prevent the form's default submission.
    event.preventDefault();
    
    // 2. Get the text from newCommentText.value.
    const commentText = newCommentText.value.trim();
    
    // 3. If the text is empty, return.
    if (!commentText) {
        alert('Comment text cannot be empty.'); // Use a simple alert for quick feedback here
        return;
    }

    // Prepare the payload
    const payload = {
        assignment_id: currentAssignmentId,
        author: 'Current User', // Hardcoded as 'Current User' as per previous context
        text: commentText
    };

    try {
        // Send POST request to the API
        const response = await fetch(COMMENT_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.id) {
            // Success: 
            
            // 6. Clear the newCommentText textarea.
            newCommentText.value = '';

            // 5. & 6. Refresh the list from the server to get the new comment (including server-side generated timestamp)
            await fetchCommentsForAssignment(); 
            
        } else {
            // Display error message from API
            alert(`Error posting comment: ${result.error || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error posting comment:', error);
        alert('A network error occurred while submitting the comment.');
    }
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentAssignmentId` by calling `getAssignmentIdFromURL()`.
 * 2. If no ID is found, display an error and stop.
 * 3. `fetch` both 'assignments.json' and 'comments.json' (you can use `Promise.all`).
 * 4. Find the correct assignment from the assignments array using the `currentAssignmentId`.
 * 5. Get the correct comments array from the comments object using the `currentAssignmentId`.
 * Store this in the global `currentComments` variable.
 * 6. If the assignment is found:
 * - Call `renderAssignmentDetails()` with the assignment object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 7. If the assignment is not found, display an error.
 */
async function initializePage() {
  // 1. Get the currentAssignmentId by calling getAssignmentIdFromURL().
    currentAssignmentId = getAssignmentIdFromURL();
    
    // 2. If no ID is found, display an error and stop.
    if (!currentAssignmentId) {
        assignmentTitle.textContent = 'Error: No Assignment ID Provided';
        assignmentDescription.textContent = 'Please navigate from the assignments list.';
        return;
    }
    
    // 3 & 4. Fetch the assignment details
    try {
        const assignmentResponse = await fetch(`${ASSIGNMENT_API}&id=${currentAssignmentId}`);
        const assignmentData = await assignmentResponse.json();

        if (assignmentResponse.ok && assignmentData.id) {
            // 6. If the assignment is found:
            
            // Call renderAssignmentDetails() with the assignment object.
            renderAssignmentDetails(assignmentData);

            // 5. Fetch comments
            await fetchCommentsForAssignment();
            
            // Add the 'submit' event listener to commentForm (calls handleAddComment).
            commentForm.addEventListener('submit', handleAddComment);

        } else if (assignmentResponse.status === 404) {
             // 7. If the assignment is not found, display an error.
            assignmentTitle.textContent = 'Assignment Not Found (404)';
            assignmentDescription.textContent = `The assignment with ID ${currentAssignmentId} could not be located.`;
        } else {
            // General fetch error
            assignmentTitle.textContent = 'Error Loading Assignment';
            assignmentDescription.textContent = assignmentData.error || 'An unknown error occurred while fetching details.';
            console.error('API Error:', assignmentData);
        }
        
    } catch (error) {
        // Network/parsing error
        assignmentTitle.textContent = 'Connection Error';
        assignmentDescription.textContent = 'Could not connect to the assignment API.';
        console.error('Fetch error:', error);
    }
}

// --- Initial Page Load ---
initializePage();
