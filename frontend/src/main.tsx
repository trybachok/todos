import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './App';
import { NotesApiProvider } from './api/NotesApiContext';
import { ApiError, createNotesApi } from './api/notesApi';
import './styles.css';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60_000,
      // A server answer does not change on retry, a lost connection may recover.
      retry: (failures, error) => !(error instanceof ApiError) && failures < 2,
    },
  },
});

const root = document.getElementById('root');
if (root === null) {
  throw new Error('The page has no #root element');
}

createRoot(root).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <NotesApiProvider api={createNotesApi()}>
        <App />
      </NotesApiProvider>
    </QueryClientProvider>
  </StrictMode>,
);
