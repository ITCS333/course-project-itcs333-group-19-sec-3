// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = [];

// --- Element Selections ---
const topicSubject = document.getElementById('topic-subject');
const opMessage = document.getElementById('op-message');
const opFooter = document.getElementById('op-footer');
const replyListContainer = document.getElementById('reply-list-container');
const replyForm = document.getElementById('reply-form');
const newReplyText = document.getElementById('new-reply');

// --- Functions ---

function getTopicIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

function renderOriginalPost(topic) {
    topicSubject.textContent = topic.subject;
    opMessage.textContent = topic.message;
    opFooter.textContent = `Posted by: ${topic.author} on ${topic.created_at}`;
}

function createReplyArticle(reply) {
    const article = document.createElement('article');

    const p = document.createElement('p');
    p.textContent = reply.text;
    article.appendChild(p);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${reply.author} on ${reply.created_at}`;
    article.appendChild(footer);

    const actions = document.createElement('div');
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.classList.add('delete-reply-btn');
    deleteBtn.dataset.id = reply.reply_id;
    actions.appendChild(deleteBtn);
    article.appendChild(actions);

    return article;
}

function renderReplies() {
    replyListContainer.innerHTML = '';
    if(currentReplies.length === 0){
        replyListContainer.innerHTML = '<p>No replies yet.</p>';
        return;
    }
    currentReplies.forEach(reply => {
        replyListContainer.appendChild(createReplyArticle(reply));
    });
}

// Fetch topic from API
async function fetchTopic() {
    try {
        const res = await fetch(`/api/discussion.php?resource=topics&id=${currentTopicId}`);
        const data = await res.json();
        if(data.success){
            renderOriginalPost(data.data);
        } else {
            topicSubject.textContent = 'Topic not found.';
        }
    } catch(err){
        console.error(err);
        topicSubject.textContent = 'Error loading topic.';
    }
}

// Fetch replies from API
async function fetchReplies() {
    try {
        const res = await fetch(`/api/discussion.php?resource=replies&topic_id=${currentTopicId}`);
        const data = await res.json();
        if(data.success){
            currentReplies = data.data;
            renderReplies();
        } else {
            replyListContainer.innerHTML = '<p>No replies found.</p>';
        }
    } catch(err){
        console.error(err);
        replyListContainer.innerHTML = '<p>Error loading replies.</p>';
    }
}

// Add new reply
async function handleAddReply(event){
    event.preventDefault();
    const text = newReplyText.value.trim();
    if(!text) return;

    const payload = {
        reply_id: `reply_${Date.now()}`,
        topic_id: currentTopicId,
        text,
        author: 'Student' // لاحقًا يمكن الحصول من $_SESSION
    };

    try{
        const res = await fetch('/api/discussion.php?resource=replies', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success){
            fetchReplies();
            replyForm.reset();
        } else {
            alert(data.error);
        }
    } catch(err){
        console.error(err);
        alert('Error posting reply.');
    }
}

// Delete a reply
async function handleReplyListClick(event){
    if(event.target.classList.contains('delete-reply-btn')){
        const replyId = event.target.dataset.id;
        try{
            const res = await fetch(`/api/discussion.php?resource=replies&id=${replyId}`, {
                method: 'DELETE'
            });
            const data = await res.json();
            if(data.success){
                fetchReplies();
            } else {
                alert(data.error);
            }
        } catch(err){
            console.error(err);
            alert('Error deleting reply.');
        }
    }
}

// --- Initial Page Load ---
window.addEventListener('DOMContentLoaded', () => {
    currentTopicId = getTopicIdFromURL();
    if(!currentTopicId){
        topicSubject.textContent = 'Topic not found.';
        return;
    }
    fetchTopic();
    fetchReplies();
    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);
});
