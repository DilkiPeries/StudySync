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
