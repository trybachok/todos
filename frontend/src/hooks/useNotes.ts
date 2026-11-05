import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';
import { useNotesApi } from '../api/NotesApiContext';
import { PAGE_SIZE, type Note, type NoteInput } from '../api/notesApi';

const keys = {
  list: ['notes', 'list'] as const,
  note: (id: number) => ['notes', 'note', id] as const,
};

/** All loaded pages of the list as one array of notes. */
export function useNoteList() {
  const api = useNotesApi();
  return useInfiniteQuery({
    queryKey: keys.list,
    queryFn: ({ pageParam }) => api.list(pageParam),
    initialPageParam: 0,
    getNextPageParam: (lastPage, pages) =>
      lastPage.hasMore ? pages.length * PAGE_SIZE : undefined,
    select: (data) => data.pages.flatMap((page) => page.items),
  });
}

export function useNote(id: number) {
  const api = useNotesApi();
  return useQuery({ queryKey: keys.note(id), queryFn: () => api.get(id) });
}

/** Creates a note or, given an id, replaces it. */
export function useSaveNote() {
  const api = useNotesApi();
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, input }: { id?: number; input: NoteInput }) =>
      id === undefined ? api.create(input) : api.update(id, input),
    onSuccess: (note: Note) => {
      // The response is the saved note, so opening it needs no request.
      queryClient.setQueryData(keys.note(note.id), note);
      void queryClient.invalidateQueries({ queryKey: keys.list });
    },
  });
}

export function useDeleteNote() {
  const api = useNotesApi();
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api.remove(id),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: keys.list });
    },
  });
}
