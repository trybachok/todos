import {
  ApiError,
  PAGE_SIZE,
  type Note,
  type NoteInput,
  type NotePage,
  type NotesApi,
} from '../api/notesApi';

/** In-memory stand-in for the server: newest notes first, no validation rules. */
export class FakeNotesApi implements NotesApi {
  private notes: Note[] = [];
  private nextId = 1;

  constructor(titles: string[] = []) {
    titles.forEach((title) => this.add({ title, content: `Текст: ${title}` }));
  }

  async list(offset: number): Promise<NotePage> {
    return {
      items: this.notes
        .slice(offset, offset + PAGE_SIZE)
        .map(({ id, title, createdAt, updatedAt }) => ({
          id,
          title,
          createdAt,
          updatedAt,
        })),
      hasMore: offset + PAGE_SIZE < this.notes.length,
    };
  }

  async get(id: number): Promise<Note> {
    return this.find(id);
  }

  async create(input: NoteInput): Promise<Note> {
    return this.add(input);
  }

  async update(id: number, input: NoteInput): Promise<Note> {
    const note = { ...this.find(id), ...input, updatedAt: now() };
    this.notes = this.notes.map((stored) => (stored.id === id ? note : stored));
    return note;
  }

  async remove(id: number): Promise<void> {
    this.find(id);
    this.notes = this.notes.filter((note) => note.id !== id);
  }

  private add(input: NoteInput): Note {
    const note = {
      id: this.nextId++,
      ...input,
      createdAt: now(),
      updatedAt: now(),
    };
    this.notes.unshift(note);
    return note;
  }

  private find(id: number): Note {
    const note = this.notes.find((stored) => stored.id === id);
    if (!note) {
      throw new ApiError({
        type: 'about:blank',
        title: 'Not Found',
        status: 404,
      });
    }
    return note;
  }
}

function now(): string {
  return new Date().toISOString();
}
