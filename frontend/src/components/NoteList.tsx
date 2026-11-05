import type { NoteSummary } from '../api/notesApi';

interface Props {
  notes: NoteSummary[];
  selectedId?: number;
  onSelect: (id: number) => void;
}

export function NoteList({ notes, selectedId, onSelect }: Props) {
  if (notes.length === 0) {
    return <p className="hint">Заметок пока нет</p>;
  }

  return (
    <ul className="note-list" aria-label="Заметки">
      {notes.map((note) => (
        <li key={note.id}>
          <button
            type="button"
            aria-current={note.id === selectedId || undefined}
            onClick={() => onSelect(note.id)}
          >
            {note.title}
          </button>
        </li>
      ))}
    </ul>
  );
}
