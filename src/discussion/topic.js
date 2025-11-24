/*
  topic.js
  Populates a single discussion topic page and manages replies
*/

// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Will hold replies for this topic

// --- Element Selections ---
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// --- Functions ---

// Get topic ID from URL query string
function getTopicIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

// Render the original topic post
function renderOriginalPost(topic) {
  topicSubject.textContent = topic.subject;
  opMessage.textContent = topic.message;
  opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

// Create a single reply <article>
function createReplyArticle(reply) {
  const article = document.createElement('article');

  const p = document.createElement('p');
  p.textContent = reply.text;
  article.appendChild(p);

  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
  article.appendChild(footer);

  const actions = document.createElement('div');
  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.classList.add('delete-reply-btn');
  deleteBtn.dataset.id = reply.id;
  actions.appendChild(deleteBtn);

  article.appendChild(actions);
  return article;
}

// Render all replies
function renderReplies() {
  replyListContainer.innerHTML = '';
  currentReplies.forEach(reply => {
    replyListContainer.appendChild(createReplyArticle(reply));
  });
}

// Handle adding a new reply
function handleAddReply(event) {
  event.preventDefault();
  const text = newReplyText.value.trim();
  if (!text) return;

  const newReply = {
    id: `reply_${Date.now()}`,
    author: 'Student',
    date: new Date().toISOString().split('T')[0],
    text
  };

  currentReplies.push(newReply);
  renderReplies();
  replyForm.reset();
}

// Handle deleting a reply using event delegation
function handleReplyListClick(event) {
  if (event.target.classList.contains('delete-reply-btn')) {
    const id = event.target.dataset.id;
    currentReplies = currentReplies.filter(r => r.id !== id);
    renderReplies();
  }
}

// Initialize the page
async function initializePage() {
  currentTopicId = getTopicIdFromURL();
  if (!currentTopicId) {
    topicSubject.textContent = 'Topic not found.';
    return;
  }

  try {
    const [topicsRes, repliesRes] = await Promise.all([
      fetch('topics.json'),
      fetch('replies.json')
    ]);

    if (!topicsRes.ok || !repliesRes.ok) throw new Error('Failed to load data');

    const topicsData = await topicsRes.json();
    const repliesData = await repliesRes.json();

    const topic = topicsData.find(t => t.id === currentTopicId);
    if (!topic) {
      topicSubject.textContent = 'Topic not found.';
      return;
    }

    currentReplies = repliesData[currentTopicId] || [];

    renderOriginalPost(topic);
    renderReplies();

    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);

  } catch (error) {
    console.error('Error loading topic page:', error);
    topicSubject.textContent = 'Failed to load topic.';
  }
}

// --- Initial Page Load ---
initializePage();
