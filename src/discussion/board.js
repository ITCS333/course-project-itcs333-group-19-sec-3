// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const newTopicForm = document.getElementById('new-topic-form');
const topicListContainer = document.getElementById('topic-list-container');
const topicSubjectInput = document.getElementById('topic-subject-input');
const topicMessageInput = document.getElementById('topic-message-input');

// --- Functions ---

// 1. Create <article> element for a topic
function createTopicArticle(topic) {
    const article = document.createElement('article');

    // Heading with link
    const h3 = document.createElement('h3');
    const a = document.createElement('a');
    a.href = `topic.html?id=${topic.id}`;
    a.textContent = topic.subject;
    h3.appendChild(a);
    article.appendChild(h3);

    // Footer with author and date
    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
    article.appendChild(footer);

    // Actions container
    const actions = document.createElement('div');
    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    // Note: Edit functionality not implemented in this exercise
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.classList.add('delete-btn');
    deleteBtn.dataset.id = topic.id;

    actions.appendChild(editBtn);
    actions.appendChild(deleteBtn);
    article.appendChild(actions);

    return article;
}

// 2. Render all topics
function renderTopics() {
    topicListContainer.innerHTML = '';
    topics.forEach(topic => {
        const article = createTopicArticle(topic);
        topicListContainer.appendChild(article);
    });
}

// 3. Handle new topic form submission
function handleCreateTopic(event) {
    event.preventDefault();
    const subject = topicSubjectInput.value.trim();
    const message = topicMessageInput.value.trim();

    if (!subject || !message) return;

    const newTopic = {
        id: `topic_${Date.now()}`,
        subject,
        message,
        author: 'Student', // hardcoded
        date: new Date().toISOString().split('T')[0]
    };

    topics.push(newTopic);
    renderTopics();
    newTopicForm.reset();
}

// 4. Handle delete button clicks
function handleTopicListClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const id = event.target.dataset.id;
        topics = topics.filter(t => t.id !== id);
        renderTopics();
    }
}

// 5. Load topics from JSON and initialize
async function loadAndInitialize() {
    try {
        const res = await fetch('topics.json');
        if (!res.ok) throw new Error('Failed to load topics.json');
        topics = await res.json();
        renderTopics();
    } catch (error) {
        console.error('Error loading topics:', error);
        topicListContainer.innerHTML = '<p>Failed to load topics.</p>';
    }

    // Event listeners
    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
}

// --- Initial Page Load ---
loadAndInitialize();
