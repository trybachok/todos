import type { Note } from './api/notesApi';
import { NoteForm } from './components/NoteForm';
import { useSaveNote } from './hooks/useNotes';

interface Props {
  note?: Note;
  onSaved: (note: Note) => void;
  onCancel: () => void;
}

export function NoteEditor({ note, onSaved, onCancel }: Props) {
  const save = useSaveNote();

  return (
    <NoteForm
      note={note}
      error={save.error}
      saving={save.isPending}
      onSubmit={(input) =>
        save.mutate({ id: note?.id, input }, { onSuccess: onSaved })
      }
      onCancel={onCancel}
    />
  );
}
