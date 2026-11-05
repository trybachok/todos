import type { NoteSummary } from '../api/notesApi';

interface Props {
  notes: NoteSummary[];
}

export function NoteList({ notes }: Props) {
  if (notes.length === 0) {
    return <p className="hint">Заметок пока нет</p>;
  }

  return (
    <ul className="note-list" aria-label="Заметки">
      {notes.map((note) => (
        <li key={note.id}>{note.title}</li>
      ))}
    </ul>
  );
}
