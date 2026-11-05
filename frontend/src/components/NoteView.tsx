import type { Note } from '../api/notesApi';

interface Props {
  note: Note;
  confirmingDelete: boolean;
  deleting: boolean;
  onEdit: () => void;
  onDelete: () => void;
  onConfirmDelete: () => void;
  onCancelDelete: () => void;
}

const dateFormat = new Intl.DateTimeFormat('ru', {
  dateStyle: 'medium',
  timeStyle: 'short',
});

export function NoteView({
  note,
  confirmingDelete,
  deleting,
  onEdit,
  onDelete,
  onConfirmDelete,
  onCancelDelete,
}: Props) {
  return (
    <article className="note">
      <h2>{note.title}</h2>
      <p className="meta">
        Создана <Time value={note.createdAt} />, изменена{' '}
        <Time value={note.updatedAt} />
      </p>
      {note.content !== '' && <p className="content">{note.content}</p>}
      {confirmingDelete ? (
        <div className="actions" role="group" aria-label="Удалить заметку?">
          <span>Удалить заметку?</span>
          <button
            type="button"
            className="danger"
            disabled={deleting}
            onClick={onConfirmDelete}
          >
            Да, удалить
          </button>
          <button type="button" disabled={deleting} onClick={onCancelDelete}>
            Отмена
          </button>
        </div>
      ) : (
        <div className="actions">
          <button type="button" onClick={onEdit}>
            Изменить
          </button>
          <button type="button" className="danger" onClick={onDelete}>
            Удалить
          </button>
        </div>
      )}
    </article>
  );
}

function Time({ value }: { value: string }) {
  return <time dateTime={value}>{dateFormat.format(new Date(value))}</time>;
}
