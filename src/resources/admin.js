/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="resources-tbody"` to the <tbody> element
     inside your `resources-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the JSON file.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.querySelector("#resource-form");
// TODO: Select the resources table body ('#resources-tbody').
const resourcesTableBody = document.querySelector("#resources-tbody"); 
// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createResourceRow(resource) {
  const { id, title, description } = resource;

  const tr = document.createElement("tr");

  tr.innerHTML = `
        <td>${title}</td>
        <td>${description}</td>
        <td>
            <button class="edit-btn" data-id="${id}">Edit</button>
            <button class="delete-btn" data-id="${id}">Delete</button>
        </td>
    `;

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `resourcesTableBody`.
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()`, and
 * append the resulting <tr> to `resourcesTableBody`.
 */
function renderTable() {
   resourcesTableBody.innerHTML = ""; 

  resources.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, and link inputs.
 * 3. Create a new resource object with a unique ID (e.g., `id: \`res_${Date.now()}\``).
 * 4. Add this new resource object to the global `resources` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
async function handleAddResource(event) {
  event.preventDefault();

  const title = document.querySelector("#resource-title").value.trim();
  const description = document.querySelector("#resource-description").value.trim();
  const link = document.querySelector("#resource-link").value.trim();

  if (!title || !link) {
    alert("Title and link are required.");
    return;
  }

  try {
    // PHASE 3 UPDATE:
    // Send data to the database via API
    const response = await fetch("api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title,
        description,
        link
      })
    });

    const result = await response.json();

    if (result.success) {
      alert("Resource added successfully!");
      await loadAndInitialize(); // refresh table from database
      resourceForm.reset();
    } else {
      alert("Failed to add resource: " + result.message);
    }

  } catch (error) {
    console.error("Error adding resource:", error);
  }
}


/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `resourcesTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `resources` array by filtering out the resource
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
async function handleTableClick(event) {
  if (!event.target.classList.contains("delete-btn")) return;

  const id = event.target.dataset.id;
  if (!confirm("Are you sure you want to delete this resource?")) return;

  try {
    // PHASE 3 UPDATE:
    // Send DELETE request to the API
    const response = await fetch(`api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      alert("Resource deleted.");
      await loadAndInitialize(); // refresh
    } else {
      alert("Failed to delete resource: " + result.message);
    }

  } catch (error) {
    console.error("Error deleting resource:", error);
  }
}


/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response and store the result in the global `resources` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `resourceForm` (calls `handleAddResource`).
 * 5. Add the 'click' event listener to `resourcesTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  try {
    // PHASE 3 UPDATE:
    // Resources now come from MySQL through index.php
    const response = await fetch("api/index.php");
    const data = await response.json();

    resources = data.data;
    renderTable();

  } catch (error) {
    console.error("Error loading resources:", error);
  }

  // Event Listeners
  resourceForm.addEventListener("submit", handleAddResource);
  resourcesTableBody.addEventListener("click", handleTableClick);
}


// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
