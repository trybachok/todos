import { useState } from 'react';
import { NoteEditor } from './NoteEditor';
import { ErrorMessage } from './components/ErrorMessage';
import { NoteView } from './components/NoteView';
import { useDeleteNote, useNote } from './hooks/useNotes';

interface Props {
  id: number;
  onDeleted: () => void;
}

export function NoteDetails({ id, onDeleted }: Props) {
  const note = useNote(id);
  const remove = useDeleteNote();
  const [mode, setMode] = useState<'view' | 'edit' | 'confirm-delete'>('view');

  if (note.isPending) {
    return <p className="hint">Загрузка…</p>;
  }
  if (note.isError) {
    return <ErrorMessage error={note.error} />;
  }
  if (mode === 'edit') {
    return (
      <NoteEditor
        note={note.data}
        onSaved={() => setMode('view')}
        onCancel={() => setMode('view')}
      />
    );
  }

  return (
    <>
      {remove.isError && <ErrorMessage error={remove.error} />}
      <NoteView
        note={note.data}
        confirmingDelete={mode === 'confirm-delete'}
        deleting={remove.isPending}
        onEdit={() => setMode('edit')}
        onDelete={() => setMode('confirm-delete')}
        onConfirmDelete={() => remove.mutate(id, { onSuccess: onDeleted })}
        onCancelDelete={() => setMode('view')}
      />
    </>
  );
}
