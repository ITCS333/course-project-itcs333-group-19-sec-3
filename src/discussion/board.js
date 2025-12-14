// ------------------------------
// Element Selections
// ------------------------------
const topicsContainer = document.getElementById('topic-list-container');
const newTopicForm = document.getElementById('new-topic-form');
const topicSubject = document.getElementById('topic-subject');
const topicMessage = document.getElementById('topic-message');

let topics = [];


function createTopicArticle(topic) {
    const article = document.createElement('article');

    const h3 = document.createElement('h3');
    const a = document.createElement('a');
    a.href = `topic.html?id=${topic.id}`;
    a.textContent = topic.subject;
    h3.appendChild(a);
    article.appendChild(h3);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${topic.author} on ${topic.date || topic.created_at}`;
    article.appendChild(footer);

    return article;
}

// ------------------------------
// Render topics
// ------------------------------
function renderTopics() {
    topicsContainer.innerHTML = '';

    if (topics.length === 0) {
        topicsContainer.innerHTML = '<p>No topics.</p>';
        return;
    }

    topics.forEach(topic => {
        topicsContainer.appendChild(createTopicArticle(topic));
    });
}

// ------------------------------
// Fetch all topics
// ------------------------------
async function fetchTopics() {
    try {
        const res = await fetch('src/discussion/discussion.php?resource=topics');
        const data = await res.json();

        if (data.success) {
            topics = data.data;
            renderTopics();
        }
    } catch (err) {
        console.error(err);
        topicsContainer.innerHTML = '<p>Error loading topics.</p>';
    }
}

function handleCreateTopic(event) {
    event.preventDefault();

    const subject = topicSubject.value.trim();
    const message = topicMessage.value.trim();
    if (!subject || !message) return;

    const payload = { subject, message };

    fetch('src/discussion/discussion.php?resource=topics', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchTopics();
            newTopicForm.reset();
        }
    })
    .catch(err => console.error(err));
}


function handleTopicListClick(event) {
    if (!event.target.closest('article')) return;
}


async function loadAndInitialize() {
    await fetchTopics();
    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicsContainer.addEventListener('click', handleTopicListClick);
}

// ------------------------------
// Initialize page
// ------------------------------
document.addEventListener('DOMContentLoaded', loadAndInitialize);
