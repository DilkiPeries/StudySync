const sets = ['biology', 'geography', 'general_knowledge', 'astronomy'];

const params = new URLSearchParams(window.location.search);
const req = params.get('flashcard-set');
const selectedSet = sets.includes(req) ? req : sets[Math.floor(Math.random() * sets.length)];

const titleEl = document.getElementById('set-title');
const container = document.getElementById('flashcards-container');

titleEl.textContent = 'Loading…';

fetch(`sets/${selectedSet}.json`)
  .then(res => {
    if (!res.ok) throw new Error('Not found');
    return res.json();
  })
  .then(data => {
    // update title
    titleEl.textContent = data.title || selectedSet.replace(/_/g, ' ');
    // clear any placeholder
    container.innerHTML = '';

    // create one card per question
    data.questions.forEach(({ question, answer }) => {
      const card = document.createElement('div');
      card.className = 'flashcard';

      const inner = document.createElement('div');
      inner.className = 'flashcard-inner';

      const front = document.createElement('div');
      front.className = 'flashcard-front';
      front.textContent = question;

      const back = document.createElement('div');
      back.className = 'flashcard-back';
      back.textContent = answer;

      inner.append(front, back);
      card.appendChild(inner);
      card.addEventListener('click', () => card.classList.toggle('flipped'));
      container.appendChild(card);
    });
  })
  .catch(err => {
    titleEl.textContent = 'Error loading set';
    container.innerHTML = `<p style="color:red; text-align:center;">
      Could not load “${selectedSet}”.<br>${err.message}
    </p>`;
    console.error(err);
  });