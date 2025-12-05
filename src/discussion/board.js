// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const newTopicForm = document.getElementById('new-topic-form');
const topicListContainer = document.getElementById('topic-list-container');
const topicSubjectInput = document.getElementById('topic-subject');
const topicMessageInput = document.getElementById('topic-message');

// --- Functions ---

// 1. Create <article> element for a topic
function createTopicArticle(topic) {
    const article = document.createElement('article');

    const h3 = document.createElement('h3');
    const a = document.createElement('a');
    a.href = `topic.html?id=${topic.id}`;
    a.textContent = topic.subject;
    h3.appendChild(a);
    article.appendChild(h3);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${topic.author} on ${topic.created_at}`;
    article.appendChild(footer);

    const actions = document.createElement('div');
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.classList.add('delete-btn');
    deleteBtn.dataset.id = topic.id;
    actions.appendChild(deleteBtn);
    article.appendChild(actions);

    return article;
}

// 2. Render all topics
function renderTopics() {
    topicListContainer.innerHTML = '';
    if (topics.length === 0) {
        topicListContainer.innerHTML = '<p>No topics found.</p>';
        return;
    }
    topics.forEach(topic => {
        const article = createTopicArticle(topic);
        topicListContainer.appendChild(article);
    });
}

// 3. Fetch topics from API
async function fetchAndRenderTopics() {
    try {
        const res = await fetch('/api/discussion.php?resource=topics');
        const data = await res.json();
        if(data.success){
            topics = data.data;
            renderTopics();
        } else {
            topicListContainer.innerHTML = '<p>Failed to load topics.</p>';
            console.error(data.error);
        }
    } catch(err) {
        console.error(err);
        topicListContainer.innerHTML = '<p>Error loading topics.</p>';
    }
}

// 4. Handle new topic form submission
async function handleCreateTopic(event) {
    event.preventDefault();
    const subject = topicSubjectInput.value.trim();
    const message = topicMessageInput.value.trim();
    if(!subject || !message) return;

    const payload = {
        subject,
        message,
        // author: 'Student' // optional for testing; server prefers session username
    };

    try {
        const res = await fetch('/api/discussion.php?resource=topics', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success){
            // server returns the created topic in data.data
            fetchAndRenderTopics();
            newTopicForm.reset();
        } else {
            alert(data.error || 'Failed to create topic.');
        }
    } catch(err) {
        console.error(err);
        alert('Error creating topic.');
    }
}

// 5. Handle delete button click
async function handleTopicListClick(event) {
    if(event.target.classList.contains('delete-btn')){
        const id = event.target.dataset.id;
        if(!confirm('Are you sure you want to delete this topic?')) return;
        try {
            const res = await fetch(`/api/discussion.php?resource=topics&id=${id}`, {
                method: 'DELETE'
            });
            const data = await res.json();
            if(data.success){
                fetchAndRenderTopics();
            } else {
                alert(data.error || 'Failed to delete topic.');
            }
        } catch(err){
            console.error(err);
            alert('Error deleting topic.');
        }
    }
}

// --- Initial Page Load ---
window.addEventListener('DOMContentLoaded', () => {
    fetchAndRenderTopics();
    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
});
