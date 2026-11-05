import { createContext, useContext, type ReactNode } from 'react';
import type { NotesApi } from './notesApi';

const NotesApiContext = createContext<NotesApi | null>(null);

export function NotesApiProvider({
  api,
  children,
}: {
  api: NotesApi;
  children: ReactNode;
}) {
  return <NotesApiContext value={api}>{children}</NotesApiContext>;
}

export function useNotesApi(): NotesApi {
  const api = useContext(NotesApiContext);
  if (api === null) {
    throw new Error('useNotesApi() must be called inside <NotesApiProvider>');
  }
  return api;
}
