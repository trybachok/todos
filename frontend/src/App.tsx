import { ErrorMessage } from './components/ErrorMessage';
import { NoteList } from './components/NoteList';
import { useNoteList } from './hooks/useNotes';

export function App() {
  const list = useNoteList();

  return (
    <div className="app">
      <aside className="sidebar">
        <header>
          <h1>Заметки</h1>
        </header>
        {list.isPending ? (
          <p className="hint">Загрузка…</p>
        ) : list.isError ? (
          <ErrorMessage error={list.error} />
        ) : (
          <NoteList notes={list.data} />
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
    </div>
  );
}
