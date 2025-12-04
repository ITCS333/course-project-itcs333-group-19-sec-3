let currentWeekId = null;
let currentComments = [];

const weekTitle = document.getElementById("week-title");
const weekStartDate = document.getElementById("week-start-date");
const weekDescription = document.getElementById("week-description");
const weekLinksList = document.getElementById("week-links-list");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newCommentText = document.getElementById("new-comment-text");

function getWeekIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderWeekDetails(week) {
  weekTitle.textContent = week.title;
  weekStartDate.textContent = "Starts on: " + week.startDate;
  weekDescription.textContent = week.description;

  weekLinksList.innerHTML = "";
  week.links.forEach(link => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = link;
    a.textContent = link;
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  const article = document.createElement("article");
  const p = document.createElement("p");
  p.textContent = comment.text;
  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + comment.author;
  article.appendChild(p);
  article.appendChild(footer);
  return article;
}

function renderComments() {
  commentList.innerHTML = "";
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

function handleAddComment(event) {
  event.preventDefault();
  const text = newCommentText.value.trim();
  if (!text) return;
  const newComment = { author: "Student", text };
  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

async function initializePage() {
  currentWeekId = getWeekIdFromURL();
  if (!currentWeekId) {
    weekTitle.textContent = "Week not found.";
    return;
  }

  try {
    const [weeksRes, commentsRes] = await Promise.all([
      fetch("weeks.json"),
      fetch("week-comments.json")
    ]);
    const weeks = await weeksRes.json();
    const commentsData = await commentsRes.json();

    const week = weeks.find(w => w.id === currentWeekId);
    currentComments = commentsData[currentWeekId] || [];

    if (week) {
      renderWeekDetails(week);
      renderComments();
      commentForm.addEventListener("submit", handleAddComment);
    } else {
      weekTitle.textContent = "Week not found.";
    }
  } catch (error) {
    weekTitle.textContent = "Error loading week data.";
    console.error(error);
  }
}

initializePage();
