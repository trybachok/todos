import type { FormEvent } from 'react';
import { ApiError, type NoteInput } from '../api/notesApi';
import { ErrorMessage } from './ErrorMessage';

interface Props {
  note?: NoteInput;
  error: unknown;
  saving: boolean;
  onSubmit: (input: NoteInput) => void;
  onCancel: () => void;
}

// Validation rules live on the server; the form shows what it returns.
export function NoteForm({ note, error, saving, onSubmit, onCancel }: Props) {
  const fieldErrors =
    error instanceof ApiError ? (error.problem.errors ?? {}) : {};

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    onSubmit({
      title: String(data.get('title')),
      content: String(data.get('content')),
    });
  };

  return (
    <form className="note-form" onSubmit={submit}>
      {error != null && <ErrorMessage error={error} />}
      <label htmlFor="note-title">Заголовок</label>
      <input
        id="note-title"
        name="title"
        defaultValue={note?.title}
        autoFocus
        {...describedBy('note-title-error', fieldErrors.title)}
      />
      <FieldError id="note-title-error" message={fieldErrors.title} />
      <label htmlFor="note-content">Текст</label>
      <textarea
        id="note-content"
        name="content"
        rows={12}
        defaultValue={note?.content}
        {...describedBy('note-content-error', fieldErrors.content)}
      />
      <FieldError id="note-content-error" message={fieldErrors.content} />
      <div className="actions">
        <button type="submit" className="primary" disabled={saving}>
          Сохранить
        </button>
        <button type="button" disabled={saving} onClick={onCancel}>
          Отмена
        </button>
      </div>
    </form>
  );
}

function describedBy(id: string, message?: string) {
  return message === undefined
    ? {}
    : { 'aria-invalid': true, 'aria-describedby': id };
}

function FieldError({ id, message }: { id: string; message?: string }) {
  return message === undefined ? null : (
    <p id={id} className="field-error">
      {message}
    </p>
  );
}
