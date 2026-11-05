import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { App } from '../App';
import { NotesApiProvider } from '../api/NotesApiContext';
import type { NotesApi } from '../api/notesApi';

export function renderApp(api: NotesApi) {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });
  render(
    <QueryClientProvider client={queryClient}>
      <NotesApiProvider api={api}>
        <App />
      </NotesApiProvider>
    </QueryClientProvider>,
  );
  return userEvent.setup();
}
