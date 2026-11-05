import { QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { App } from '../App';
import { NotesApiProvider } from '../api/NotesApiContext';
import type { NotesApi } from '../api/notesApi';
import { createQueryClient } from '../queryClient';

export function renderApp(api: NotesApi) {
  const queryClient = createQueryClient();
  queryClient.setDefaultOptions({
    queries: { ...queryClient.getDefaultOptions().queries, retryDelay: 0 },
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
