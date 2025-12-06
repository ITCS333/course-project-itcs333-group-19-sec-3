/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
const assignmentForm= document.querySelector("#assignment-form");

// TODO: Select the assignments table body ('#assignments-tbody').
const assignmentTbody=document.querySelector("#assignments-tbody");

//Define API URL
const API_URL = 'index.php?resource=assignments';

// --- Functions ---

/**
 * TODO: Implement the createAssignmentRow function.
 * It takes one assignment object {id, title, dueDate}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `dueDate`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createAssignmentRow(assignment) {
  // ... your implementation here ...
  const {id,title,dueDate} = assignment;

  const tr=document.createElement("tr");

  const titleTd=document.createElement("td");
  titleTd.textContent=title;

  const dueDateTd=document.createElement("td");
  dueDateTd.textContent= dueDate;

  const actionsTd=document.createElement("td");

  const editButton=document.createElement("button");
  editButton.textContent="Edit";
  editButton.classList.add("edit-btn");
  editButton.dataset.id=id;

  const deleteBtn=document.createElement("button");
  deleteBtn.textContent="Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id=id;

  actionsTd.appendChild(editButton);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(dueDateTd);
  tr.appendChild(actionsTd);

  return tr;

}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `assignmentsTableBody`.
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call `createAssignmentRow()`, and
 * append the resulting <tr> to `assignmentsTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  assignmentTbody.innerHTML="";
  assignments.forEach(assignment=>{
    const row=createAssignmentRow(assignment);
    assignmentTbody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddAssignment function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, due date, and files inputs.
 * 3. Create a new assignment object with a unique ID (e.g., `id: \`asg_${Date.now()}\``).
 * 4. Add this new assignment object to the global `assignments` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
async function handleAddAssignment(event) {
  // ... your implementation here ...
  event.preventDefault();

  const title=document.querySelector("#assignment-title").value.trim();
  const description=document.querySelector("#assignment-description").value.trim();
  const dueDate=document.querySelector("#assignment-due-date").value;
  const filesInput = document.querySelector("#assignment-files").value.trim();

  const files = filesInput.split('\n').map(f => f.trim()).filter(f => f.length > 0);

  const newAssignment={
    id: `asg_${Date.now()}`,
    title, 
    description, 
    due_date: dueDate, 
    files
  };

  assignments.push(newAssignment);
  renderTable();
  event.target.reset();

  try {
        // 1. Send data to the PHP API using POST
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newAssignment)
        });

        const result = await response.json();
        
        if (!response.ok) {
            console.rror("Failed to add assignment:", result.error);
            alert(`Error adding assignment: ${result.error || 'Unknown error'}`);
            return;
        }

        // 2. Add the created assignment object (which includes the new DB ID) to the global array
        assignments.push(result);
        
        // 3. Refresh the table and reset the form
        renderTable();
        event.target.reset();

    } catch (error) {
        console.error("Network or submission error:", error);
        alert("A network error occurred while submitting the assignment.");
    }
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `assignmentsTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `assignments` array by filtering out the assignment
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
async function handleTableClick(event) {
  // ... your implementation here ...
  if(event.target.classList.contains("delete-btn")){
    const id=event.target.dataset.id;
  
    if (!confirm(`Are you sure you want to delete assignment ID: ${id}?`)) {
            return;
        }

    try {
            // Send DELETE request (using POST with a method override or actual DELETE)
            // Using DELETE method is the standard REST approach
            const response = await fetch(`${API_URL}&id=${id}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (!response.ok) {
                console.error("Failed to delete assignment:", result.error);
                alert(`Error deleting assignment: ${result.error || 'Unknown error'}`);
                return;
            }

    assignments=assignments.filter(asg=> asg.id!=id);

    renderTable();
  }
  catch (error) {
            console.error("Network error during deletion:", error);
            alert("A network error occurred while trying to delete the assignment.");
        }
    }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response and store the result in the global `assignments` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `assignmentForm` (calls `handleAddAssignment`).
 * 5. Add the 'click' event listener to `assignmentsTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try{
    const response=await fetch(API_URL);
    if(!response.ok){
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data=await response.json();
    assignments=data; //updating global assignment store

    renderTable();

    assignmentForm.addEventListener("submit", handleAddAssignment);
    assignmentTbody.addEventListener("click", handleTableClick);

  }
  catch(error){
    console.error("Error loading assignments:", error);
  }

}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
