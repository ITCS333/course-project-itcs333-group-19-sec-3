const topicsContainer = document.getElementById('topic-list-container');
const newTopicForm = document.getElementById('new-topic-form');
const topicSubject = document.getElementById('topic-subject');
const topicMessage = document.getElementById('topic-message');

let topics = [];

// ------------------------------
// Fetch all topics
// ------------------------------
async function fetchTopics(){
    try{
        const res = await fetch('src/discussion/discussion.php?resource=topics');
        const data = await res.json();
        if(data.success){
            topics = data.data;
            renderTopics();
        } else console.error(data.error);
    }catch(err){ 
        console.error(err); 
        topicsContainer.innerHTML='<p>Error loading topics.</p>'; 
    }
}

// ------------------------------
// Render topics
// ------------------------------
function renderTopics(){
    topicsContainer.innerHTML='';
    if(topics.length===0){ 
        topicsContainer.innerHTML='<p>No topics.</p>'; 
        return; 
    }

    topics.forEach(t=>{
        const article = document.createElement('article');

        // Subject and link to topic page
        const h3 = document.createElement('h3');
        const a = document.createElement('a');
        a.href=`topic.html?id=${t.id}`;
        a.textContent=t.subject;
        h3.appendChild(a);
        article.appendChild(h3);

        // Author and created_at
        const footer = document.createElement('footer');
        footer.textContent=`Posted by: ${t.author} on ${t.created_at}`;
        article.appendChild(footer);

        // Actions: Delete button (ownership-based handled in PHP)
        const actions = document.createElement('div');
        const delBtn = document.createElement('button');
        delBtn.textContent='Delete';
        delBtn.dataset.id=t.id;
        delBtn.classList.add('delete-btn');
        actions.appendChild(delBtn);
        article.appendChild(actions);

        topicsContainer.appendChild(article);
    });
}

// ------------------------------
// Create new topic
// ------------------------------
async function createTopic(e){
    e.preventDefault();
    const subject = topicSubject.value.trim();
    const message = topicMessage.value.trim();
    if(!subject || !message) return;

    const payload = { subject, message };

    try{
        const res = await fetch('src/discussion/discussion.php?resource=topics',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(payload)
        });
        const data = await res.json();
        if(data.success){
            fetchTopics();
            newTopicForm.reset();
        } else alert(data.error || 'Failed to create topic');
    }catch(err){ 
        console.error(err); 
        alert('Error creating topic'); 
    }
}

// ------------------------------
// Handle click events (Delete)
// ------------------------------
async function handleTopicClick(e){
    if(e.target.classList.contains('delete-btn')){
        const id = e.target.dataset.id;
        if(!confirm('Delete this topic?')) return;
        try{
            const res = await fetch(`src/discussion/discussion.php?resource=topics&id=${id}`,{
                method:'DELETE'
            });
            const data = await res.json();
            if(data.success) fetchTopics();
            else alert(data.error || 'Failed to delete topic');
        }catch(err){ 
            console.error(err); 
            alert('Error deleting topic'); 
        }
    }
}

// ------------------------------
// Initialize
// ------------------------------
window.addEventListener('DOMContentLoaded',()=>{
    fetchTopics();
    newTopicForm.addEventListener('submit',createTopic);
    topicsContainer.addEventListener('click',handleTopicClick);
});
