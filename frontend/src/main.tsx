import { QueryClientProvider } from '@tanstack/react-query';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import { NotesApiProvider } from './api/NotesApiContext';
import { createNotesApi } from './api/notesApi';
import { createQueryClient } from './queryClient';
import './styles.css';

const root = document.getElementById('root');
if (root === null) {
  throw new Error('The page has no #root element');
}

createRoot(root).render(
  <StrictMode>
    <QueryClientProvider client={createQueryClient()}>
      <NotesApiProvider api={createNotesApi()}>
        <App />
      </NotesApiProvider>
    </QueryClientProvider>
  </StrictMode>,
);
