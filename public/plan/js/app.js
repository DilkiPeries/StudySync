let state = JSON.parse(localStorage.getItem('planState') || '{}');
const save = () =>
  localStorage.setItem('planState', JSON.stringify(state));
const show = id =>
  document.querySelectorAll('.step')
    .forEach(el => el.classList.toggle('active', el.id === id));


window.addEventListener('load', () => {
  if (!state.name) return show('step-name');
  document.getElementById('show-name').textContent = state.name;
  document.getElementById('show-name-2').textContent = state.name;
  if (!state.grade) return show('step-grade');
  if (!state.subjects) return initSubjects();
  if (!state.days) return show('step-timing');
  show('step-dashboard');
  renderDashboard();
});


document.getElementById('btn-name-next').onclick = () => {
  const v = document.getElementById('input-name').value.trim();
  if (!v) return;
  state.name = v; save();
  document.getElementById('show-name').textContent = v;
  document.getElementById('show-name-2').textContent = v;
  show('step-grade');
};


document.querySelectorAll('#step-grade .options button')
  .forEach(b => b.onclick = () => {
    state.grade = b.dataset.grade;
    const groups = ['Group 1','Group 2','Group 3'];
    const all = {
      '6-9': ['English','Sinhala','Math','Science','Civic','Geography','History','Health','Religion','Aesthetic','Tamil','PTS'],
      '10-11': ['English','Sinhala','Math','Science','History','Religion', ...groups],
      '12-13': [...groups]
    };
    state.subjects = all[state.grade].map(name => ({
      name, mark: null, target: null
    }));
    state.current = 0; save();
    initSubjects();
  });


function initSubjects() {
  const s = state.subjects[state.current];
  document.getElementById('subject-name').textContent = s.name;
  document.getElementById('total-subjects').textContent = state.subjects.length;
  document.getElementById('subject-index').textContent = state.current + 1;
  document.getElementById('input-mark').value = s.mark ?? '';
  document.getElementById('input-target').value = s.target ?? '';

  
  const groupDiv = document.getElementById('group-name-input');
  if (s.name.startsWith('Group')) {
    groupDiv.style.display = 'block';
    const inG = document.getElementById('input-group-name');
    inG.value = s.name;
    inG.onchange = () => {
      s.name = inG.value.trim() || s.name;
      save();
      document.getElementById('subject-name').textContent = s.name;
    };
  } else groupDiv.style.display = 'none';

  show('step-subjects');
}

document.getElementById('btn-skip').onclick = () =>
  document.getElementById('input-mark').value = '';

document.getElementById('btn-subject-next').onclick = () => {
  const m = parseInt(document.getElementById('input-mark').value);
  const t = parseInt(document.getElementById('input-target').value);
  if (isNaN(t)) return alert('Enter your target mark');
  const s = state.subjects[state.current];
  s.mark = isNaN(m) ? null : m;
  s.target = t;
  save();
  state.current++;
  if (state.current < state.subjects.length) initSubjects();
  else show('step-timing');
};


document.getElementById('btn-generate').onclick = async () => {
  const days = parseInt(document.getElementById('input-days').value);
  const minPerDay = parseInt(document.getElementById('input-minutes').value);
  if (isNaN(days) || isNaN(minPerDay))
    return alert('Fill both fields');
  state.days = days; state.minPerDay = minPerDay; save();

  
  const res = await fetch('/api/plan', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      subjects: state.subjects.map(s => ({
        subject: s.name,
        previous: s.mark ?? 0,
        target: s.target
      })),
      days, minPerDay
    })
  });
  if (!res.ok) return alert('AI plan failed');
  const aiPlan = await res.json();
  
  state.plan = aiPlan.map(item => ({
    id: item.id,
    name: item.subject,
    duration: item.duration,      
    durationSec: item.duration * 60,
    studiedSec: 0,
    running: false
  }));
  save();
  show('step-dashboard');
  renderDashboard();
};

//
// How Not To Define Variables 101 below :)
//

