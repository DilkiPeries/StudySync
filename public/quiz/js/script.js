let current = 0;
let score = 0;
let data = null;

// Available question sets
const availableSets = [
  "algebra",
  "science-basics",
  "geography-world",
  "computer-fundamentals",
  "history-sri-lanka"
];

// Utility to get query param
function getQueryParam(param) {
  const url = new URL(window.location.href);
  return url.searchParams.get(param);
}

const selectedSet = getQueryParam("question-set") || randomSet();
const jsonPath = `question-sets/${selectedSet}.json`;

fetch(jsonPath)
  .then(res => {
    if (!res.ok) throw new Error("Set not found");
    return res.json();
  })
  .then(json => {
    data = json;
    document.getElementById("quiz-title").textContent = json.title;
    document.title = json.title;
    showQuestion();
  })
  .catch(err => {
    document.querySelector(".quiz-container").innerHTML =
      `<h1 style="color:red">❌ Error</h1><p>${err.message}</p>`;
  });

function randomSet() {
  const randomIndex = Math.floor(Math.random() * availableSets.length);
  return availableSets[randomIndex];
}

function showQuestion() {
  const q = data.questions[current];
  document.getElementById("question").textContent = q.question;
  const optionsEl = document.getElementById("options");
  optionsEl.innerHTML = "";

  q.options.forEach(opt => {
    const btn = document.createElement("button");
    btn.textContent = opt;
    btn.classList.add("option-btn");
    btn.onclick = () => selectAnswer(btn, q.answer);
    optionsEl.appendChild(btn);
  });

  document.getElementById("next-btn").classList.add("hidden");
}

function selectAnswer(button, correctAnswer) {
  const selected = button.textContent;
  const buttons = document.querySelectorAll(".option-btn");

  buttons.forEach(btn => {
    btn.disabled = true;
    if (btn.textContent === correctAnswer) {
      btn.classList.add("correct");
    } else if (btn.textContent === selected) {
      btn.classList.add("wrong");
    }
  });

  if (selected === correctAnswer) score++;
  document.getElementById("next-btn").classList.remove("hidden");
}

document.getElementById("next-btn").onclick = () => {
  current++;
  if (current < data.questions.length) {
    showQuestion();
  } else {
    document.getElementById("quiz-box").classList.add("hidden");
    document.getElementById("result-box").classList.remove("hidden");
    document.getElementById("score-text").textContent =
      `You got ${score} out of ${data.questions.length} correct.`;
  }
};