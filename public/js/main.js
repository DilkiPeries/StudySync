const socket = io();
const getUsername = () =>
  localStorage.getItem('username') ||
  document.cookie.split('; ').find(c => c.startsWith('username='))?.split('=')[1]

const user = getUsername()
if (!user) location.href = '/'


const form = document.getElementById('form');
const input = document.getElementById('input');
const messages = document.getElementById('messages');
const typingIndicator = document.getElementById('typing-indicator');

form.addEventListener('submit', e => {
  e.preventDefault();
  const text = input.value.trim();
  if (!text) return;
  socket.emit('chat message', { user, text });
  input.value = '';
});

input.addEventListener('keydown', e => {
  if (e.key === 'Tab') {
    const val = input.value;
    if (val === '@' || val.toLowerCase().startsWith('@s')) {
      e.preventDefault();
      input.value = '@SynK ';
    }
  }
});


// NOTE FOR JUDGES:
// The following part of the code contains third-part libraries that are used to render the input recieved by gemini.
// Since gemini include markdwon and unicode Katex and Marks are used to process and render.
//
//  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
//     <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
//     <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"
//         onload="renderMathInElement(document.body);"
//         data-auto-render="true">
//      </script>
// <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
//
// Above are the libraries used.
// 28/06/2025 15:42 Hiruja Edurapola


socket.on('chat message', msg => {
  const li = document.createElement('li')

  const seed = msg.user.startsWith('SynK') 
    ? 'bottts/svg?seed=SynK' 
    : `initials/svg?seed=${encodeURIComponent(msg.user)}`
  const avatarUrl = `https://api.dicebear.com/9.x/${seed}`

  const raw = msg.text
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/(@SynK)/gi, '<span class="mention">$1</span>')
  const html = marked.parse(raw)

  li.innerHTML = `
    <img class="avatar" src="${avatarUrl}" alt="${msg.user}"/>
    <div class="bubble">
      <strong>${msg.user}:</strong>
      ${html}
    </div>
  `
  messages.appendChild(li)
  messages.scrollTop = messages.scrollHeight


  if (window.renderMathInElement) {
    renderMathInElement(li, {
      delimiters: [
        { left: "$$", right: "$$", display: true },
        { left: "$",  right: "$",  display: false }
      ]
    })
  }
})



socket.on('typing', () => {
  typingIndicator.classList.remove('hidden');
});

socket.on('stopTyping', () => {
  typingIndicator.classList.add('hidden');
});

const CHAT_KEY = 'studysync_history'

function saveChatHistory() {
  const raw = Array.from(document.querySelectorAll('#messages li')).map(li => li.innerHTML)
  localStorage.setItem(CHAT_KEY, JSON.stringify(raw))
}

function restoreChatHistory() {
  const saved = JSON.parse(localStorage.getItem(CHAT_KEY) || '[]')
  for (const html of saved) {
    const li = document.createElement('li')
    li.innerHTML = html
    messages.appendChild(li)
    if (window.renderMathInElement) renderMathInElement(li)
  }
  messages.scrollTop = messages.scrollHeight
}

restoreChatHistory()

socket.on('chat message', msg => {
  saveChatHistory()
})
