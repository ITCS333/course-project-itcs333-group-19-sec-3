/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from 'students.json'.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const tbody= document.querySelector("#students-table-body");
// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm= document.getElementById("add-student-form");
// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const passwordForm= document.getElementById("password-form");
// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput= document.getElementById("search-input");
// TODO: Select all table header (th) elements in thead.
const tableHeaders= document.querySelectorAll("#student-table thead th");

// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  // ... your implementation here ...
  const tr= document.createElement("tr");
  const stuName= document.createElement("td");
    stuName.textContent = student.name;
    tr.appendChild(stuName);

   const stuId = document.createElement("td");
    stuId.textContent = student.id;
    tr.appendChild(stuId);
  
    const stuEmail = document.createElement("td");
    stuEmail.textContent = student.email;
    tr.appendChild(stuEmail);

    const tdButtons = document.createElement("td");
    const editBtn = document.createElement("button");
    editBtn.textContent = "Edit";
    editBtn.classList.add("edit-btn");
    editBtn.setAttribute("data-id", student.id);
    tdButtons.appendChild(editBtn);

    const deleteBtn = document.createElement("button");
    deleteBtn.textContent = "Delete";
    deleteBtn.classList.add("delete-btn");
    deleteBtn.setAttribute("data-id", student.id);
    tdButtons.appendChild(deleteBtn);
  
   tr.appendChild(tdButtons);
    return tr;

}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  // ... your implementation here ...
    studentTableBody.innerHTML = "";
  studentArray.forEach(student => {
        const tr = createStudentRow(student);
        studentTableBody.appendChild(tr);
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
  // ... your implementation here ...
 event.preventDefault();
 const currentPassword = document.getElementById("current-password").value;
 const newPassword = document.getElementById("new-password").value;
 const confirmPassword = document.getElementById("confirm-password").value;
   if (newPassword !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }
  if (newPassword.length < 8) {
        alert("Password must be at least 8 characters.");
        return;
    }
  alert("Password updated successfully!");
  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value = "";
  document.getElementById("confirm-password").value = "";
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
  // ... your implementation here ...
  event.preventDefault();
  const name = document.getElementById("student-name").value;
  const id = document.getElementById("student-id").value;
  const email = document.getElementById("student-email").value;
  const defaultPassword = document.getElementById("default-password").value;
   if (!name || !id || !email) {
        alert("Please fill out all required fields.");
        return;
    }
  const duplicate = students.some(student => student.id === id);
    if (duplicate) {
        alert("A student with this ID already exists.");
        return;
    }
  const newStudent = { name, id, email };
  students.push(newStudent);
  renderTable(students);
  
  document.getElementById("student-name").value = "";
  document.getElementById("student-id").value = "";
  document.getElementById("student-email").value = "";
  document.getElementById("default-password").value = "password123"; 

}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  const target = event.target;
   if (target.classList.contains("delete-btn")) {
        const studentId = target.dataset.id;
        students = students.filter(student => student.id !== studentId);
        renderTable(students);
   }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  // ... your implementation here ...
  const searchTerm = searchInput.value.toLowerCase();
  if (!searchTerm) {
        renderTable(students);
        return;
    }
  const filteredStudents = students.filter(student => 
        student.name.toLowerCase().includes(searchTerm)
    );
  renderTable(filteredStudents);
}


/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  // ... your implementation here ...
  const th = event.currentTarget;        
  const colIndex = th.cellIndex;
  let sortKey = "";
  if (colIndex === 0) 
    sortKey = "id";
  if (colIndex === 1) 
    sortKey = "name";
  if (colIndex === 2) 
    sortKey = "email";

  let currentDir = th.dataset.sortDir || "asc";
  let newDir = currentDir === "asc" ? "desc" : "asc";
  th.dataset.sortDir = newDir;

  students.sort((a, b) => {
    let valA = a[sortKey];
    let valB = b[sortKey];

    if (sortKey === "id") {
      return newDir === "asc" ? (valA - valB) : (valB - valA);
    }
    return newDir === "asc"
      ? valA.localeCompare(valB)
      : valB.localeCompare(valA);
  });
  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  // ... your implementation here ...
  try {
    const response = await fetch("students.json");
    if (!response.ok) {
      console.error("Failed to load students.json");
      return;
    }
    const data = await response.json();
    students = data;
    renderTable(students);
    
    changePasswordForm.addEventListener("submit", handleChangePassword);
    addStudentForm.addEventListener("submit", handleAddStudent);
    studentTableBody.addEventListener("click", handleTableClick);
    searchInput.addEventListener("input", handleSearch);
    tableHeaders.forEach(th => {
      th.addEventListener("click", handleSort);
    });

  } catch (error) {
    console.error("Error loading students:", error);
  }

}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
