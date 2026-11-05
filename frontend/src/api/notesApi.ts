import createClient from 'openapi-fetch';
import type { components, paths } from './schema.gen';

type Schemas = components['schemas'];
export type NoteSummary = Schemas['NoteSummary'];
export type Note = Schemas['Note'];
export type NoteInput = Schemas['NoteInput'];
export type NotePage = Schemas['NotePage'];
type Problem = Schemas['Problem'] &
  Partial<Pick<Schemas['ValidationProblem'], 'errors'>>;

export const PAGE_SIZE = 20;

export interface NotesApi {
  list(offset: number): Promise<NotePage>;
  get(id: number): Promise<Note>;
  create(input: NoteInput): Promise<Note>;
  update(id: number, input: NoteInput): Promise<Note>;
  remove(id: number): Promise<void>;
}

/** The server answered with an error status. */
export class ApiError extends Error {
  readonly problem: Problem;

  constructor(problem: Problem) {
    super(problem.detail ?? problem.title);
    this.name = 'ApiError';
    this.problem = problem;
  }
}

export function createNotesApi(fetch = globalThis.fetch): NotesApi {
  const client = createClient<paths>({ baseUrl: '/api', fetch });
  const note = (id: number) => ({ params: { path: { id } } });

  return {
    list: (offset) =>
      unwrap(
        client.GET('/notes', {
          params: { query: { limit: PAGE_SIZE, offset } },
        }),
      ),
    get: (id) => unwrap(client.GET('/notes/{id}', note(id))),
    create: (body) => unwrap(client.POST('/notes', { body })),
    update: (id, body) =>
      unwrap(client.PUT('/notes/{id}', { ...note(id), body })),
    remove: (id) => unwrap(client.DELETE('/notes/{id}', note(id))),
  };
}

async function unwrap<T>(
  request: Promise<{ data?: T; error?: unknown; response: Response }>,
): Promise<T> {
  const { data, error, response } = await request;
  if (!response.ok) {
    throw new ApiError(
      isProblem(error)
        ? error
        : {
            type: 'about:blank',
            title: response.statusText,
            status: response.status,
          },
    );
  }
  return data as T;
}

function isProblem(body: unknown): body is Problem {
  return typeof body === 'object' && body !== null && 'title' in body;
}
