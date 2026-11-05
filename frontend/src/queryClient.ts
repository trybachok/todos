import { QueryClient } from '@tanstack/react-query';
import { ApiError } from './api/notesApi';

export function createQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 60_000,
        // A server answer does not change on retry, a lost connection may recover.
        retry: (failures, error) =>
          !(error instanceof ApiError) && failures < 2,
      },
    },
  });
}