function renderDashboard() {
  const totalReq = state.plan.reduce((a,s) => a + s.durationSec, 0);
  const totalDone = state.plan.reduce((a,s) => a + s.studiedSec, 0);
  const overallPct = totalReq ? Math.round(totalDone / totalReq * 100) : 0;
  document.getElementById('global-bar').style.width = overallPct + '%';
  document.getElementById('global-label').textContent =
    `Overall ${overallPct}%`;

  const container = document.querySelector('.subjects');
  container.innerHTML = '';
  state.plan.forEach((s,i) => {
    const pct = s.durationSec
      ? Math.round(s.studiedSec / s.durationSec * 100)
      : 0;
    const eH = Math.floor(s.studiedSec / 3600);
    const eM = Math.floor((s.studiedSec % 3600) / 60);
    const eS = s.studiedSec % 60;
    const rSec = s.durationSec - s.studiedSec;
    const rH = Math.floor(rSec / 3600);
    const rM = Math.floor((rSec % 3600) / 60);
    const rS = rSec % 60;

    const el = document.createElement('div');
    el.className = 'subject';
    el.innerHTML = `
      <div class="ring">
        <svg viewBox="0 0 100 100">
          <circle class="bg" cx="50" cy="50" r="45"/>
          <circle class="fg" cx="50" cy="50" r="45"
            style="stroke-dashoffset:${283 - pct/100*283}"/>
        </svg>
        <div class="label">${pct}%</div>
      </div>
      <div class="subject-info">
        <span><strong>${s.name}</strong></span>
        <span>Prev: ${s.mark ?? '–'} • Target: ${s.target ?? '–'}</span>
        <span>Done: ${eH}h ${eM}m ${eS}s</span>
        <span>Remaining: ${rH}h ${rM}m ${rS}s</span>
        <div class="timer-controls">
          <button data-idx="${i}">
            ${s.running ? '⏸️' : '▶️'}
          </button>
        </div>
      </div>`;
    container.appendChild(el);
  });
  attachTimers();
}


function attachTimers() {
  document.querySelectorAll('.timer-controls button')
    .forEach(btn => btn.onclick = () => {
      const i = +btn.dataset.idx;
      const s = state.plan[i];
      if (s.running) {
        clearInterval(s._int);
        s.running = false;
        btn.textContent = 'Start';
      } else {
        s.running = true;
        btn.textContent = 'Pause';
        s._int = setInterval(() => {
          if (s.studiedSec < s.durationSec) {
            s.studiedSec++;
            save();
            updateSubject(i);
            updateGlobal();
          } else {
            clearInterval(s._int);
            s.running = false;
            btn.textContent = '✅';
          }
        }, 1000);
      }
      save();
    });
}

function updateSubject(i) {
  const s = state.plan[i];
  const el = document.querySelectorAll('.subject')[i];
  const pct = s.durationSec
    ? Math.round(s.studiedSec / s.durationSec * 100)
    : 0;
  const eH = Math.floor(s.studiedSec / 3600);
  const eM = Math.floor((s.studiedSec % 3600) / 60);
  const eS = s.studiedSec % 60;
  const rSec = s.durationSec - s.studiedSec;
  const rH = Math.floor(rSec / 3600);
  const rM = Math.floor((rSec % 3600) / 60);
  const rS = rSec % 60;

  el.querySelector('.fg').style.strokeDashoffset =
    283 - pct/100*283;
  el.querySelector('.label').textContent = pct + '%';
  const spans = el.querySelectorAll('.subject-info span');
  spans[2].textContent = `Done: ${eH}h ${eM}m ${eS}s`;
  spans[3].textContent =
    `Remaining: ${rH}h ${rM}m ${rS}s`;
}

function updateGlobal() {
  const totalReq = state.plan.reduce((a,s) => a + s.durationSec, 0);
  const totalDone = state.plan.reduce((a,s) => a + s.studiedSec, 0);
  const pct = totalReq ? Math.round(totalDone / totalReq * 100) : 0;
  document.getElementById('global-bar').style.width = pct + '%';
  document.getElementById('global-label').textContent =
    `Overall ${pct}%`;
}

document.getElementById('btn-reset').onclick = () => {
  if (confirm('Erase all data?')) {
    localStorage.removeItem('planState');
    location.reload();
  }
};
