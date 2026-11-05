import { useState } from 'react';
import { NoteDetails } from './NoteDetails';
import { NoteEditor } from './NoteEditor';
import { ErrorMessage } from './components/ErrorMessage';
import { NoteList } from './components/NoteList';
import { useNoteList } from './hooks/useNotes';

type Pane =
  | { kind: 'empty' }
  | { kind: 'new' }
  | { kind: 'note'; id: number }
  | { kind: 'deleted' };

export function App() {
  const [pane, setPane] = useState<Pane>({ kind: 'empty' });
  const list = useNoteList();
  const open = (id: number) => setPane({ kind: 'note', id });

  return (
    <div className="app">
      <aside className="sidebar">
        <header>
          <h1>Заметки</h1>
          <button
            type="button"
            className="primary"
            onClick={() => setPane({ kind: 'new' })}
          >
            Новая заметка
          </button>
        </header>
        {list.isPending ? (
          <p className="hint">Загрузка…</p>
        ) : list.isError ? (
          <ErrorMessage error={list.error} />
        ) : (
          <NoteList
            notes={list.data}
            selectedId={pane.kind === 'note' ? pane.id : undefined}
            onSelect={open}
          />
        )}
        {list.hasNextPage && (
          <button
            type="button"
            disabled={list.isFetchingNextPage}
            onClick={() => void list.fetchNextPage()}
          >
            Показать ещё
          </button>
        )}
      </aside>
      <main className="pane">
        {pane.kind === 'empty' && (
          <p className="hint">Выберите заметку или создайте новую</p>
        )}
        {pane.kind === 'deleted' && (
          <p className="hint" role="status">
            Заметка удалена
          </p>
        )}
        {pane.kind === 'new' && (
          <NoteEditor
            onSaved={(note) => open(note.id)}
            onCancel={() => setPane({ kind: 'empty' })}
          />
        )}
        {pane.kind === 'note' && (
          <NoteDetails
            key={pane.id}
            id={pane.id}
            onDeleted={() => setPane({ kind: 'deleted' })}
          />
        )}
      </main>
    </div>
  );
}
