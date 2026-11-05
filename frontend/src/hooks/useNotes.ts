import { useInfiniteQuery } from '@tanstack/react-query';
import { useNotesApi } from '../api/NotesApiContext';
import { PAGE_SIZE } from '../api/notesApi';

const keys = {
  list: ['notes', 'list'] as const,
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
