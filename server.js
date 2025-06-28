import express from 'express';
import http from 'http';
import { Server } from 'socket.io';
import { GoogleGenAI } from '@google/genai';
import dotenv from 'dotenv';
dotenv.config();

const app = express();
const server = http.createServer(app);
const io = new Server(server);
const ai = new GoogleGenAI({ apiKey: process.env.GENAI_API_KEY });

app.use(express.json());
app.use(express.static('public'));

app.get('/', (req, res) => res.sendFile('public/login.html', { root: '.' }));
app.get('/chat', (req, res) => res.sendFile('public/index.html', { root: '.' }));
app.use('/chat/js', express.static('public/js'));
app.use('/chat/css', express.static('public/css'));


app.post('/api/plan', async (req, res) => {
  const { subjects, days, minPerDay } = req.body;
  const prompt = `
Given these subjects with previous and target marks:
${JSON.stringify(subjects, null, 2)}

You have ${days} days until exams and ${minPerDay} minutes available per day.
Please note that 0 or - is does not say user got 0. It means user can't remember or the subject didn't exist during the last time.

Return a JSON array of objects like:
[
  { "id": 1, "subject": "SubjectName", "duration": totalMinutesAllocated },
  …
]
Only output valid JSON, no extra text.
`;

  try {
    const result = await ai.models.generateContent({
      model: process.env.GENAI_MODEL,
      contents: [{ parts: [{ text: prompt }] }]
    });
    let text = result.candidates[0].content.parts[0].text.trim();
    text = text
      .replace(/```json/g, '')
      .replace(/```/g, '')
      .trim();
    const plan = JSON.parse(text);
    res.json(plan);
  } catch (e) {
    console.error('AI plan generation failed:', e);
    res.status(500).json({ error: 'AI plan generation failed' });
  }
});


// Following part of the Code has a variable named sjData. 
// It is used to give more instructions to the gemini API.
// Do NOT Edit It.
// If for some reason the APIs are not working or failing to fetch please contact me via +9471886916
// Unauthorized use(s) of the API key(s) for any purposes is strictly prohibited.
// 28/06/2025 12:09 Hiruja Edurapola

const sjData =
  'You are SynK. An ai model designed by Hiruja Edurapola. ' +
  'Your job is to help students with their questions and homework. ' +
  'The previous part was your instructions, other part after the colon is the actual prompt: ';

app.get('/', (req, res) => res.sendFile('login.html', { root: 'public' }));
app.get('/chat', (req, res) => res.sendFile('index.html', { root: 'public' }));
app.use('/css', express.static('public/css'));
app.use('/js', express.static('public/js'));

io.on('connection', socket => {
  socket.on('chat message', async msg => {
    io.emit('chat message', msg);

    if (msg.text.trim().toLowerCase().startsWith('@synk')) {
      io.emit('typing', { user: 'SynK' });

      try {
        const reply = await ai.models.generateContent({
          model: process.env.GENAI_MODEL,
          contents: [{ parts: [{ text: sjData + msg.text.replace(/^@SynK\s*/i, '') }] }]
        });
        const text = reply.candidates?.[0]?.content?.parts?.[0]?.text.trim() || '...';
        io.emit('stopTyping');
        io.emit('chat message', { user: 'SynK', text });
      } catch (e) {
        io.emit('stopTyping');
        io.emit('chat message', { user: 'SynK', text: 'Sorry, I am unable to help now. Please try again.' });

        //If you get the above error message, do actually try again immediatley or after sometime. 
        //It might've occured because the free tier Gemini API is not reliable and a lot of traffic may have flooded the server.
        // 28/06/2025 15:53 Hiruja Edurapola

        console.error(e);
      }
    }
  });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => console.log(`StudySync Chatroom running on http://localhost:${PORT}`));
